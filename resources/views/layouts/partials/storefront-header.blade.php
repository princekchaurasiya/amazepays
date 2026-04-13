@php
    $searchPlaceholder = __('storefront.search_placeholder');
@endphp

<header class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3 md:flex-nowrap md:gap-6">
        <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" alt="{{ __('storefront.brand_name') }}" class="h-9 w-auto object-contain md:h-10">
        </a>

        <form action="{{ route('search') }}" method="GET" class="order-3 w-full md:order-none md:mx-auto md:max-w-xl md:flex-1">
            <label class="sr-only" for="storefront-search">{{ $searchPlaceholder }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input id="storefront-search" type="search" name="query" value="{{ request('query') }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-full border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-500 shadow-sm transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                    autocomplete="off">
            </div>
        </form>

        <nav class="ml-auto flex shrink-0 items-center gap-2 md:gap-4" aria-label="{{ __('storefront.nav_home') }}">
            <a href="{{ url('/business') }}" class="hidden text-sm font-medium text-gray-700 hover:text-brand-600 sm:inline">
                {{ __('storefront.nav_for_business') }}
            </a>
            @auth
                <div class="relative hidden items-center gap-2 sm:flex">
                    <a href="{{ route('profile') }}" class="text-sm font-medium text-gray-700 hover:text-brand-600">{{ __('storefront.nav_profile') }}</a>
                    <form action="{{ route('userLogOut') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('storefront.nav_logout') }}</button>
                    </form>
                </div>
            @else
                <a href="#" data-open-auth-modal
                    class="inline-flex items-center rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    {{ __('storefront.nav_login_signup') }}
                </a>
            @endauth

            <details class="relative sm:hidden">
                <summary class="list-none cursor-pointer rounded-full border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                    <span class="sr-only">Menu</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <div class="absolute right-0 mt-2 w-48 rounded-xl border border-gray-100 bg-white py-2 shadow-lg">
                    <a href="{{ url('/business') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('storefront.nav_for_business') }}</a>
                    <a href="{{ route('about') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('storefront.nav_about') }}</a>
                    <a href="{{ route('contact-us') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('storefront.nav_contact') }}</a>
                    @auth
                        <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('storefront.nav_profile') }}</a>
                        <a href="{{ route('my-order') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('storefront.nav_my_orders') }}</a>
                        <form action="{{ route('userLogOut') }}" method="POST" class="border-t border-gray-100 px-4 pt-2">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-red-600">{{ __('storefront.nav_logout') }}</button>
                        </form>
                    @endauth
                </div>
            </details>
        </nav>
    </div>
</header>
