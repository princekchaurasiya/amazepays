@extends('layouts.app')
@section('title')
    {{ env('APP_NAME') }} | Contact
@endsection
@section('content')
    <div class="section">
        <div id="map" class="rounded-lg overflow-hidden" style="height: 150px;"></div>
    </div>

    <div class="map-wrapper pb-7">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 offset-lg-1">
                    <div class="row">
                        <div class="col-lg-5 align-self-center">
                            <h1 class="text-grey-900 fw-700 display3-size mb-5 lh-1">Contact Us</h1>
                            <p>Finding the perfect gift can be a challenge, especially when you're unsure of someone's preferences. Whether it’s a birthday, anniversary, or any special occasion, giving a physical gift isn’t always ideal. Why settle for cash when you can offer the freedom of choice with a digital e-gift card?</p>

                            <p>At {{ env('COMPANY_BRAND') }}, we specialize in digital e-gift cards from leading brands, making gift-giving simple and meaningful. Whether it's for a celebration like Diwali, Christmas, or just because, our e-gift cards are a thoughtful, versatile, and convenient solution.</p>

                            <p>From apparel and home decor to electronics, restaurants, and travel, our digital gift cards cover a wide range of categories. Plus, with instant delivery via email, your gift reaches the recipient instantly, no matter where they are.</p>

                            <p>For the perfect blend of personalization and convenience, choose a digital e-gift card from {{ env('COMPANY_BRAND') }} — the smart, hassle-free way to give the gift of choice!</p>
                        </div>

                        <div class="col-lg-7 align-self-center">

                            <div class="contact-wrap bg-white shadow-lg rounded-lg ">
                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('save-contact') }}" method="POST" id="contactForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12">
                                            <div class="form-group mb-3">
                                                <input type="text" name="name"
                                                    class="form-control h60 bg-color-none text-grey-700" placeholder="Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12">
                                            <div class="form-group mb-3">
                                                <input type="email" name="email"
                                                    class="form-control h60 bg-color-none text-grey-700"
                                                    placeholder="Email">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-3 md-mb25">
                                                <textarea class="w-100 h125 p-3 form-control" name="message" placeholder="Message"></textarea>
                                            </div>
                                            <button type="submit"
                                                class="form-control rounded-lg h60 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w175">Submit
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>



                        </div>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-lg-12 offset-lg-1 col-xl-12 offset-xl-1">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 md-mb25">
                                <h4 class="text-grey-900 fw-600 font-xl ls-2">Address</h4>
                                <h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4">
                                    {{ env('COMPANY_ADDRESS') }}
                                </h4>
                            </div>
                            <div class="col-lg-4 col-md-4 md-mb25">
                                <h4 class="text-grey-900 fw-600 font-xl ls-2">Email Us</h4>
                                <h5 class="font-xsss lh-24 fw-500 text-grey-500 mt-4 mb-0">
                                    {{ env('COMPANY_EMAIL') }}
                                </h5>
                            </div>
                            <div class="col-lg-4 col-md-4 md-mb25">
                                <h4 class="text-grey-900 fw-600 font-xl ls-2">Contact Us</h4>
                                <h5 class="font-xsss lh-24 fw-500 text-grey-500 mt-0">
                                    +91 {{ env('COMPANY_CONTACT_NO') }}
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $("#contactForm").validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 50,
                        letterswithspace: true // Custom method to check for letters and spaces only
                    },
                    email: {
                        required: true,
                        email: true // This rule will use the built-in email validation
                    },
                    message: {
                        required: true,
                        minlength: 10,
                        maxlength: 70
                    }
                },
                messages: {
                    name: {
                        required: "Please enter your name",
                        maxlength: "Your name must not exceed 50 characters",
                        letterswithspace: "Please enter letters and spaces only"
                    },
                    email: {
                        required: "Please enter your email",
                        email: "Please enter a valid email address"
                    },
                    message: {
                        required: "Please enter your message",
                        minlength: "Your message must be at least 10 characters long",
                        maxlength: "Your message should not exceed 70 characters long"
                    }
                },
                errorPlacement: function(error, element) {
                    // Display error message after the form element
                    error.insertAfter(element);
                },
                submitHandler: function(form) {
                    form.submit(); // Submit the form if validation is successful
                }
            });

            // Custom method to check for letters and spaces only
            $.validator.addMethod("letterswithspace", function(value, element) {
                return this.optional(element) || /^[a-zA-Z\s]*$/.test(value);
            }, "Please enter letters and spaces only");

            // Prevent form submission on button click (to allow validation to run)
            $('#contactForm button[type="submit"]').on('click', function(e) {
                e.preventDefault(); // Prevent default form submission
                $("#contactForm").submit(); // Trigger form validation and submission
            });
        });
    </script>
@endpush
