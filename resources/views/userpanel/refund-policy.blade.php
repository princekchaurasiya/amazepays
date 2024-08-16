@extends('layouts.app')
@section('title')
    Amazepay | Refund Policy
@endsection
@section('content')
    <div class="faq-wrapper pt-4 pb-0">
        <div class="container mt-5">
            <h1 class="text-center"><b>Refund Policy</b></h1>

            <div class="mt-4">
                <h2>A. General Refund Policy</h2>
                <p>At Amazepay, we are committed to ensuring that your experience with our digital products is smooth and hassle-free. In the event that you have accidentally purchased the wrong e-gift card or encountered an issue with your order, we offer a refund policy under specific conditions. Please review the following guidelines to understand the process.</p>
            </div>

            <div class="mt-4">
                <h2>B. Eligibility for Refunds</h2>
                <p>To be eligible for a refund, the following criteria must be met:</p>
                <ul>
                    <li>The refund request must be made within 7 days of the purchase date.</li>
                    <li>The e-gift card must not have been redeemed or used.</li>
                    <li>You must provide a receipt or proof of purchase.</li>
                </ul>
            </div>

            <div class="mt-4">
                <h2>C. Refund Process</h2>
                <p>To initiate a refund, please follow these steps:</p>
                <ul>
                    <li>Contact our customer support team via email at <a href="mailto:{{ config('companyDefaultValues.company_email') }}">{{ config('companyDefaultValues.company_email') }}</a> or call us at <a href="tel:{{ config('companyDefaultValues.company_contact_no') }}">{{ config('companyDefaultValues.company_contact_no') }}</a> to request a refund.</li>
                    <li>Provide your order number and the reason for the refund request.</li>
                </ul>
                <p>Once we verify that the e-gift card has not been used or redeemed, we will process your refund. Please note that it may take up to 7 business days for the refund to appear on your original method of payment.</p>
            </div>


            <div class="mt-4">
                <h2>D. Non-Refundable Items</h2>
                <p>Some items are non-refundable, including: E-gift cards that have been redeemed or partially used.</p>
            </div>

            <div class="mt-4">
                <h2>E. Exchanges</h2>
                <p>We do not offer exchanges for e-gift cards. If you wish to cancel an unused e-gift card, please request a refund and purchase a new one.</p>
            </div>

            <div class="mt-4 mb-4">
                <h2>F. Contact Us</h2>
                <p>If you have any questions or concerns regarding our refund policy, please contact us at:</p>
                <ul>
                    <li>Email: <a href="mailto:{{ config('companyDefaultValues.company_email') }}" class="font-weight-bold text-dark">{{ config('companyDefaultValues.company_email') }}</a></li>
                    <li>Phone: <a href="tel:{{ config('companyDefaultValues.company_contact_no') }}" class="font-weight-bold text-dark">{{ config('companyDefaultValues.company_contact_no') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@endpush
