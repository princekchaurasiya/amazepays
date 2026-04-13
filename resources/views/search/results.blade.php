@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        @if ($results->isEmpty())
            <h1 class="text-center text-xl font-bold text-gray-900 md:text-2xl">
                {{ __('storefront.search.no_results', ['query' => request('query')]) }}
            </h1>
        @else
            <h1 class="text-center text-xl font-bold text-gray-900 md:text-2xl">
                {{ __('storefront.search.results_for', ['query' => request('query')]) }}
            </h1>
            <p class="mt-2 text-center text-sm text-gray-500">{{ __('storefront.brands_count', ['count' => $results->count()]) }}</p>
        @endif

        @if (!$results->isEmpty())
            <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach ($results as $result)
                    <x-product-card :product="$result" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
