@props([
    'brand',
    'discount' => null,
])

@php
    $href = route('brands.show', ['slug' => $brand->slug]);
    $logo = ! empty($brand->logo) && $brand->logo !== 'null' ? Storage::url($brand->logo) : null;
    $discountVal = $discount !== null ? (float) $discount : 0.0;

    $palette = ['#1a1a2e', '#16213e', '#0f3460', '#533483', '#2b2d42', '#3d5a80', '#264653', '#2d6a4f'];
    $idx = abs(crc32((string) ($brand->name ?? 'x'))) % count($palette);
    $bgColor = $palette[$idx];
    $bgDark = $palette[($idx + 4) % count($palette)];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'hubble-card text-decoration-none']) }}>
    <div
        class="hubble-card__img-wrap"
        style="background: linear-gradient(160deg, {{ $bgColor }} 0%, {{ $bgDark }} 100%);"
    >
        @if ($logo)
            <img src="{{ $logo }}" alt="{{ $brand->name }}" class="hubble-card__img">
        @else
            <span class="hubble-card__placeholder">{{ strtoupper(substr((string) $brand->name, 0, 1)) }}</span>
        @endif
        <div class="hubble-card__badge" aria-hidden="true">
            <img src="{{ asset('images/logo.png') }}" alt="">
        </div>
    </div>
    <div class="hubble-card__body">
        <p class="hubble-card__title mb-1">{{ $brand->name }}</p>
        @if ($discountVal > 0)
            <p class="hubble-card__discount mb-0">
                {{ rtrim(rtrim(number_format($discountVal, 2, '.', ''), '0'), '.') }}% {{ __('storefront.off_suffix') }}
            </p>
        @endif
    </div>
</a>
