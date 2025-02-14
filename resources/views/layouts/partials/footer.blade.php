<div class="footer-wrapper mt-0">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-12 col-lg-4 col-sm-9 col-xs-12 md-mb25">
                        <!-- <a href="index.html" class="logo"><img src="images/logo.png" alt="logo"></a> -->
                        <a href="/" class="logo"><img src="{{ asset('images/logo.png') }}"
                                alt="logo" class="custLogo"></a>

                        <p class="w-100 mt-4 text-black">
                            <strong>Company Name :</strong> {{ env('COMPANY_NAME') }}

                            {{-- <a
                                href="{{ env('COMPANY_NEW_WEBSITE_LINK_ABOUT_US') }}"
                                target="_blank">{{ env('COMPANY_NAME') }}</a> --}}
                                <br />


                            <strong>CIN :</strong> {{ config('companyDefaultValues.company_cin') }}<br />
                            {{ config('companyDefaultValues.company_address') }}
                        </p>

                    </div>
                    {{-- <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                        <h5>Channel</h5>
                        <ul>
                            <li><a href="#">Gift Cards</a></li>
                            <li><a href="#">bank Cards</a></li>
                            <li><a href="#">Vouchers</a></li>
                        </ul>
                    </div> --}}
                    <div class="col-md-3 col-lg-2 col-sm-3 col-xs-6">
                        <h5 class="text-orange">Quick Read</h5>
                        <ul>
                            <li><a class="font-xsss text-black" href="{{ url('terms-of-use') }}">Term of
                                    use</a></li>
                            <li><a class="font-xsss text-black" href="{{ url('privacy-policy') }}">Privacy
                                    Policy</a></li>

                            <li><a class="font-xsss text-black" href="{{ url('refund-policy') }}">Refund
                                    Policy</a></li>

                                    {{-- <li><a class="font-xsss text-black" href="{{ route('faq') }}">Frequently Asked Questions</a></li> --}}


                            <li><a class="font-xsss text-black"
                                    href="{{ config('companyDefaultValues.company_new_website_link') }}">Who
                                    we are?</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3 col-lg-2 col-sm-3 col-xs-6">
                        <h5 class="text-orange">Easy Guide</h5>
                        <ul>
                            <li><a class="font-xsss text-black" href="{{ url('/') }}">Home</a></li>
                            <li><a class="font-xsss text-black" href="{{ url('about') }}">About</a></li>
                            <li><a class="font-xsss text-black" href="{{ url('contact-us') }}">Contact Us</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-3 col-lg-3 col-sm-3 col-xs-6 md-mb25">
                        <h5 class="mb-3 text-orange">Contact us on</h5>
                        <ul class="list-inline">
                            <li class="list-inline-item mr-3"><a href="{{ env('FACEBOOK_URL') }}" class="ti-facebook-color "><i
                                        class="ti-facebook font-md "></i></a>
                            </li>
                            {{-- <li class="list-inline-item mr-3"><a href="#"><i
                                        class="ti-twitter-alt font-md"></i></a>
                            </li> --}}
                            <li class="list-inline-item mr-3"><a href="{{ env('LINKEDIN_URL') }}" class="ti-linkedin-color"><i
                                        class="ti-linkedin font-md"></i></a>
                            </li>
                            <li class="list-inline-item"><a href="{{ env('INSTAGRAM_URL') }}" class="ti-instagram-color"><i class="ti-instagram font-md"></i></a>
                            </li>
                        </ul>
                        <ul class="mt-3">

                            <li>
                                <a class="text-black"
                                    href="mailto:{{ config('companyDefaultValues.company_email') }}">
                                    <i class="fas fa-envelope"></i>
                                    {{ config('companyDefaultValues.company_email') }}
                                </a>
                            </li>


                            <li>
                                <a class="text-black"
                                    href="tel:{{ str_replace(' ', '', config('companyDefaultValues.company_contact_no')) }}">
                                    <i class="fas fa-phone-alt"></i> +91
                                    {{ config('companyDefaultValues.company_contact_no') }}
                                </a>
                            </li>

                        </ul>
                    </div>
                </div>
                <div class="middle-footer mt-5 pt-4"></div>
            </div>
            <div class="col-sm-12 lower-footer pt-0"></div>
            <div class="col-sm-6 col-xs-12">
                <p class="copyright-text">© {{ date('Y') }} copyright. All rights reserved.</p>
            </div>
            <div class="col-sm-6 col-xs-12 text-right">
                <p class="copyright-text float-right">Design & Develop by <a href="https://toutle.in/"
                        class="">Toutle</a>
                </p>
            </div>
        </div>
    </div>
</div>
