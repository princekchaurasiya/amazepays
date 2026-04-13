@php
    /** Always show strip. When DB categories are empty, show Hubble-style quick links (header has search — no duplicate here). */
    $storefrontCategories = isset($storefrontCategories) ? $storefrontCategories : collect();
    $hasDbCategories = $storefrontCategories->isNotEmpty();
    $home = url('/');
@endphp
<nav class="storefront-category-nav border-b border-gray-200/80 bg-gradient-to-b from-slate-100 via-gray-50 to-white" aria-label="{{ __('storefront.categories') }}">
    <div class="mx-auto max-w-7xl px-3 py-3">
        <div class="rounded-[1.5rem] border border-gray-200/90 bg-white px-3 py-2.5 shadow-md shadow-gray-900/[0.06] ring-1 ring-black/[0.04]">
            <div class="flex flex-wrap items-start justify-center gap-x-1 gap-y-2 overflow-x-auto py-1 scrollbar-thin sm:justify-center md:flex-nowrap md:gap-2 md:overflow-visible">
                {{-- Browse (home) --}}
                <a href="{{ $home }}"
                    class="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 text-center text-gray-700 transition hover:bg-gray-50 {{ request()->routeIs('home') ? 'bg-gray-50 shadow-sm ring-2 ring-brand-500/25' : '' }}">
                    <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm ring-1 ring-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </span>
                    <span class="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">{{ __('storefront.category_browse') }}</span>
                </a>

                @unless($hasDbCategories)
                    {{-- Hubble-like shortcuts when no taxonomy rows yet (fills the strip visually) --}}
                    <a href="{{ $home }}#storefront-section-hot"
                        class="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 border-l border-gray-200 pl-3 text-center text-gray-700 transition hover:bg-gray-50 md:pl-4">
                        <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-orange-600 shadow-sm ring-1 ring-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                        </span>
                        <span class="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">{{ __('storefront.nav_quick_hot') }}</span>
                    </a>
                    <a href="{{ $home }}#storefront-section-brands"
                        class="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 text-center text-gray-700 transition hover:bg-gray-50">
                        <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </span>
                        <span class="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">{{ __('storefront.nav_quick_brands') }}</span>
                    </a>
                    <a href="{{ $home }}#storefront-section-deals"
                        class="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 text-center text-gray-700 transition hover:bg-gray-50">
                        <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm ring-1 ring-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">{{ __('storefront.nav_quick_deals') }}</span>
                    </a>
                @endunless

                @foreach($storefrontCategories as $cat)
                    @php
                        $active = request()->routeIs('categories.show') && request()->route('slug') === $cat->slug;
                    @endphp
                    <a href="{{ route('categories.show', $cat->slug) }}"
                        class="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 text-center text-gray-700 transition hover:bg-gray-50 {{ $active ? 'bg-gray-50 shadow-sm ring-2 ring-brand-500/25' : '' }} {{ $loop->first ? 'border-l border-gray-200 pl-3 md:pl-4' : '' }}">
                        <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-1 ring-gray-200">
                            @if(!empty($cat->thumbnail) && $cat->thumbnail !== 'null')
                                <img src="{{ Storage::url($cat->thumbnail) }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="text-sm font-bold text-brand-600">{{ strtoupper(substr($cat->name, 0, 1)) }}</span>
                            @endif
                        </span>
                        <span class="max-w-[5.5rem] truncate text-xs font-medium text-gray-800" title="{{ $cat->name }}">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>
