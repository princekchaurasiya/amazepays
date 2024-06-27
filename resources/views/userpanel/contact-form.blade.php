@extends('layouts.app')
@section('title')
    Amazepay | Contact
@endsection
@section('content')
    <div class="section">
        <div id="map" class="rounded-lg overflow-hidden" style="height: 150px;"></div>
    </div>

    <div class="map-wrapper pb-7">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 offset-lg-1">
                    <div class="contact-wrap bg-white shadow-lg rounded-lg position-relative">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        <h1 class="text-grey-900 fw-700 display3-size mb-5 lh-1">Contact us</h1>
                        <form action="{{ route('save-contact') }}" method="POST" id="contactForm">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group mb-3">
                                        <input type="text" name="name" class="form-control h60 bg-color-none text-grey-700"
                                            placeholder="Name">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group mb-3">
                                        <input type="email" name="email" class="form-control h60 bg-color-none text-grey-700"
                                            placeholder="Email">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-3 md-mb25">
                                        <textarea class="w-100 h125 p-3 form-control" name="message" placeholder="Message"></textarea>
                                    </div>
                                    <div class="form-check text-left mt-3 float-left md-mb25">
                                        <input type="checkbox" class="form-check-input mt-2" id="exampleCheck1">
                                        <label class="form-check-label font-xsss text-grey-500 fw-500"
                                            for="exampleCheck1">I agree to the term of this <a href="#"
                                                class="text-grey-600 fw-600">Privacy Policy</a></label>
                                    </div>
                                    <button type="submit"
                                        class="form-control rounded-lg h60 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w175">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-12 offset-lg-1 col-xl-12 offset-xl-1">
                    <div class="row">
                        <div class="col-lg-4 col-md-4 md-mb25">
                            <h4 class="text-grey-900 fw-600 font-xl ls-2">Address</h4>
                            <h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4">98-103, 4 Floor, Aditya Industrial Estate
                                Co-op Premises Ltd Mindspace Behind Evershine
                                Mall Off Link Road Malad (West) <br />Mumbai, Maharashtra 400064</h4>
                        </div>
                        <div class="col-lg-4 col-md-4 md-mb25">
                            <h4 class="text-grey-900 fw-600 font-xl ls-2">Email Us</h4>
                            <h5 class="font-xsss lh-24 fw-500 text-grey-500 mt-4 mb-0">support@amazepay.in</h5>
                        </div>
                        <div class="col-lg-4 col-md-4 md-mb25">
                            <h4 class="text-grey-900 fw-600 font-xl ls-2">Contact Us</h4>
                            <h5 class="font-xsss lh-24 fw-500 text-grey-500 mt-0">+91-98211 99497</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
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
                errorPlacement: function (error, element) {
                    // Display error message after the form element
                    error.insertAfter(element);
                },
                submitHandler: function (form) {
                    form.submit(); // Submit the form if validation is successful
                }
            });

            // Custom method to check for letters and spaces only
            $.validator.addMethod("letterswithspace", function(value, element) {
                return this.optional(element) || /^[a-zA-Z\s]*$/.test(value);
            }, "Please enter letters and spaces only");

            // Prevent form submission on button click (to allow validation to run)
            $('#contactForm button[type="submit"]').on('click', function (e) {
                e.preventDefault(); // Prevent default form submission
                $("#contactForm").submit(); // Trigger form validation and submission
            });
        });
    </script>
@endpush
