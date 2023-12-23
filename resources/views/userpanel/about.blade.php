@extends('layouts.app')
@section('title')
    Amazepay | About
@endsection
@section('content')


<div class="about-wrapper pb-7 pt-7">
        	<div class="container">
        		<div class="row">
        			<div class="col-lg-12">
        				<h2 class="display3-size fw-300 open-font lh-2 mt-0">Welcome to Amazepays, your one-stop destination for gift vouchers and digital gift cards!</h2>
        			</div>
        			<div class="col-lg-12 mt-5">
						<h4 class="text-grey-900 fw-600 font-xl ls-2">WHAT WE DO</h4>
						<h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4"> At Amazepays, we believe in making gifting a hassle-free and delightful experience. We are a leading gift voucher provider, offering a wide range of gift vouchers and digital gift cards from various popular brands across the globe. Our mission is to help you find the perfect gift for your loved ones, no matter what the occasion.
						</h4><h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4">
						We understand the importance of making a good impression with a gift, and that's why we strive to provide a seamless and user-friendly platform for our customers to choose and purchase gift vouchers. Our team of experts is dedicated to curating the best gift options for you, so you can find the perfect gift for any occasion, be it a birthday, anniversary, wedding, or corporate gifting.
						</h4><h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4">
						At Amazepays, we value customer satisfaction above all else, and we go above and beyond to ensure that our customers have an enjoyable and memorable gifting experience. Our user-friendly website, secure payment gateways, and prompt delivery services make gifting with us a breeze.
						</h4><h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4">
						So, whether you're looking for a gift for a special someone, or a corporate gift for your valued clients or employees, we've got you covered. Browse through our extensive range of gift vouchers and digital gift cards today, and give the gift of choice with Amazepays!
						</h4>
					</div>
        			{{-- <div class="col-lg-12 mt-5"><img src="{{URL::asset('/images/about.png')}}" alt="about" class="img-fluid"></div> --}}
        			<div class="col-lg-12 mt-5 text-center pt-4">
        				{{-- <a href="#" class="ml-1 mr-1 rounded-lg text-primary font-xss border-size-md border-primary fw-600 open-font p-3 w200 btn mb-3 mt-3">Learn More</a> --}}
        				{{-- <h3 class="font-xss fw-600 text-grey-500 p-3 d-inline-block d-none-sm">or</h3> --}}
        				<a href="{{ url('contact_us') }}" class="ml-1 mr-1 rounded-lg alert-primary text-primary font-xss border-size-md border-0 fw-600 open-font p-3 w200 btn">Contact Us</a>
        			</div>
        		</div>
        	</div>
        </div>

        {{-- <div class="service-wrapper bg-greyblue pb-0 pt-7">
        	<div class="container">
        		<div class="row">
        			<div class="col-lg-6 text-center mb-5">
						<i class="fa-solid fa-money-bill-transfer text-white btn-round-xxl bg-success d-inline-block font-xl"></i>
        				<h4 class="text-grey-900 fw-700 font-sm open-font mb-3 mt-4">Domestic Money Transfer</h4>
        				<p class="font-xsss fw-500 text-grey-500 lh-26 mt-0 mb-0 pl-5 pr-5">A. Direct Money Transfer (DMT) is a unique product that can be used to send money instantly to any Bank's account holder within India. The cash to account fund transfers will now be made easy with IPPB DMT services.</p>
        			</div>

        			<div class="col-lg-6 text-center mb-5">
						<i class="fa-solid fa-indian-rupee-sign text-white btn-round-xxl bg-secondary d-inline-block font-xl"></i>
        				<h4 class="text-grey-900 fw-700 font-sm open-font mb-3 mt-4">Bill Payment</h4>
        				<p class="font-xsss fw-500 text-grey-500 lh-26 mt-0 mb-0 pl-5 pr-5">Bill payment is a facility provided to the customer to make their utility payments online through digital banking.</p>
        			</div>

        			<div class="col-lg-6 text-center mb-5">
						<i class="fa-solid fa-mobile-screen text-white btn-round-xxl bg-warning d-inline-block font-xl"></i>
        				<h4 class="text-grey-900 fw-700 font-sm open-font mb-3 mt-4">Multiple Recharge</h4>
        				<p class="font-xsss fw-500 text-grey-500 lh-26 mt-0 mb-0 pl-5 pr-5">Recharge any operator at one platform available at all retails.</p>
        			</div>

        			<div class="col-lg-6 text-center mb-5">
						<i class="fa-solid fa-receipt text-white btn-round-xxl bg-danger d-inline-block font-xl"></i>
        				<h4 class="text-grey-900 fw-700 font-sm open-font mb-3 mt-4">Aadhar Pay</h4>
        				<p class="font-xsss fw-500 text-grey-500 lh-26 mt-0 mb-0 pl-5 pr-5">Easily get your money through biometric process instantly anywhere in India.</p>
        			</div>
        		</div>
        	</div>
        </div> --}}

        {{-- <div class="team-wrapper bg-greyblue pb-7 pt-7">
        	<div class="container">
        		<div class="row">
        			<!-- <div class="col-lg-12 text-center mb-2 pb-3">
                        <h2 class="text-grey-900 lh-3 fw-400 display1-size">Our Team</h2>
                        <p class="font-xsss text-grey-500">Our Customers love what we do</p>
                    </div> -->
                    <!-- <div class="col-lg-4 mb-3">
                    	<div class="card w-100 team-div p-0 shadow-lg border-0 rounded-lg overflow-hidden">
                    		<div class="card-image w-100 p-0">
                    			<img src="https://via.placeholder.com/300x300.png" alt="team" class="w-100">
                    		</div>
                    		<div class="card-body w-100 p-5 text-center">
                    			<h4 class="text-grey-900 fw-700 font-sm open-font">Bessie Cooper</h4>
                    			<h5 class="text-grey-500 font-xsss mb-4">Project Manager</h5>
                    			<p class="font-xsss fw-500 text-grey-500 lh-26 mt-2 mb-0">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                    			<ul class="list-inline mt-4 mb-0">
	                                <li class="list-inline-item mr-3"><a href="#"><i class="font-xs text-grey-500 ti-dribbble"></i></a></li>
	                                <li class="list-inline-item mr-3"><a href="#"><i class="font-xs text-grey-500 ti-skype"></i></a></li>
	                                <li class="list-inline-item"><a href="#"><i class="font-xs text-grey-500 ti-instagram"></i></a></li>
	                            </ul>
                    		</div>
                    	</div>
                    </div>

                    <div class="col-lg-4 mb-3">
                    	<div class="card w-100 team-div p-0 shadow-lg border-0 rounded-lg overflow-hidden">
                    		<div class="card-image w-100 p-0">
                    			<img src="https://via.placeholder.com/300x300.png" alt="team" class="w-100">
                    		</div>
                    		<div class="card-body w-100 p-5 text-center">
                    			<h4 class="text-grey-900 fw-700 font-sm open-font">Jocab Murphy</h4>
                    			<h5 class="text-grey-500 font-xsss mb-4">Project Manager</h5>
                    			<p class="font-xsss fw-500 text-grey-500 lh-26 mt-2 mb-0">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                    			<ul class="list-inline mt-4 mb-0">
	                                <li class="list-inline-item mr-3"><a href="#"><i class="font-xs text-grey-500 ti-dribbble"></i></a></li>
	                                <li class="list-inline-item mr-3"><a href="#"><i class="font-xs text-grey-500 ti-skype"></i></a></li>
	                                <li class="list-inline-item"><a href="#"><i class="font-xs text-grey-500 ti-instagram"></i></a></li>
	                            </ul>
                    		</div>
                    	</div>
                    </div> -->

                    <!-- <div class="col-lg-4 mb-3">
                    	<div class="card w-100 team-div p-0 shadow-lg border-0 rounded-lg overflow-hidden">
                    		<div class="card-image w-100 p-0">
                    			<img src="https://via.placeholder.com/300x300.png" alt="team" class="w-100">
                    		</div>
                    		<div class="card-body w-100 p-5 text-center">
                    			<h4 class="text-grey-900 fw-700 font-sm open-font">Esther Howard</h4>
                    			<h5 class="text-grey-500 font-xsss mb-4">Project Manager</h5>
                    			<p class="font-xsss fw-500 text-grey-500 lh-26 mt-2 mb-0">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                    			<ul class="list-inline mt-4 mb-0">
	                                <li class="list-inline-item mr-3"><a href="#"><i class="font-xs text-grey-500 ti-dribbble"></i></a></li>
	                                <li class="list-inline-item mr-3"><a href="#"><i class="font-xs text-grey-500 ti-skype"></i></a></li>
	                                <li class="list-inline-item"><a href="#"><i class="font-xs text-grey-500 ti-instagram"></i></a></li>
	                            </ul>
                    		</div>
                    	</div>
                    </div> -->


        		</div>
        	</div>
        </div> --}}

@endsection

@push('scripts')

@endpush
