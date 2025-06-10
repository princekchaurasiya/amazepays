@extends('layouts.app')

@section('title')
    Amazepay | Self gift
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
                        <h5 class="mb-0">Activity not supported</h5>
                    </div>
                    
                    @if(isset($blockType) && $blockType == 'recipient')
                        <div class="alert alert-warning py-2 mb-3">
                            <p class="mb-0">You are trying to send a gift to self</p>
                            @if(isset($phone))
                                <p class="mb-0 mt-1"><strong>Recipient Phone:</strong> {{ $phone }}</p>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-3">
                            <p class="mb-0"></p>
                        </div>
                    @endif
                    
            
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