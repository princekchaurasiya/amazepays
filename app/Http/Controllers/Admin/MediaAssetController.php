<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Media\Models\MediaAsset;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaAssetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('settings.view');

        $tenantId = $this->resolveTenantId($request);

        $assets = MediaAsset::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->paginate(48)
            ->through(fn (MediaAsset $a) => [
                'id' => $a->id,
                'path' => $a->path,
                'disk' => $a->disk,
                'mime' => $a->mime,
                'width' => $a->width,
                'height' => $a->height,
                'alt_text' => $a->alt_text,
                'url' => $this->absoluteStorageUrl($a->path),
                'created_at' => optional($a->created_at)->toISOString(),
            ]);

        return Inertia::render('Admin/Media/Index', [
            'assets' => $assets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:8192'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $path = $file->storeAs('media', Str::uuid()->toString().'.'.$ext, 'public');

        MediaAsset::create([
            'tenant_id' => $tenantId,
            'path' => $path,
            'disk' => 'public',
            'mime' => $file->getClientMimeType(),
            'alt_text' => $validated['alt_text'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Asset uploaded.');
    }

    public function destroy(Request $request, MediaAsset $asset): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($asset->tenant_id !== $tenantId, 404);

        if ($asset->path && Storage::disk('public')->exists($asset->path)) {
            Storage::disk('public')->delete($asset->path);
        }

        $asset->delete();

        return back()->with('success', 'Asset deleted.');
    }

    private function absoluteStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return url(Storage::url($path));
    }

    private function resolveTenantId(Request $request): int
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant && method_exists($tenant, 'getKey')) {
            return (int) $tenant->getKey();
        }

        $id = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
        if (is_int($id) && $id > 0) {
            return $id;
        }

        if (! Schema::hasTable('tenants')) {
            return 1;
        }

        return (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 1);
    }
}

