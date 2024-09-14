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
            $(document).ready(function() {
                @if (session('login_required'))
                    // Automatically trigger the login modal on page load


                    // Handle redirection after login
                    $('#Modallogin').on('hidden.bs.modal', function () {
                        let redirectUrl = "{{ session('intended_url') ? session('intended_url') : route('home') }}";
                        window.location.href = redirectUrl;
                    });

                    @php
                        session()->forget('login_required');
                        session()->forget('intended_url');
                    @endphp
                @endif
            });
        </script>
    @endpush
@endsection
