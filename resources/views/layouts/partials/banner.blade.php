<div class="banner-wrapper style1 bg-image-contain">
    <!-- Desktop Carousel (hidden on mobile) -->
    <div id="desktopCarousel" class="carousel slide d-none d-md-block" data-ride="carousel">
        @if ($slides->whereNotNull('desktop_image')->count() > 1)
            <ol class="carousel-indicators">
                @foreach ($slides as $index => $slide)
                    @if (!is_null($slide->desktop_image) && !empty($slide->desktop_image))
                        <li data-target="#desktopCarousel" data-slide-to="{{ $index }}"
                            class="{{ $index === 0 ? 'active' : '' }}"></li>
                    @endif
                @endforeach
            </ol>
        @endif
        <div class="carousel-inner">
            @foreach ($slides as $index => $slide)
                @if (!is_null($slide->desktop_image) && !empty($slide->desktop_image))
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}"
                        style="cursor: {{ $slide->is_linked ? 'pointer' : 'default' }};">
                        @if ($slide->product_id)
                            <a href="{{ route('get-product-by-slug', ['slug' => $slide->slug]) }}">
                                <img src="{{ Storage::url($slide->desktop_image) }}" alt="{{ $slide->img_alt_tag }}"
                                    class="d-block w-100">
                            </a>
                        @elseif($slide->category_id)
                            <a href="{{ route('categories.show', ['slug' => $slide->slug]) }}">
                                <img src="{{ Storage::url($slide->desktop_image) }}" alt="{{ $slide->img_alt_tag }}"
                                    class="d-block w-100">
                            </a>
                        @elseif($slide->brand_id)
                            <a href="{{ route('brands.show', ['slug' => $slide->slug]) }}">
                                <img src="{{ Storage::url($slide->desktop_image) }}" alt="{{ $slide->img_alt_tag }}"
                                    class="d-block w-100">
                            </a>
                        @else
                            <img src="{{ Storage::url($slide->desktop_image) }}" alt="{{ $slide->img_alt_tag }}"
                                class="d-block w-100">
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
        @if ($slides->whereNotNull('desktop_image')->count() > 1)
            <a class="carousel-control-prev" href="#desktopCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#desktopCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        @endif
    </div>
    <!-- Mobile Carousel (hidden on desktop) -->
    <div id="mobileCarousel" class="carousel slide d-block d-md-none" data-ride="carousel">
        @if ($slides->whereNotNull('image_mobile')->count() > 1)
            <ol class="carousel-indicators">
                @foreach ($slides as $index => $slide)
                    @if (!is_null($slide->image_mobile) && !empty($slide->image_mobile))
                        <li data-target="#mobileCarousel" data-slide-to="{{ $index }}"
                            class="{{ $index === 0 ? 'active' : '' }}"></li>
                    @endif
                @endforeach
            </ol>
        @endif
        <div class="carousel-inner">
            @foreach ($slides as $index => $slide)
                @if (!is_null($slide->image_mobile) && !empty($slide->image_mobile))
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}"
                        style="cursor: {{ $slide->is_linked ? 'pointer' : 'default' }};">
                        @if ($slide->product_id)
                            <a href="{{ route('get-product-by-slug', ['slug' => $slide->slug]) }}">
                                <img src="{{ Storage::url($slide->image_mobile) }}" alt="{{ $slide->img_alt_tag }}"
                                    class="d-block w-100">
                            </a>
                        @elseif($slide->category_id)
                            <a href="{{ route('categories.show', ['slug' => $slide->slug]) }}">
                                <img src="{{ Storage::url($slide->image_mobile) }}" alt="{{ $slide->img_alt_tag }}"
                                    class="d-block w-100">
                            </a>
                        @elseif($slide->brand_id)
                            <a href="{{ route('brands.show', ['slug' => $slide->slug]) }}">
                                <img src="{{ Storage::url($slide->image_mobile) }}" alt="{{ $slide->img_alt_tag }}"
                                    class="d-block w-100">
                            </a>
                        @else
                            <img src="{{ Storage::url($slide->image_mobile) }}" alt="{{ $slide->img_alt_tag }}"
                                class="d-block w-100">
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
        @if ($slides->whereNotNull('image_mobile')->count() > 1)
            <a class="carousel-control-prev" href="#mobileCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#mobileCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        @endif
    </div>
</div>
