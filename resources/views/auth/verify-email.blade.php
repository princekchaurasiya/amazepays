@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Verify Your Email</h4>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle"></i> Please fix the following errors:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="text-center mb-4">
                        <i class="fa fa-envelope fa-3x text-primary mb-3"></i>
                        <p class="text-muted">We've sent a verification code to your email address. Please enter it below to verify your account.</p>
                    </div>

                    <form method="POST" action="{{ route('verify.email') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" 
                                   name="email" 
                                   id="email"
                                   class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                   placeholder="Enter your email address" 
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label for="code" class="form-label">Verification Code</label>
                            <input type="text" 
                                   name="code" 
                                   id="code"
                                   class="form-control form-control-lg @error('code') is-invalid @enderror" 
                                   placeholder="Enter 6-digit verification code" 
                                   value="{{ old('code') }}"
                                   maxlength="6"
                                   required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-check"></i> Verify Email
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-2">Didn't receive the code?</p>
                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="resendCodeBtn">
                            <i class="fa fa-refresh"></i> Resend Code
                        </button>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.form-control {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-primary {
    border-radius: 10px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
}

.alert {
    border-radius: 10px;
    border: none;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resendBtn = document.getElementById('resendCodeBtn');
            const emailInput = document.getElementById('email');
            
            resendBtn.addEventListener('click', function() {
                const email = emailInput.value.trim();
                
                if (!email) {
                    alert('Please enter your email address first.');
                    emailInput.focus();
                    return;
                }
                
                // Disable button and show loading state
                resendBtn.disabled = true;
                resendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
                
                // Send AJAX request
                fetch('{{ route("resend.verification") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ email: email })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <i class="fa fa-check-circle"></i> ${data.message}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        
                        // Insert alert before the form
                        const form = document.querySelector('form');
                        form.parentNode.insertBefore(alertDiv, form);
                        
                        // Auto-remove alert after 5 seconds
                        setTimeout(() => {
                            if (alertDiv.parentNode) {
                                alertDiv.remove();
                            }
                        }, 5000);
                        
                        // Start countdown for resend button
                        let countdown = 60;
                        const countdownInterval = setInterval(() => {
                            if (countdown > 0) {
                                resendBtn.innerHTML = `<i class="fa fa-clock-o"></i> Resend in ${countdown}s`;
                                countdown--;
                            } else {
                                clearInterval(countdownInterval);
                                resendBtn.disabled = false;
                                resendBtn.innerHTML = '<i class="fa fa-refresh"></i> Resend Code';
                            }
                        }, 1000);
                        
                    } else {
                        // Show error message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <i class="fa fa-exclamation-circle"></i> ${data.message}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        
                        const form = document.querySelector('form');
                        form.parentNode.insertBefore(alertDiv, form);
                        
                        // Re-enable button
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = '<i class="fa fa-refresh"></i> Resend Code';
                        
                        // Auto-remove alert after 5 seconds
                        setTimeout(() => {
                            if (alertDiv.parentNode) {
                                alertDiv.remove();
                            }
                        }, 5000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show generic error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fa fa-exclamation-circle"></i> An error occurred. Please try again.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    `;
                    
                    const form = document.querySelector('form');
                    form.parentNode.insertBefore(alertDiv, form);
                    
                    // Re-enable button
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '<i class="fa fa-refresh"></i> Resend Code';
                    
                    // Auto-remove alert after 5 seconds
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.remove();
                        }
                    }, 5000);
                });
            });
        });
    </script>
@endsection