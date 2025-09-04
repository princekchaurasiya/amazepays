@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Orders</h2>

    @if(count($orders) > 0)
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>External Ref</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Order Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order['orderID'] }}</td>
                <td>{{ $order['externalRef'] }}</td>
                <td>{{ $order['status'] }}</td>
                <td>{{ $order['totalAmount'] }}</td>
                <td>{{ $order['orderDate'] }}</td>
                <td>
                    <a href="{{ route('orders.get', ['orderId' => $order['orderID']]) }}" class="btn btn-sm btn-info">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    <nav>
        <ul class="pagination">
            @for($i = 1; $i <= $meta['totalPages']; $i++)
                <li class="page-item {{ $meta['page'] == $i ? 'active' : '' }}">
                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                </li>
            @endfor
        </ul>
    </nav>
    @else
    <p>No orders found.</p>
    @endif
</div>
@endsection