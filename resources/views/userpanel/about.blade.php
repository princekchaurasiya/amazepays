@extends('layouts.app')

@section('title', 'Amazepay | About')

@section('content')
    <div class="about-wrapper pb-7 pt-7">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h2 class="font-xss lh-24 fw-500 text-grey-900 mt-4">
						Welcome to <a href="{{ config('companyDefaultValues.company_website') }}" target="_blank">Amazepays</a>.
						The website <a href="{{ config('companyDefaultValues.company_website') }}" target="_blank">{{ config('companyDefaultValues.company_website') }}</a> is owned and operated by <b>{{ config('companyDefaultValues.company_official_name') }}</b>
						with its registered office at <b>{{ config('companyDefaultValues.company_address') }}</b>
					</h2>

                </div>
                <div class="col-lg-12 mt-5">
                    <h4 class="text-grey-900 fw-600 font-xl ls-2">WHAT WE DO</h4>
                    <h4 class="font-xsss lh-24 fw-500 text-grey-900 mt-4"> At Amazepays, we believe in making gifting a
                        hassle-free and delightful experience. We are a leading gift voucher provider, offering a wide range
                        of gift vouchers and digital gift cards from various popular brands across the globe. Our mission is
                        to help you find the perfect gift for your loved ones, no matter what the occasion.
                    </h4>
                    <h4 class="font-xsss lh-24 fw-500 text-grey-900 mt-4">
                        We understand the importance of making a good impression with a gift, and that's why we strive to
                        provide a seamless and user-friendly platform for our customers to choose and purchase gift
                        vouchers. Our team of experts is dedicated to curating the best gift options for you, so you can
                        find the perfect gift for any occasion, be it a birthday, anniversary, wedding, or corporate
                        gifting.
                    </h4>
                    <h4 class="font-xsss lh-24 fw-500 text-grey-900 mt-4">
                        At Amazepays, we value customer satisfaction above all else, and we go above and beyond to ensure
                        that our customers have an enjoyable and memorable gifting experience. Our user-friendly website,
                        secure payment gateways, and prompt delivery services make gifting with us a breeze.
                    </h4>
                    <h4 class="font-xsss lh-24 fw-500 text-grey-900 mt-4">
                        So, whether you're looking for a gift for a special someone, or a corporate gift for your valued
                        clients or employees, we've got you covered. Browse through our extensive range of gift vouchers and
                        digital gift cards today, and give the gift of choice with Amazepays!
                    </h4>
                </div>
                <div class="col-lg-12 mt-5 text-center pt-4">
                    <a href="{{ url('contact-us') }}"
                        class="ml-1 mr-1 rounded-lg alert-primary text-primary font-xss border-size-md border-0 fw-600 open-font p-3 w200 btn">Contact
                        Us</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Additional scripts if needed --}}
@endpush
