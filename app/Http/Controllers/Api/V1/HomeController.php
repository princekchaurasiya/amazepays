<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Slide;
use App\Services\Storefront\SlidePresentationService;
use Illuminate\Http\JsonResponse;

/**
 * Public storefront home payload (hero slides) for mobile and external clients.
 */
class HomeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SlidePresentationService $slidePresentation,
    ) {}

    public function index(): JsonResponse
    {
        $slides = $this->slidePresentation->homepageSlides();
        $this->slidePresentation->attachSlugs($slides);

        $payload = $slides
            ->map(fn (Slide $slide) => $this->slidePresentation->toPublicArray($slide))
            ->values()
            ->all();

        return $this->ok('Home data retrieved.', [
            'slides' => $payload,
        ]);
    }
}
