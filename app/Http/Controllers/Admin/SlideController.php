<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use App\Models\StorefrontBrand;
use App\Services\Storefront\SlidePresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SlideController extends Controller
{
    public function __construct(
        private SlidePresentationService $slidePresentation,
    ) {}

    public function index(): Response
    {
        $this->authorize('settings.view');

        $slides = Slide::query()
            ->orderByRaw('priority IS NULL ASC')
            ->orderBy('priority', 'asc')
            ->orderByDesc('id')
            ->paginate(24)
            ->through(fn (Slide $s) => [
                'id' => $s->id,
                'priority' => $s->priority,
                'status' => $s->status,
                'display_on_page' => $s->display_on_page,
                'img_alt_tag' => $s->img_alt_tag,
                'desktop_image' => $this->slidePresentation->absoluteStorageUrl($s->desktop_image),
                'image_mobile' => $this->slidePresentation->absoluteStorageUrl($s->image_mobile),
                'product_id' => $s->product_id,
                'category_id' => $s->category_id,
                'brand_id' => $s->brand_id,
            ]);

        return Inertia::render('Admin/Slides/Index', [
            'slides' => $slides,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('settings.update');

        return Inertia::render('Admin/Slides/Form', [
            'slide' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $this->validated($request, null, true);

        $desk = $this->storeUploaded($request->file('desktop_image_file'), 'desktop');
        $mob = $this->storeUploaded($request->file('image_mobile_file'), 'mobile');

        if ($desk) {
            $data['desktop_image'] = $desk;
        }
        if ($mob) {
            $data['image_mobile'] = $mob;
        }

        Slide::create($data);

        return redirect()->route('panel.settings.hero-slides.index')->with('success', 'Slide created.');
    }

    public function edit(Slide $slide): Response
    {
        $this->authorize('settings.update');

        return Inertia::render('Admin/Slides/Form', [
            'slide' => array_merge($slide->toArray(), [
                'desktop_image_url' => $this->slidePresentation->absoluteStorageUrl($slide->desktop_image),
                'image_mobile_url' => $this->slidePresentation->absoluteStorageUrl($slide->image_mobile),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Slide $slide): RedirectResponse
    {
        $this->authorize('settings.update');

        $data = $this->validated($request, $slide, false);

        if ($request->hasFile('desktop_image_file')) {
            $this->deletePublicPath($slide->desktop_image);
            $path = $this->storeUploaded($request->file('desktop_image_file'), 'desktop');
            if ($path) {
                $data['desktop_image'] = $path;
            }
        }

        if ($request->hasFile('image_mobile_file')) {
            $this->deletePublicPath($slide->image_mobile);
            $path = $this->storeUploaded($request->file('image_mobile_file'), 'mobile');
            if ($path) {
                $data['image_mobile'] = $path;
            }
        }

        $slide->update($data);

        return back()->with('success', 'Slide updated.');
    }

    public function destroy(Slide $slide): RedirectResponse
    {
        $this->authorize('settings.update');

        $this->deletePublicPath($slide->desktop_image);
        $this->deletePublicPath($slide->image_mobile);
        $slide->delete();

        return redirect()->route('panel.settings.hero-slides.index')->with('success', 'Slide removed.');
    }

    /**
     * @return array{products: Collection, categories: Collection, brands: Collection}
     */
    private function formOptions(): array
    {
        return [
            'products' => Product::visible()->select('id', 'name', 'sku')->orderBy('name')->limit(500)->get(),
            'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
            'brands' => StorefrontBrand::query()->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Slide $slide, bool $isCreate): array
    {
        $request->validate([
            'desktop_image_file' => 'nullable|image|max:6144',
            'image_mobile_file' => 'nullable|image|max:6144',
            'small_header' => 'nullable|string|max:255',
            'big_header' => 'nullable|string|max:255',
            'cta_value' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:2048',
            'priority' => 'nullable|integer|min:0|max:999999',
            'slider_location' => 'nullable|string|max:64',
            'status' => 'required|integer|in:0,1',
            'img_alt_tag' => 'nullable|string|max:255',
            'display_on_page' => 'nullable|string|max:64',
            'product_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:storefront_brands,id',
            'custom_url' => 'nullable|string|max:2048',
            'link_type' => 'nullable|string|max:64',
        ]);

        $data = $request->only([
            'small_header',
            'big_header',
            'cta_value',
            'cta_link',
            'priority',
            'slider_location',
            'status',
            'img_alt_tag',
            'display_on_page',
            'product_id',
            'category_id',
            'brand_id',
            'custom_url',
            'link_type',
        ]);

        if ($isCreate && empty($data['display_on_page'])) {
            $data['display_on_page'] = 'homepage';
        }

        return $data;
    }

    private function storeUploaded(?UploadedFile $file, string $prefix): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = $file->storeAs('slides', $prefix.'-'.Str::uuid()->toString().'.'.$ext, 'public');

        return $path ?: null;
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
