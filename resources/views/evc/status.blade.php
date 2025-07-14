@extends('layouts.app')

@section('content')
<div class="container">
    <h2>EVC Status Details</h2>

    <table class="table table-bordered">
        <tr>
            <th>Order ID</th>
            <td>{{ $order_id }}</td>
        </tr>
        <tr>
            <th>Request Ref No</th>
            <td>{{ $request_ref_no }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $status }}</td>
        </tr>
    </table>
</div>
@endsection
