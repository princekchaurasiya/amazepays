@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <h1>Saved Orders</h1>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>External Ref</th>
                <th>Variant ID</th>
                <th>MRP</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>{{ $order->external_ref }}</td>
                <td>{{ $order->variant_id }}</td>
                <td>{{ $order->mrp }}</td>
                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4">No orders found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{ $orders->links() }}
</div>
@endsection
