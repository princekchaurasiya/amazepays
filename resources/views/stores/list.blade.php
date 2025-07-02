@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <br/>
    <h2>Store List</h2>

    <form method="GET" class="row mb-4" action="{{ route('stores.filter') }}">
        <div class="col-md-3">
        <label>Search Store</label>
        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="e.g. Amazon">
        </div>
        <input type="hidden" name="brand_code" value="{{ $filters['brand_code'] ?? '' }}">
        <form method="GET" action="{{ route('stores.filter') }}" class="mb-4">

    <div class="row">
    <div class="col-md-3">
        <label>Brand Code</label>
        <select name="brand_code" class="form-control">
            <option value="">All</option>
            @foreach($brandcodes as $code)
                <option value="{{ $code }}" @if(($filters['brand_code'] ?? '') === $code) selected @endif>{{ $code }}</option>
            @endforeach
        </select>
    </div>
</div>

        <div class="col-md-3">
            <label>Brand Name</label>
            <select name="brand_name" class="form-control">
                <option value="">All</option>
                @foreach($brandnames as $name)
                    <option value="{{ $name }}" @if(($filters['brand_name'] ?? '') === $name) selected @endif>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label>Country</label>
            <select name="country" class="form-control">
                <option value="">All</option>
                @foreach($countries as $country)
                    <option value="{{ $country }}" @if(($filters['country'] ?? '') === $country) selected @endif>{{ $country }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label>State</label>
            <select name="state" class="form-control">
                <option value="">All</option>
                @foreach($states as $state)
                    <option value="{{ $state }}" @if(($filters['state'] ?? '') === $state) selected @endif>{{ $state }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-3">
            <label>City</label>
            <select name="city" class="form-control">
                <option value="">All</option>
                @foreach($cities as $city)
                    <option value="{{ $city }}" @if(($filters['city'] ?? '') === $city) selected @endif>{{ $city }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label>Contact Number</label>
            <input type="text" name="contact_number" class="form-control" value="{{ $filters['contact_number'] ?? '' }}">
        </div>

        <div class="col-md-3">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary form-control">Filter</button>
        </div>

        <div class="col-md-3">
            <label>&nbsp;</label>
            <a href="{{ route('stores.filter') }}" class="btn btn-secondary form-control">Reset</a>
        </div>
    </div>

        <div class="col-md-2 align-self-end">
            <button class="btn btn-secondary">Filter</button>
        </div>
        <div class="col-md-2 align-self-end">
        <a href="{{ route('stores.export', request()->query()) }}" class="btn btn-success">Export to Excel</a>
        </div>
    </form>

    @if($stores->isEmpty())
        <p>No stores found.</p>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Store Code</th>
                    <th>Brand Code</th>
                    <th>Brand Name</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Country</th>
                    <th>Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stores as $store)
                <tr>
                    <td>{{ $store->store_code }}</td>
                    <td>{{ $store->brand_code }}</td>
                    <td>{{ $store->brand_name }}</td>
                    <td>{{ $store->address ?? 'N/A' }}</td>
                    <td>{{ $store->city }}</td>
                    <td>{{ $store->state }}</td>
                    <td>{{ $store->country }}</td>
                    <td>{{ $store->contact_number ?? 'N/A' }}</td> 
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $stores->withQueryString()->links() }}
    @endif
</div>
@endsection
