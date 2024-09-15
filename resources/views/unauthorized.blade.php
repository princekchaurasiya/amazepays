@extends('layouts.app')

@section('title', 'Unauthorized Access')

@section('content')
    <div class="container mt-5">
        <div class="alert alert-warning">
            <h4 class="alert-heading">Oops!</h4>
            You have logged out or your session has expired. Please login to continue.
        </div>
    </div>

    @push('scripts')
        <script type="text/javascript">

        </script>
    @endpush
@endsection
