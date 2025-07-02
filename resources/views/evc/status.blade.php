@extends('layouts.app')

@section('content')
<div class="container">
    <h2>EVC Status Details</h2>

    <table class="table table-bordered">
        <tr>
            <th>Order ID</th>
            <td>{{ $status->order_id }}</td>
        </tr>
        <tr>
            <th>Request Ref No</th>
            <td>{{ $status->request_ref_no }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $status->status }}</td>
        </tr>
        <tr>
            <th>Details</th>
            <td>
                <pre>{{ json_encode($status->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </td>
        </tr>
        <tr>
            <th>Checked At</th>
            <td>{{ $status->created_at->format('d M Y h:i A') }}</td>
        </tr>
    </table>
</div>
@endsection
