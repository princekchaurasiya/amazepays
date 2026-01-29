@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Select Brand to View Stores</h2>

    <form action="{{ route('admin.stores.fetch') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="brand_code">Choose Brand:</label>
            <select name="brand_code" class="form-control" required>
                @foreach($brands as $brand)
                    <option value="{{ $brand->brand_code }}">{{ $brand->brand_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Get Stores</button>
    </form>
</div>
@endsection
