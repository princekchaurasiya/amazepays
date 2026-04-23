<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Admin panel — structured business settings only. Secrets stay in .env.
 */
class SettingsController extends Controller
{
    /** @var array<string, array{label: string, type: string, help?: string, default: string|int|float|bool}> */
    public const DEFINITIONS = [
        'company.gst_rate' => [
            'label' => 'GST rate (%)',
            'type' => 'number',
            'help' => 'Default GST percentage shown on invoices and pricing where applicable.',
            'default' => 18,
        ],
        'company.invoice_tnc' => [
            'label' => 'Invoice terms & conditions',
            'type' => 'textarea',
            'help' => 'Text or HTML shown on invoices and customer communications.',
            'default' => '',
        ],
        'company.display_name' => [
            'label' => 'Company display name',
            'type' => 'text',
            'help' => 'Public-facing company name in emails and documents (override; falls back to COMPANY_NAME in .env if empty).',
            'default' => '',
        ],
        'limits.daily_purchase' => [
            'label' => 'Default daily purchase limit (INR)',
            'type' => 'number',
            'help' => 'Default cap per user per day when no per-user override is set.',
            'default' => 50000,
        ],
        'limits.monthly_purchase' => [
            'label' => 'Default monthly purchase limit (INR)',
            'type' => 'number',
            'help' => 'Default cap per user per month when no per-user override is set.',
            'default' => 200000,
        ],
        'limits.max_order_quantity' => [
            'label' => 'Max order quantity',
            'type' => 'number',
            'help' => 'Maximum units per line item or order (enforced in checkout where applicable).',
            'default' => 10,
        ],
        'notifications.admin_email' => [
            'label' => 'Admin notification email',
            'type' => 'email',
            'help' => 'Primary admin inbox for operational alerts (override; falls back to CONTACT_US_ADMIN_EMAIL in .env).',
            'default' => '',
        ],
        'notifications.order_failure_email' => [
            'label' => 'Order failure alert email',
            'type' => 'email',
            'help' => 'Receives order failure notifications (override; falls back to ORDER_FAILURE_ADMIN_EMAIL in .env).',
            'default' => '',
        ],
        'notifications.it_admin_email' => [
            'label' => 'IT admin email',
            'type' => 'email',
            'help' => 'Technical / integration alerts (override; falls back to CONTACT_US_IT_ADMIN_EMAIL in .env).',
            'default' => '',
        ],
    ];

    public const GROUPS = [
        'company' => [
            'title' => 'Company & invoice',
            'keys' => ['company.gst_rate', 'company.display_name', 'company.invoice_tnc'],
        ],
        'limits' => [
            'title' => 'Purchase limits',
            'keys' => ['limits.daily_purchase', 'limits.monthly_purchase', 'limits.max_order_quantity'],
        ],
        'notifications' => [
            'title' => 'Notifications',
            'keys' => ['notifications.admin_email', 'notifications.order_failure_email', 'notifications.it_admin_email'],
        ],
    ];

