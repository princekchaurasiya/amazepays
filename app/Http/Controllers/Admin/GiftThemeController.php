<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCardTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GiftThemeController extends Controller
{
    public function index(): Response
    {
        $hasGalleryColumn = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'gallery_images');
        $hasThumbPath = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'thumbnail_path');
        $hasPreviewPath = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'preview_image_path');
        $hasThumbUrl = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'thumbnail_url');
        $hasImageUrl = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'image_url');

        $themes = GiftCardTheme::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(50)
            ->through(function (GiftCardTheme $theme) use ($hasGalleryColumn, $hasThumbPath, $hasPreviewPath, $hasThumbUrl, $hasImageUrl): array {
                $legacyPrimary = null;
                if ($hasThumbPath && is_string($theme->getAttribute('thumbnail_path'))) {
                    $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('thumbnail_path'));
                } elseif ($hasPreviewPath && is_string($theme->getAttribute('preview_image_path'))) {
                    $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('preview_image_path'));
                } elseif ($hasThumbUrl && is_string($theme->getAttribute('thumbnail_url'))) {
                    $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('thumbnail_url'));
                } elseif ($hasImageUrl && is_string($theme->getAttribute('image_url'))) {
                    $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('image_url'));
                }

                $galleryUrls = [];
                $galleryPaths = [];
                if ($hasGalleryColumn) {
                    $galleryPaths = collect((array) $theme->getAttribute('gallery_images'))
                        ->filter(static fn ($path): bool => is_string($path) && $path !== '')
                        ->values()
                        ->all();
                    $galleryUrls = collect($galleryPaths)
                        ->map(static fn ($path) => GiftCardTheme::resolveMediaUrl((string) $path))
                        ->filter()
                        ->values()
                        ->all();
                } elseif ($legacyPrimary) {
                    $galleryUrls = [$legacyPrimary];
                }

                return [
                    'id' => $theme->id,
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'primary_image_url' => $galleryUrls[0] ?? $legacyPrimary,
                    'gallery_urls' => $galleryUrls,
                    'gallery_paths' => $galleryPaths,
                    'is_active' => (bool) $theme->is_active,
                    'sort_order' => (int) $theme->sort_order,
                ];
            });

        return Inertia::render('Admin/GiftThemes/Index', [
            'themes' => $themes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data = array_merge($data, $this->storeUploadedMedia($request));
        GiftCardTheme::create($data);

        return back()->with('success', 'Gift theme created.');
    }

    public function update(Request $request, GiftCardTheme $theme): RedirectResponse
    {
        $data = $this->validatePayload($request, $theme);
        $data = array_merge($data, $this->storeUploadedMedia($request, $theme));
        $theme->update($data);

        return back()->with('success', 'Gift theme updated.');
    }

    public function toggle(GiftCardTheme $theme): RedirectResponse
    {
        $theme->update([
            'is_active' => ! $theme->is_active,
        ]);

        return back()->with('success', 'Gift theme status updated.');
    }

    public function destroy(GiftCardTheme $theme): RedirectResponse
    {
        $this->deleteThemeMedia($theme);
        $theme->delete();

        return back()->with('success', 'Gift theme removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?GiftCardTheme $theme = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('gift_card_themes', 'slug')->ignore($theme?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'gallery_files' => ['nullable', 'array', 'max:12'],
            'gallery_files.*' => ['image', 'max:7168'],
            'retained_gallery_images' => ['nullable', 'array', 'max:12'],
            'retained_gallery_images.*' => ['string', 'max:255'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storeUploadedMedia(Request $request, ?GiftCardTheme $theme = null): array
    {
        $payload = [];
        $folder = 'gift-themes';
        $uploadedGalleryFiles = $this->extractGalleryUploads($request);
        $hasUploadedGalleryFiles = count($uploadedGalleryFiles) > 0;
        $hasGalleryColumn = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'gallery_images');
        $hasThumbPath = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'thumbnail_path');
        $hasPreviewPath = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'preview_image_path');
        $hasThumbUrl = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'thumbnail_url');
        $hasImageUrl = Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'image_url');

        if ($hasGalleryColumn && $theme) {
            $existingPaths = array_values(array_filter((array) $theme->getAttribute('gallery_images'), static fn ($v): bool => is_string($v) && $v !== ''));
            $requestedRetained = array_values(array_filter(
                (array) $request->input('retained_gallery_images', $existingPaths),
                static fn ($v): bool => is_string($v) && $v !== ''
            ));
            $requestedSet = array_flip($requestedRetained);
            $galleryPaths = array_values(array_filter(
                $existingPaths,
                static fn (string $path): bool => array_key_exists($path, $requestedSet)
            ));
            $dropped = array_values(array_filter(
                $existingPaths,
                static fn (string $path): bool => ! array_key_exists($path, $requestedSet)
            ));
            $this->deleteManyPublicPaths($dropped);
            $seenHashes = [];
            foreach ($galleryPaths as $path) {
                $hash = $this->hashPublicPath($path);
                if ($hash) {
                    $seenHashes[$hash] = true;
                }
            }
        } elseif ($hasUploadedGalleryFiles) {
            $galleryPaths = [];
            $seenHashes = [];
        } else {
            $galleryPaths = [];
            $seenHashes = [];
        }

        if ($hasUploadedGalleryFiles) {
            foreach ($uploadedGalleryFiles as $image) {
                $uploadedHash = $this->hashUploadedFile($image);
                if ($uploadedHash && array_key_exists($uploadedHash, $seenHashes)) {
                    continue;
                }
                $stored = $this->storePublicFile($image, $folder);
                if ($stored) {
                    $galleryPaths[] = $stored;
                    $hash = $uploadedHash ?: $this->hashPublicPath($stored);
                    if ($hash) {
                        $seenHashes[$hash] = true;
                    }
                }
            }
        }

        $galleryPaths = array_values(array_unique($galleryPaths));

        if ($hasGalleryColumn) {
            if ($theme || $hasUploadedGalleryFiles) {
                $payload['gallery_images'] = $galleryPaths;
            }
        } else {
            if ($hasUploadedGalleryFiles) {
                $first = $galleryPaths[0] ?? null;
                if ($first) {
                    if ($hasThumbPath) {
                        $payload['thumbnail_path'] = $first;
                    }
                    if ($hasPreviewPath) {
                        $payload['preview_image_path'] = $first;
                    }
                    if ($hasThumbUrl) {
                        $payload['thumbnail_url'] = $first;
                    }
                    if ($hasImageUrl) {
                        $payload['image_url'] = $first;
                    }
                }
            }
        }

        return $payload;
    }

    private function storePublicFile(?UploadedFile $file, string $folder): ?string
    {
        if (! $file) {
            return null;
        }
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = $file->storeAs($folder, Str::uuid()->toString().'.'.$ext, 'public');

        return $path ?: null;
    }

    private function deleteThemeMedia(GiftCardTheme $theme): void
    {
        if (Schema::hasTable('gift_card_themes') && Schema::hasColumn('gift_card_themes', 'gallery_images')) {
            $this->deleteManyPublicPaths((array) $theme->getAttribute('gallery_images'));
        }
    }

    private function deleteManyPublicPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deletePublicPath(is_string($path) ? $path : null);
        }
    }

    /**
     * @return list<UploadedFile>
     */
    private function extractGalleryUploads(Request $request): array
    {
        $candidates = ['gallery_files', 'image', 'images', 'gallery_images'];
        foreach ($candidates as $key) {
            $direct = $request->file($key);
            if ($direct instanceof UploadedFile) {
                return [$direct];
            }
            if (is_array($direct) && $direct !== []) {
                return $this->flattenUploadedFiles($direct);
            }

            $nested = data_get($request->allFiles(), $key);
            if ($nested instanceof UploadedFile) {
                return [$nested];
            }
            if (is_array($nested) && $nested !== []) {
                return $this->flattenUploadedFiles($nested);
            }
        }

        // Last-resort fallback: if exactly one file exists in the request, treat it as gallery upload.
        $all = $this->flattenUploadedFiles($request->allFiles());
        if (count($all) === 1) {
            return $all;
        }

        return [];
    }

    /**
     * @param  mixed  $value
     * @return list<UploadedFile>
     */
    private function flattenUploadedFiles($value): array
    {
        if ($value instanceof UploadedFile) {
            return [$value];
        }
        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $entry) {
            foreach ($this->flattenUploadedFiles($entry) as $file) {
                $items[] = $file;
            }
        }

        return $items;
    }

    private function deletePublicPath(?string $path): void
    {
        if (! $path) {
            return;
        }
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function hashUploadedFile(UploadedFile $file): ?string
    {
        $realPath = $file->getRealPath();
        if (! $realPath || ! is_file($realPath)) {
            return null;
        }

        $hash = @hash_file('sha256', $realPath);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private function hashPublicPath(string $path): ?string
    {
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        if (! $absolute || ! is_file($absolute)) {
            return null;
        }

        $hash = @hash_file('sha256', $absolute);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }
}
