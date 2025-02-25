@extends('layouts.app')

@section('content')
<div class="faq-wrapper p-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center mt-5">Frequently Asked Questions</h1>
            <hr class="normalhr">
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">What is Frenetic India Services Pvt Ltd gift card platform?</h3>
                <p class="text-black">Frenetic India Services Pvt Ltd provides a seamless corporate gifting platform for businesses and individuals to purchase and manage digital gift cards across various brands.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">How do I purchase a gift card on Frenetic India Services?</h3>
                <p class="text-black">Customers can log in to the platform, browse available brands, select a gift card value, and proceed with the purchase using the provided payment options.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">How can I redeem my gift card?</h3>
                <p class="text-black">Gift cards can be redeemed either online or in-store, subject to the brand’s redemption policy. Each gift card comes with specific instructions outlined in the terms and conditions, which should be reviewed carefully before use.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">What happens if my gift card expires?</h3>
                <p class="text-black">Each gift card has a set validity period mentioned at the time of purchase. Once it expires, it cannot be reactivated, extended, or refunded.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">How do I check my gift card balance?</h3>
                <p class="text-black">You can check your gift card balance by visiting the respective brand’s website or contacting their customer support.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">What should I do if my gift card is not working?</h3>
                <p class="text-black">If you are facing issues with redemption, please contact our support team at <a href="mailto:{{ env('CONTACT_US_ADMIN_EMAIL') }}">{{ env('CONTACT_US_ADMIN_EMAIL') }}</a> with your order details. We will assist you in resolving the issue.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">Are there any additional charges for purchasing a gift card?</h3>
                <p class="text-black">No, the price of the gift card is as per the selected value. However, GST or other applicable charges may be included as per regulatory guidelines.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">Can I transfer my gift card to someone else?</h3>
                <p class="text-black">Yes, you can share the gift card code with anyone. However, once the code is used, it cannot be transferred again.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">How long does it take to receive a gift card after purchase?</h3>
                <p class="text-black">Digital gift cards are typically delivered instantly upon successful payment. However, in rare cases, it may take a few hours due to verification processes.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">Can I cancel or return my gift card after purchase?</h3>
                <p class="text-black">No, once a gift card is purchased, it cannot be canceled, refunded, or exchanged as per our policy.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">What should I do if I haven't received my gift card?</h3>
                <p class="text-black">If you haven’t received your e-gift card within the expected time, please check your spam/junk folder. If you still haven’t received it, contact our support team at <a href="mailto:{{ env('CONTACT_US_ADMIN_EMAIL') }}">{{ env('CONTACT_US_ADMIN_EMAIL') }}</a> for assistance.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">The amount is debited from my account but the order is not successful. What should I do?</h3>
                <p class="text-black">If your payment has been deducted but the order was not successfully processed, please reach out to our support team at <a href="mailto:{{ env('CONTACT_US_ADMIN_EMAIL') }}">{{ env('CONTACT_US_ADMIN_EMAIL') }}</a> for assistance. Our team will review the issue, and the resolution process may take up to 72 hours. Please note that our support team operates from Monday to Saturday.</p>
            </div>
            <div class="faq-item mb-4">
                <h3 class="font-weight-bold">How can I personalize a gift card?</h3>
                <p class="text-black">To personalize a gift card, you will need to provide the recipient's details in the "Gifting Details" section. This will include entering a personalized message to make the gift more special.</p>
            </div>
        </div>
    </div>


</div>
@endsection
