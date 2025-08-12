@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Activated EVC Details</h2>

    <div class="card mt-3">
        <div class="card-body">
            <p><strong>Order ID:</strong> {{ $order_id }}</p>
            <p><strong>Request Ref No:</strong> {{ $request_ref_no }}</p>
        </div>
    </div>

    @if(isset($evc['data']) && count($evc['data']) > 0)
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>EVC Code</th>
                    <th>Status</th>
                    <th>Activated Date</th>
                    <th>Other Info</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evc['data'] as $item)
                    <tr>
                        <td>{{ $item['evc_code'] ?? '-' }}</td>
                        <td>{{ $item['status'] ?? '-' }}</td>
                        <td>{{ $item['activated_date'] ?? '-' }}</td>
                        <td>{{ $item['other_info'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-warning mt-4">
            No activated EVC found for this order.
        </div>
    @endif
</div>
@endsection
