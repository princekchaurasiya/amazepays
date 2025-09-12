<!-- resources/views/refund-policy.blade.php -->

@extends('layouts.app')
@section('title')
    Amazepay | Refund Policy
@endsection
@section('content')
    <div class="faq-wrapper pt-4 pb-0">
        <div class="container mt-5">
            <h1 class="text-center"><b>Refund Policy</b></h1>

            <div class="mt-4">
                <h2>A. General Policy</h2>
                <p class="text-grey-900">All gift vouchers purchased through our platform are delivered digitally and are non-refundable. Once a gift voucher has been issued, we are unable to accept returns or exchanges. This policy is in place to ensure the validity of the gift voucher and protect against fraudulent activity.</p>
            </div>

            <div class="mt-4">
                <h2>B. Non-Refundable Items</h2>
                <p class="text-grey-900">Once a gift voucher is sent to your email, it is considered non-refundable. We cannot guarantee the validity of the code once it leaves our secure inventory. Please be cautious when selecting your voucher.
                    <br/>
                    If voucher is not generated, capture a screenshot of the error and send it to the support team at <a href="mailto:{{ env('COMPANY_EMAIL') }}">{{ env('COMPANY_EMAIL') }}</a> for assistance.
                    In this case of refund,it may take up to 7 working days for the amount to be credited to your account.
                </p>
            </div>

            <div class="mt-4">
                <h2>C. Delivery and Verification</h2>
                <p class="text-grey-900">Most orders are fulfilled within 15-20 minutes of purchase. However, new customers may experience a slight delay due to account verification. If you do not receive your voucher promptly, it may be due to a verification step required to protect against fraud. You may have received instructions for completing verification.</p>
                <p class="text-grey-900">If you have already verified your account and still have not received your voucher, please contact us at <a href="mailto:{{ env('COMPANY_EMAIL') }}">{{ env('COMPANY_EMAIL') }}</a> or call us at <a href="tel:{{ env('COMPANY_CONTACT_NO') }}">{{ env('COMPANY_CONTACT_NO') }}</a> for assistance.</p>
            </div>

            <div class="mt-4">
                <h2>D. Technical Issues</h2>
                <p class="text-grey-900">If you encounter problems with your gift voucher, please:</p>
                <ul>
                    <li>Verify that you are entering the code correctly.</li>
                    <li>Ensure you are redeeming the voucher in the correct place.</li>
                    <li>Check your account balance by logging out and back in.</li>
                </ul>
                <p class="text-grey-900">For any issues, please send us a screenshot of the error at <a href="mailto:{{ env('COMPANY_EMAIL') }}">{{ env('COMPANY_EMAIL') }}</a>, and we will assist you as quickly as possible.</p>
            </div>

            <div class="mt-4">
                <h2>E. Country Restrictions</h2>
                <p class="text-grey-900">Our gift vouchers are Indian gift vouchers and may be region-locked. If you are unsure, please contact us or the gift voucher’s support team before purchase. We cannot process refunds or exchanges once the voucher code has been delivered.</p>
            </div>

            <div class="mt-4">
                <h2>F. Contact Us</h2>
                <p class="text-grey-900">For any assistance, including verification issues or technical support, please reach out to our customer support team at <a href="mailto:{{ env('COMPANY_EMAIL') }}">{{ env('COMPANY_EMAIL') }}</a> or call us at <a href="tel:{{ env('COMPANY_CONTACT_NO') }}">{{ env('COMPANY_CONTACT_NO') }}</a>. Our team is available to help and ensure a smooth experience.</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@endpush
