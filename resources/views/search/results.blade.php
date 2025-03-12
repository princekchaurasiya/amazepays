@extends('layouts.app')

@section('content')
@php
    use App\Helpers\CommonHelper;
@endphp

<div class="product-wrapper pt-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-12">
            @if ($results->isEmpty())
            <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text-danger">No results found for "{{ request('query') }}"</h1>
            @else
            <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">Search Result for "{{ request('query') }}"</h1>
            @endif
            <hr class="normalhr">
        </div>
    </div>

    @if (!$results->isEmpty())
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-12 col-md-12 col-lg-10 col-xl-10">
                    <div class="row">
                        @foreach ($results as $result)


                        <div class="col-lg-3 col-6">
                            <div class="product-wrapper-image">
                                <a href="{{ route('get-product-by-slug', ['slug' => $result->url]) }}" class="d-block text-center">
                                    <p class="single-image-wrapper">
                                        <img src="{{ CommonHelper::getProductImage($result) }}"
                                             alt="product-image"
                                             class="w-100 mt-4 d-inline-block">
                                    </p>
                                </a>
                                <hr>
                                <a href="{{ route('get-product-by-slug', ['slug' => $result->url]) }}">
                                    <div class="product-image-text-wrapper m-lg-1">
                                        <p class="text-center fw-600 text-product-name-color text-product-name-font-size mt-lg-2 mt-3">
                                            {{ ucwords($result->name) }}
                                        </p>
                                    </div>
                                </a>
                                @if ($result->discount_percentage && $result->discount_percentage > 0)
                                    <div class="ribbon"><span>{{ $result->discount_percentage }}% off</span></div>
                                @endif
                            </div>
                        </div>


                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
