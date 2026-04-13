@extends('layouts.app')

@section('title')
    {{ __('storefront.business.hero_title') }} | {{ config('app.name') }}
@endsection

@section('content')
    <div class="border-b border-emerald-100 bg-gradient-to-b from-emerald-50/80 to-white">
        <div class="mx-auto max-w-7xl px-4 py-10 md:py-14">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-800">{{ __('storefront.nav_for_business') }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl">
                        {{ __('storefront.business.hero_title') }}
                    </h1>
                    <p class="mt-3 max-w-xl text-gray-600">{{ __('storefront.business.hero_subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ url('/panel/b2b/wallet') }}"
                            class="inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                            {{ __('storefront.business.cta_portal') }}
                        </a>
                        <a href="{{ route('contact-us') }}"
                            class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                            {{ __('storefront.business.cta_contact') }}
                        </a>
                    </div>
                    <p class="mt-4 text-xs text-gray-500">{{ __('storefront.business.footer_note') }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-emerald-100 md:min-w-[220px]">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('storefront.business.saved_label') }}</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $savingsDisplay }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10">
        @if($categories->isNotEmpty())
            <section class="mb-10" aria-labelledby="biz-cat-heading">
                <p id="biz-cat-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                    {{ __('storefront.business.section_categories') }}
                </p>
                <div class="flex gap-2 overflow-x-auto pb-2 md:flex-wrap md:justify-center">
                    @foreach($categories as $cat)
                        <a href="{{ route('categories.show', $cat->slug) }}"
                            class="shrink-0 rounded-full bg-white px-4 py-2 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-gray-100 hover:ring-brand-500/30">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($brands->isNotEmpty())
            <section class="mb-10" aria-labelledby="biz-brand-heading">
                <p id="biz-brand-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                    {{ __('storefront.business.section_brands') }}
                </p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach($brands as $brand)
                        <x-brand-card
                            :brand="$brand"
                            :discount="$brandMaxDiscounts[$brand->id] ?? null" />
                    @endforeach
                </div>
            </section>
        @endif

        @if($featuredProducts->isNotEmpty())
            <section aria-labelledby="biz-prod-heading">
                <p id="biz-prod-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                    {{ __('storefront.hot_deals') }}
                </p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @foreach($featuredProducts as $product)
                        @if($product->slug)
                            <x-product-card :product="$product" />
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