    public function index()
    {
        $this->authorize('settings.view');

        $fromDb = DB::table('settings')->pluck('value', 'key')->all();

        $groups = [];
        foreach (self::GROUPS as $groupId => $meta) {
            $fields = [];
            foreach ($meta['keys'] as $key) {
                $def = self::DEFINITIONS[$key];
                $default = $this->envDefaultForKey($key, $def['default']);
                $raw = $fromDb[$key] ?? null;
                $fields[] = [
                    'key' => $key,
                    'label' => $def['label'],
                    'type' => $def['type'],
                    'help' => $def['help'] ?? null,
                    'value' => $raw !== null && $raw !== '' ? $this->castOut($key, $raw) : $this->castOut($key, (string) $default),
                ];
            }
            $groups[] = [
                'id' => $groupId,
                'title' => $meta['title'],
                'fields' => $fields,
            ];
        }

        $sections = HomepageSection::query()
            ->ordered()
            ->get()
            ->map(fn (HomepageSection $s) => [
                'id' => $s->id,
                'section_name' => $s->section_name,
                'section_type' => $s->section_type,
                'title' => $s->title,
                'content' => $s->content,
                'status' => (bool) $s->status,
                'sort_order' => (int) $s->sort_order,
                'config' => $s->config ?? [],
            ])
            ->values()
            ->all();

        $homepageSectionTypes = collect(HomepageSection::SECTION_TYPES)
            ->map(fn (string $t) => [
                'value' => $t,
                'label' => match ($t) {
                    'hot_deals' => 'Hot deals (products with a hot deal rank)',
                    'other_deals' => 'Other deals',
                    'kgen' => 'KGen Technology (API product grid)',
                    'custom_html' => 'Custom HTML',
                    default => str_replace('_', ' ', ucwords($t, '_')),
                },
            ])
            ->values()
            ->all();

        return Inertia::render('Admin/Settings/Index', [
            'groups' => $groups,
            'sections' => $sections,
            'homepageSectionTypes' => $homepageSectionTypes,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('settings.update');

        $rules = ['settings' => 'required|array'];
        foreach (self::DEFINITIONS as $key => $def) {
            $field = 'settings.'.$key;
            if (($def['type'] ?? '') === 'toggle') {
                $rules[$field] = 'nullable|boolean';
            } elseif (str_contains($key, 'gst_rate') || str_starts_with($key, 'limits.') || ($def['type'] ?? '') === 'number') {
                $rules[$field] = 'nullable|numeric|min:0';
            } elseif (str_contains($key, 'email')) {
                $rules[$field] = 'nullable|email|max:255';
            } else {
                $rules[$field] = 'nullable|string|max:65000';
            }
        }

        $validated = $request->validate($rules);
        $payload = [];
        foreach (array_keys(self::DEFINITIONS) as $key) {
            if (array_key_exists($key, $validated['settings'])) {
                $payload[$key] = $validated['settings'][$key];
            }
        }

        $old = [];
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $old[$key] = DB::table('settings')->where('key', $key)->value('value');
        }

        foreach ($payload as $key => $value) {
            if (! array_key_exists($key, self::DEFINITIONS)) {
                continue;
            }
            $stored = $value === null ? '' : (string) $value;
            $row = [
                'value' => $stored,
                'updated_at' => now(),
            ];
            if (DB::table('settings')->where('key', $key)->doesntExist()) {
                $row['created_at'] = now();
            }
            DB::table('settings')->updateOrInsert(['key' => $key], $row);
        }

        audit('settings.updated', null, $old, $payload);

        return back()->with('success', 'Settings updated successfully.');
    }

    public function storeSection(Request $request)
    {
        $this->authorize('settings.update');

        $validated = $request->validate([
            'section_name' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', 'unique:homepage_sections,section_name'],
            'section_type' => ['required', 'string', Rule::in(HomepageSection::SECTION_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:65000'],
            'status' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'config' => ['nullable', 'array'],
            'config.priority_product_count' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $maxSort = HomepageSection::queryMaxSortOrder();

        $attributes = [
            'section_name' => $validated['section_name'],
            'section_type' => $validated['section_type'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
            'status' => $validated['status'] ?? true,
            'config' => $this->normalizeSectionConfig($validated['section_type'], $validated['config'] ?? [], null),
        ];
        if (HomepageSection::hasSortOrderColumn()) {
            $attributes['sort_order'] = $validated['sort_order'] ?? ($maxSort + 1);
        }

        $section = HomepageSection::create($attributes);

        audit('homepage_section.created', $section, [], $section->toArray());

        return back()->with('success', 'Homepage section created.');
    }

    public function updateSection(Request $request, HomepageSection $section)
    {
        $this->authorize('settings.update');

        $validated = $request->validate([
            'section_name' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', Rule::unique('homepage_sections', 'section_name')->ignore($section->id)],
            'section_type' => ['required', 'string', Rule::in(HomepageSection::SECTION_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:65000'],
            'status' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'config' => ['nullable', 'array'],
            'config.priority_product_count' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $old = $section->only(['section_name', 'section_type', 'title', 'content', 'status', 'sort_order', 'config']);

        $update = [
            'section_name' => $validated['section_name'],
            'section_type' => $validated['section_type'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
            'status' => array_key_exists('status', $validated) ? (bool) $validated['status'] : $section->status,
            'config' => $this->normalizeSectionConfig(
                $validated['section_type'],
                $validated['config'] ?? [],
                $section->config ?? null
            ),
        ];
        if (HomepageSection::hasSortOrderColumn()) {
            $update['sort_order'] = $validated['sort_order'] ?? $section->sort_order;
        }

        $section->update($update);

        audit('homepage_section.updated', $section, $old, $section->fresh()->toArray());

        return back()->with('success', 'Homepage section updated.');
    }

    public function destroySection(HomepageSection $section)
    {
        $this->authorize('settings.update');

        $snapshot = $section->toArray();
        $section->delete();

        audit('homepage_section.deleted', null, $snapshot, []);

        return back()->with('success', 'Homepage section removed.');
    }

    public function reorderSections(Request $request)
    {
        $this->authorize('settings.update');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:homepage_sections,id'],
        ]);

        if (! HomepageSection::hasSortOrderColumn()) {
            return back()->with('warning', 'Add the sort_order column (run migrations) to enable section reordering.');
        }

        foreach ($validated['ids'] as $index => $id) {
            HomepageSection::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        audit('homepage_section.reordered', null, [], ['ids' => $validated['ids']]);

        return back()->with('success', 'Section order saved.');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>|null  $previous
     * @return array<string, mixed>|null
     */
    private function normalizeSectionConfig(string $sectionType, array $config, ?array $previous): ?array
    {
        if ($sectionType === 'hot_deals') {
            $fallback = (int) data_get($previous, 'priority_product_count', 10);
            $n = (int) ($config['priority_product_count'] ?? $fallback);

            return ['priority_product_count' => max(1, min(500, $n))];
        }

        return null;
    }

    private function envDefaultForKey(string $key, mixed $fallback): string|int|float
    {
        return match ($key) {
            'company.display_name' => (string) (config('companyDefaultValues.company_name') ?: env('COMPANY_NAME', (string) $fallback)),
            'notifications.admin_email' => (string) (env('CONTACT_US_ADMIN_EMAIL', (string) $fallback)),
            'notifications.order_failure_email' => (string) (env('ORDER_FAILURE_ADMIN_EMAIL', (string) $fallback)),
            'notifications.it_admin_email' => (string) (env('CONTACT_US_IT_ADMIN_EMAIL', (string) $fallback)),
            default => $fallback,
        };
    }

    private function castOut(string $key, string $raw): string|float|int|bool
    {
        $def = self::DEFINITIONS[$key] ?? null;
        if ($def && ($def['type'] ?? '') === 'toggle') {
            return (bool) (int) $raw;
        }
        if (str_contains($key, 'gst_rate') || str_starts_with($key, 'limits.') || ($def && ($def['type'] ?? '') === 'number')) {
            return is_numeric($raw) ? (str_contains($raw, '.') ? (float) $raw : (int) $raw) : $raw;
        }

        return $raw;
    }
}
