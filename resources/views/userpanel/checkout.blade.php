@extends('app')
@section('title')
    Gift & Giggles
@endsection
@section('content')
        <div class="faq-wrapper pt-4 pb-0">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center mb-lg-5 mb-4 pb-3">
                        <h2 class="text-grey-900 fw-400 display1-size">Checkout</h2>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-7">
                    <div class="table-content table-responsive mb-5 card border-0 bg-greyblue p-5">
                            <div class="page-title">
                                <div class="row">
                                <div class="col-md-6 col-sm-4 col-xs-6"><h4 class="mont-font fw-500 font-xxl mb-5">Sender Details</h4></div>
                                <div class="col-md-6 col-sm-4 col-xs-6"><a href="#" class="float-right">Edit</a></div>
                                </div>
                                <form action="#">
                                    <div class="row">
                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">First Name</label>
                                                <input type="text" name="comment-name" class="form-control" value="{{\Auth::user()->name }}" readonly>  
                                            </div>        
                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">Email</label>
                                                <input type="text" name="comment-name" class="form-control" value="{{\Auth::user()->email }}" readonly>
                                            </div>        
                                        </div>
                                        
                                    </div>

                                    <div class="row">

                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">Phone</label>
                                                <input type="text" name="comment-name" class="form-control" readonly>
                                            </div>        
                                        </div>

                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">Postcode</label>
                                                <input type="text" name="comment-name" class="form-control">
                                            </div>        
                                        </div>

                                    </div>

                                    <div class="row">
                                    
                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">Address 1</label>
                                                <input type="text" name="comment-name" class="form-control">
                                            </div>        
                                        </div>

                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">Address 2</label>
                                                <input type="text" name="comment-name" class="form-control">
                                            </div>        
                                        </div>
                                    </div>

                                    <div class="row">
                                        
                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">Twon / City</label>
                                                <input type="text" name="comment-name" class="form-control" readonly>
                                            </div>        
                                        </div>

                                        <div class="col-lg-6 mb-3">
                                            <div class="form-gorup">
                                                <label class="mont-font fw-500 font-xsss" for="comment-name">State</label>
                                                <input type="text" name="comment-name" class="form-control" readonly>
                                            </div>        
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="table-content table-responsive mb-5 card border-0 bg-greyblue p-5">
                            <h4 class="mont-font fw-500 font-xxl mb-5">Choose Payment Option</h4>
                            <div class="cc-selector-2 col-lg-12 col-sm-12">
                                <div class="row">
                                    <div class="col-md-3 col-sm-4 col-xs-6">
                                        <input  checked="checked" id="phone-pay" type="radio" name="creditcard" value="mastercard" />
                                        <label class="drinkcard-cc" for="phone-pay">
                                            <img src="{{URL::asset('images/phone_pay.png')}}" width="100px" height="70px">
                                        </label>
                                    </div>
                                    <div class="col-md-3 col-sm-4 col-xs-6">
                                        <input id="visa" type="radio" name="creditcard" value="visa"/>
                                        <label class="drinkcard-cc" for="visa">
                                            <img src="http://i.imgur.com/lXzJ1eB.png" width="100px" height="70px">
                                        </label>
                                    </div>
                                    <div class="col-md-3 col-sm-4 col-xs-6">
                                        <input id="upi" type="radio" name="creditcard" value="upi" />
                                        <label class="drinkcard-cc" for="upi">
                                            <img src="{{URL::asset('images/upi.png')}}" width="100px" height="70px">
                                        </label>
                                    </div>
                                    <div class="col-md-3 col-sm-4 col-xs-6">
                                        <input id="gpay" type="radio" name="creditcard" value="gpay"/>
                                        <label class="drinkcard-cc" for="gpay">
                                            <img src="{{URL::asset('images/gpay.png')}}" width="100px" height="70px">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="row justify-content-center">
                            <div class="col-xl-12">
                                <div id="accordion" class="accordion">
                                    <div class="card border-0 mb-4">
                                        <div class="card-header" id="headingTwo">
                                            <h5 class="mb-0">
                                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="" aria-expanded="true" aria-controls="collapseTwo">
                                                Choose Your Payment Option
                                            </button>
                                            </h5>
                                        </div>

                                        <div id="collapseTwo" class="" aria-labelledby="headingTwo" data-parent="#accordion">
                                            <div class="card-body">
                                                <div class="cc-selector-2 col-lg-12 col-sm-12">
                                                    <div class="row">
                                                        <div class="col-md-3 col-sm-4 col-xs-6">
                                                            <input  checked="checked" id="phone-pay" type="radio" name="creditcard" value="mastercard" />
                                                            <label class="drinkcard-cc" for="phone-pay">
                                                                <img src="{{URL::asset('images/phone_pay.png')}}" width="100px" height="70px">
                                                            </label>
                                                        </div>
                                                        <div class="col-md-3 col-sm-4 col-xs-6">
                                                            <input id="visa" type="radio" name="creditcard" value="visa"/>
                                                            <label class="drinkcard-cc" for="visa">
                                                                <img src="http://i.imgur.com/lXzJ1eB.png" width="100px" height="70px">
                                                            </label>
                                                        </div>
                                                        <div class="col-md-3 col-sm-4 col-xs-6">
                                                            <input id="upi" type="radio" name="creditcard" value="upi" />
                                                            <label class="drinkcard-cc" for="upi">
                                                                <img src="{{URL::asset('images/upi.png')}}" width="100px" height="70px">
                                                            </label>
                                                        </div>
                                                        <div class="col-md-3 col-sm-4 col-xs-6">
                                                            <input id="gpay" type="radio" name="creditcard" value="gpay"/>
                                                            <label class="drinkcard-cc" for="gpay">
                                                                <img src="{{URL::asset('images/gpay.png')}}" width="100px" height="70px">
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                               
                                                
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div> -->
                       
                       
                        <!-- <button class="bg-current border-0 w-50 float-right form-bttn fw-900 rounded-lg text-white"> Pay Now</button> -->
                    </div>
                    <div class="col-lg-5 cart-item">
                        <div class="row justify-content-center">
                            <div class="col-xl-12">
                                <div class="card border-0 mb-4">
                                    <div class="card-header" id="headingTwo">
                                        <div class="row">
                                            <div class="col-lg-12 col-sm-12">
                                                <div class="row order-data">
                                                    <div class="mont-font col-md-6 col-sm-4 col-xs-6 order-summary"><span>Order Summary</span></div>
                                                    <div class="col-md-6 col-sm-4 col-xs-6"><a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="float-right mont-font">Edit</a></div>
                                                </div>
                                                <div class="row cart-item-record">
                                                    <div class="col-md-6 col-sm-4 col-xs-12">
                                                        <img class="cart-coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                                                    </div>
                                                    <div class="col-md-6 col-sm-4 col-xs-9"><span class="product-name mont-font">Amazon Pay E-Gift Card  </span>
                                                        <div class="row item-qty-subtotal">
                                                            <div class="col-md-6 col-sm-4 col-xs-6"><span>Qty : 1</span></div>
                                                            <div class="col-md-6 col-sm-4 col-xs-6"><span>Subtotal : ₹100</span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row coupan-code">
                                                    <div class="col-md-12 col-sm-4 col-xs-12">
                                                        <form class="coupan-code-form"><input type="text" class="coupan-code-input mont-font" placeholder="Enter Coupan Code" id="coupan-code"><a href="#" id="apply-coupan"class="bg-current border-0 text-white apply-coupan-button mont-font">Button</a></form>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row total-amount">
                                                    <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font"><span>Grand Total : </span></div>
                                                    <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font"><span>₹100</span></div>
                                                    <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font"><span>Payable Amount : </span></div>
                                                    <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font"><span>₹1000</span></div>
                                                </div>
                                            </div>   
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="card shadow-none border-0">
                            <!-- <a href="{{route('user-logout')}}" class="header-btn bg-dark fw-500 text-white font-xssss">Logout</a> -->
                            <a href="#" class="mont-font w-100 p-3 mt-3 mb-3 font-xsss text-center text-white bg-current rounded-lg text-uppercase fw-600 ls-3" id="place-order">Place Order</a>    
                        </div>
                    </div>
                </div>
            </div>   
        </div>
 
    @push('scripts')
        <script type="text/javascript">
            $('#apply-coupan').click( function(e) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                e.preventDefault();
                var formData = new FormData();
                formData.append( 'coupan',$('#coupan-code').val());
                 
                
                var type = "POST";
                var ajaxurl = "{{url('/check-data')}}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (data) {
                        debugger;
                        console.log(data);
                    },
                    error: function (data) {
                        console.log(data);
                    }
                });
                return false;
            });
            // place order
            $('#place-order').click( function(e) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                e.preventDefault();
                var formData = new FormData();
                formData.append( 'coupan',$('#coupan-code').val());
                 
                
                var type = "POST";
                var ajaxurl = "{{url('/order-card')}}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (data) {
                        debugger;
                        console.log(data);
                    },
                    error: function (data) {
                        console.log(data);
                    }
                });
                return false;
            });
        </script>
    @endpush
@endsection