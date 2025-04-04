@extends('layouts.app')

@section('title')
    Amazepay | User Blocked
@endsection

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 text-center text-white">Access Restricted</h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-ban fa-3x text-danger"></i>
                    </div>
                    
                    <div class="alert alert-danger text-center py-2 mb-3">
                        <h5 class="mb-0">User Blocked</h5>
                    </div>
                    
                    @if(isset($blockType) && $blockType == 'recipient')
                        <div class="alert alert-warning py-2 mb-3">
                            <p class="mb-0">The recipient you are trying to send a gift to has been blocked from receiving gifts.</p>
                            @if(isset($phone))
                                <p class="mb-0 mt-1"><strong>Recipient Phone:</strong> {{ $phone }}</p>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-3">
                            <p class="mb-0">Your account has been blocked from sending gifts.</p>
                        </div>
                    @endif
                    
                    <div class="bg-light p-3 rounded mb-3">
                        <p class="mb-2"><strong>Possible reasons:</strong></p>
                        <ul class="mb-0 pl-3">
                            <li>Account suspension</li>
                            <li>Terms of service violation</li>
                            <li>User requested block</li>
                        </ul>
                    </div>
                    
                    <div class="bg-light p-3 rounded mb-3">
                        <p class="mb-1"><strong>Contact Support:</strong></p>
                        <p class="mb-1"><i class="fas fa-envelope mr-1"></i> {{ config('companyDefaultValues.company_email') }}</p>
                        <p class="mb-0"><i class="fas fa-phone mr-1"></i> +91 {{ config('companyDefaultValues.company_contact_no') }}</p>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('home') }}" class="btn btn-primary px-4">Return to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 