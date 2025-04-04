@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <div id="login-error-msg" class="alert alert-danger" style="display: none;"></div>
                    <div id="login-contact-info" class="alert alert-info" style="display: none;">
                        <p class="contact-message mb-2"></p>
                        <div class="contact-details">
                            <p class="mb-1"><strong>Email:</strong> <span class="contact-email"></span></p>
                            <p class="mb-1"><strong>Phone:</strong> <span class="contact-phone"></span></p>
                            <p class="mb-1"><strong>Hours:</strong> <span class="contact-hours"></span></p>
                        </div>
                    </div>
                    
                    <form id="loginForm" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="mobile">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" required 
                                   placeholder="Enter your mobile number">
                            <div class="invalid-feedback" id="mobile-error"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   placeholder="Enter your password">
                            <div class="invalid-feedback" id="password-error"></div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Register here</a></p>
                        <p><a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Reset error messages
        $('.invalid-feedback').hide();
        $('#login-error-msg').hide();
        $('#login-contact-info').hide();
        
        $.ajax({
            url: "{{ route('user-login') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mobile: $('#mobile').val(),
                password: $('#password').val()
            },
            success: function(response) {
                if (response.status === 200) {
                    // Redirect on successful login
                    window.location.href = response.redirect || "{{ route('home') }}";
                } else if (response.status === 403) {
                    // Handle blocked user with contact information
                    $('#login-error-msg').text(response.msg).show();
                    
                    if (response.contact_info) {
                        $('.contact-email').text(response.contact_info.email);
                        $('.contact-phone').text(response.contact_info.phone);
                        $('.contact-hours').text(response.contact_info.hours);
                        $('#login-contact-info').show();
                    }
                } else {
                    // Show error message
                    $('#login-error-msg').text(response.msg).show();
                }
            },
            error: function(xhr) {
                $('#login-error-msg').text('An error occurred. Please try again.').show();
            }
        });
    });
});
</script>
@endpush
@endsection 