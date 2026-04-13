@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <nav class="mb-6 text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ url('/') }}" class="hover:text-brand-600">{{ __('storefront.breadcrumb_home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-medium text-gray-900">{{ $brand->name }}</li>
            </ol>
        </nav>

        <div class="flex flex-col items-center gap-4 md:flex-row md:items-start md:gap-8">
            @if (!empty($brand->logo))
                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gray-50 ring-1 ring-gray-100 md:h-28 md:w-28">
                    <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-full max-w-full object-contain p-2">
                </div>
            @endif
            <div class="text-center md:text-left">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 md:text-3xl">{{ $brand->name }}</h1>
                @if($products->isNotEmpty())
                    <p class="mt-2 text-sm text-gray-500">{{ __('storefront.brands_count', ['count' => $products->count()]) }}</p>
                @endif
            </div>
        </div>

        @if (!empty($allBrands) && $allBrands->isNotEmpty())
            <div class="mt-8 flex gap-2 overflow-x-auto pb-2 md:flex-wrap md:justify-center">
                @foreach ($allBrands as $singleBrand)
                    @php $active = isset($singleBrand->id, $brand->id) && (int) $singleBrand->id === (int) $brand->id; @endphp
                    <a href="{{ route('brands.show', ['slug' => $singleBrand->slug ?? '#']) }}"
                        class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition {{ $active ? 'bg-gray-900 text-white' : 'bg-white text-gray-800 ring-1 ring-gray-200 hover:ring-brand-500/30' }}">
                        {{ $singleBrand->name ?? '—' }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @forelse ($products as $product)
                @if ($product->slug)
                    <x-product-card :product="$product" />
                @endif
            @empty
                <p class="col-span-full py-12 text-center text-gray-500">{{ __('storefront.empty_brand') }}</p>
            @endforelse
        </div>
    </div>
@endsection
