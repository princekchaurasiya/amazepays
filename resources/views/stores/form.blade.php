@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Select Brand to Sync & View Stores</h2>

    <form method="POST" action="{{ route('stores.sync') }}">
        @csrf
        <div class="form-group">
            <label for="brand_code">Brand</label>
            <select name="brand_code" class="form-control" required>
                <option value="">Select</option>
                @foreach($brands as $code => $name)
                    <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Sync & Show Stores</button>
    </form>
</div>
@endsection
