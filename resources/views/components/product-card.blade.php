@props([
    'product',
])

@php
    $slug = $product->url ?? $product->slug ?? null;
    $href = $slug ? route('get-product-by-slug', ['slug' => $slug]) : '#';
    $img = $product->display_image_url ?? null;
    $discount = (float) ($product->discount_percentage ?? 0);
    $out = ! empty($product->out_of_stock) && $product->out_of_stock;
    $displayName = $product->display_name ?? $product->name ?? '';

    $palette = ['#1a1a2e', '#16213e', '#0f3460', '#533483', '#2b2d42', '#3d5a80', '#264653', '#2d6a4f'];
    $idx = abs(crc32((string) ($product->name ?? 'x'))) % count($palette);
    $bgColor = $palette[$idx];
    $bgDark = $palette[($idx + 4) % count($palette)];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'hubble-card text-decoration-none']) }}>
    <div class="hubble-card__img-wrap"
        style="background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $bgDark }} 100%);"
    >
        @if ($out)
            <span class="hubble-card__out">{{ __('storefront.out_of_stock') }}</span>
        @endif
        @if ($img)
            <img src="{{ $img }}" alt="{{ $displayName }}" class="hubble-card__img">
        @else
            <span class="hubble-card__placeholder">{{ strtoupper(substr((string) $displayName, 0, 1)) }}</span>
        @endif
        <div class="hubble-card__badge" aria-hidden="true">
            <img src="{{ asset('images/logo.png') }}" alt="">
        </div>
    </div>
    <div class="hubble-card__body">
        <p class="hubble-card__title mb-1">{{ $displayName }}</p>
        @if ($discount > 0)
            <p class="hubble-card__discount mb-0">
                {{ rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') }}% {{ __('storefront.off_suffix') }}
            </p>
        @endif
    </div>
</a>
