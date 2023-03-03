@extends('app')
@section('title')
Amazepay | Gift Card
@endsection
@section('content')

    <div class="gift-card-detail-page pt-lg--7 pb-lg--7 pb-5 pt-5">
        <div class="container">
            <div class="row">
                <form action="{{route('checkout', ['sku' => $prdtDetails['sku']])}}" method="POST" id="form_id">
                    {{csrf_field()}}
                    <div class="col-lg-12 mb-lg-4 mb-4 pb-3">
                        <h6 class="text-grey-900 fw-400 font-xl">E-Gift Card</h6>
                        <hr>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card-container">
                                <img src="{{$prdtDetails['images']['small'] == null ? URL::asset('/images/hamburger.jpg'): $prdtDetails['images']['small']}}" alt="product-detail-image">
                            </div>
                            <div class="container">
                                <!-- <h6 class="text-grey-900 fw-400 font-xs mt-2">Offers</h6>
                                <ul class="square-type-unordered">
                                    <li>Only UPI payment is accepted for this gift card. --- On Amazon Pay Special E-Gift Card (woohoo.in/amazon-pay-special-e-gift-card) Credit/Debit card and Net Banking options are available.</li>
                                </ul> -->

                                
                            </div>
                                
                        </div>
                        <div class="col-lg-6">
                            <div class="container">
                                <div class="row">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <h6 class="mb-3 fw-600 font-xs mt-2" name="product_name" value="{{$prdtDetails['name']}}">{{$prdtDetails['name']}}</h6>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="mb-3 font-xssss fw-600 mt-2">Validity : {{$prdtDetails['expiry']}}</span>
                                        </div>
                                    </div>
                                    <div class="row copuan-quantity">
                                        <div class="col-sm-6">
                                            @if($prdtDetails['price']['type'] == "RANGE")
                                                <div class="radio-btn-row">
                                                    @foreach($prdtDetails['price']['denominations'] as $denomination)
                                                        <div class="custom-control mr-4 custom-radio custom-control-inline">
                                                            <input type="radio" class="custom-control-input range" id="customRadio-{{$denomination}}" name="denomination" value="{{$denomination}}">
                                                            <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio-{{$denomination}}">{{$denomination}}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <input type="text" class="form-control mb-3" placeholder="Select Denomination" name="denomination" id="denomination" value="">
                                                <span class="font-xssss fw-400 error-rec-deno"></span>
                                            @endif  
                                        </div>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control mb-3" placeholder="Quantity" name="quantity" id="quantity" value="">
                                            <span class="font-xssss fw-400 error-rec-qnty"></span>
                                        </div>
                                    </div>
                                    
                                </div>   
                            </div>
                        </div>
                    </div>
                    <div class="row personalise-gift-card">
                        <div class="tabs">
                            <input type="radio" name="tabs" id="tabone" checked="checked">
                            <label for="tabone">Offers</label>
                            <div class="tab">  
                                <ul class="square-type-unordered">
                                    <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                                    <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                                    <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                                    <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                                    <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                                </ul>
                            </div>
                        
                            <input type="radio" name="tabs" id="tabtwo">
                            <label for="tabtwo">Description</label>
                            <div class="tab">
                                <ul class="square-type-unordered">
                                    <li>{{$prdtDetails['description']}}</li>
                                </ul>
                            </div>
                        
                            <input type="radio" name="tabs" id="tabthree">
                            <label for="tabthree">Terms & Condition</label>
                            <div class="tab term-condition">
                                {!! $prdtDetails['tnc']['content'] !!}
                            </div>
                            <input type="radio" name="tabs" id="tabfour">
                            <label for="tabfour">How to Redeem</label>
                            <div class="tab">
                                <ul class="square-type-unordered">
                                    <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                                    <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                                    <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                                    <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                                    <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                                </ul>
                            </div>
                        </div>
                    </div> 
                    @if(\Auth::user())
                        <input type="submit" class="form-control rounded-lg h60 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 mt-4 w100" value="Pay Now" id="pay-now">
                    @else
                        <a href="#" class="form-control rounded-lg h60 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 mt-4 w100" data-toggle="modal" data-target="#Modallogin">Pay Now</a>
                    @endif
                </form>
            </div>
            
        </div>
    </div>
    @push('scripts')
        <script>
            // Preview Image on page load
                $(document).ready(function(){
                    $('div>.owl-items:first').addClass('border-black');
                    $('.child:first').css("display","block");
                    $('.child-active:first').addClass('border-black');
                    $('.preview > img').attr("src",$('.active:first').find('img').attr('src')); 

                    var storageData = JSON.parse(window.localStorage.getItem('data'));     
                    // $('#receiver-name').val(storageData.recName);
                    // $('#receiver-email').val(storageData.recEmail);
                    // $('#receiver-msg').val(storageData.recMsg); 
                    // alert($('.copuan-quantity').find('.range').attr('type').length);
                    if($('.copuan-quantity').find('.range').attr('type') == 'radio'){

                        $($('.copuan-quantity').find('.range')).each(function(index,val){
                            if(val['value'] == storageData.denomination){
                                $(this).prop('checked', true);
                            }  
                        }); 
                    } else {
                        $('#denomination').val(storageData.denomination);
                    } 
                    // $('#denomination').val(storageData.denomination);
                    $('#quantity').val(storageData.quantity);   
                });  
            // end Preview Image on page load

            // Gift Send Option
                $('#customRadio').click(function(){
                    $('.gifting-details').removeClass('d-none');
                    $('.add-gift-cards').addClass('d-none');
                });
                $('#customRadio1').click(function(){
                    $('.add-gift-cards').removeClass('d-none');
                    $('.gifting-details').addClass('d-none');
                });

            // Delivery Mode
                $("input[name='delivery_mode']").change(function(){
                    var delivery_mode = $(this).val();
                    switch (delivery_mode) { 
                        case 'email': 
                            $('.receiver-email').removeClass('d-none');
                            $('.receiver-mobile').addClass('d-none');
                            break;
                        case 'mobile': 
                            $('.receiver-email').addClass('d-none');
                            $('.receiver-mobile').removeClass('d-none');
                            break;
                        default:
                            $('.receiver-email').removeClass('d-none');
                            $('.receiver-mobile').removeClass('d-none');
                    }
                });

            // Preview Image   
                $('.active').on('click',function(){
                    $('.active').removeClass('border-black');
                    $(this).addClass('border-black');
                    // console.log($(this).find('img').attr('src'));
                    $('.preview > img').attr("src",$(this).find('img').attr('src'));
                    
                });
                
                // function showChild(className) {
                //     // var $thumb = $(this);
                //     // console.log($('div > img').attr('src'));
                //     // $('.preview > img').attr("src",$('.active').find('img').attr('src'));
                //     // $('.child').css("display","none");
                //     // $('.child_' + className).css("display","block");
                //     // $('.child-active').removeClass('border-black');
                //     // $('.child').find('.child-active:first').addClass('border-black');
                //     //$('.preview > img').attr("src",$(this).find('img').attr('src'));                
                // }

                // $('.thumbnail').click(function() {
                //     var $thumb = $(this);
                //     $('.preview > img').attr("src",$thumb.find('img').attr('src'));
                // });             
                // $('.child-active').click(function(){
                //     $('.child-active').removeClass('border-black');
                //     $(this).addClass('border-black');
                //     //var $thumb = $(this);
                //     $('.preview > img').attr("src",$(this).find('img').attr('src'));
                // });
            // end Preview Image 
            // $('#myForm').on('submit', function(e){
            //     e.preventDefault();
            //     var len = $('#username').val().length;
            //     if (len < 6 && len > 1) {
            //         this.submit();
            //     }
            // });
            
            $('form').on('submit',function(e){
                e.preventDefault();
                var denomination    = '';
                if($('.copuan-quantity').find('.range').attr('type') == 'radio'){
                    denomination = $("input[name='denomination']:checked").val();
                } else {
                    denomination = $('#denomination').val();
                }
                var quantity = $('#quantity').val(); 

                const data = {
                    denomination: denomination,
                    quantity: quantity,
                }
                var localData = window.localStorage.setItem('data', JSON.stringify(data));
                //this.submit();
                
                var status = true;
                if (denomination.length == "") {
                    $(".error-rec-deno").text('Please Select Denomination');
                    status     = false;
                } else {
                    $(".error-rec-deno").empty();
                    
                }

                if (quantity.length == "") {
                    $(".error-rec-qnty").text('Enter Quantity' );
                    $(".error-rec-qnty").css('color', 'red');
                    // $(this).css('color', 'red');
                    status     = false;
                } else {
                    $(".error-rec-qnty").empty();
                    
                }
                if(status != true){
                     return false;  
                } else {
                     this.submit();
                }
                // switch (delivery_mode) { 
                //     case 'email': 
                //         status = email(recName,recEmail,recMsg);
                //         break;
                //     case 'mobile': 
                //         status =  mobile(recName,recMobile,recMsg);
                //         break;
                //     default:
                //         status =  both(recName,recEmail,recMobile,recMsg);
                // }

                // if(status != true){
                //     return false;  
                // } else {
                //     this.submit();
                // } 
            });

            function email(recName,recEmail,recMsg){
                var status     = true;
                if (recName.length == "") {
                    $(".error-rec-name").text('Name Required');
                    status     = false;
                } else {
                    $(".error-rec-name").empty();
                    
                }
                if (recEmail.length == "") {
                    $(".error-rec-email").text('Email Required');
                    status     = false;
                } else {
                    $(".error-rec-email").empty();
                    
                }
                if(status == true){ 
                    return status;
                } else {
                    return status;
                }
            }

            function mobile(recName,recMobile,recMsg){
                
                var status     = true;
                if (recName.length == "") {
                    $(".error-rec-name").text('Name Required');
                    status     = false;
                } else {
                    $(".error-rec-name").empty();
                    
                }
                if (recMobile.length == "") {
                    $(".error-rec-mobile").text('Mobile Required');
                    status     = false;
                } else {
                    $(".error-rec-mobile").empty();
                    
                }
                if(status == true){
                    return status;
                } else {
                    return status;
                }
                
            }

            function both(recName,recEmail,recMobile,recMsg){
                
                var status     = true;
                if (recName.length == "") {
                    $(".error-rec-name").text('Name Required');
                    status = false;
                } else {
                    $(".error-rec-name").empty();
                }
                if (recEmail.length == "") {
                    $(".error-rec-email").text('Email Required');
                    status = false;
                } else {
                    $(".error-rec-email").empty();
                }
                if (recMobile.length == "") {
                    $(".error-rec-mobile").text('Mobile Required');
                    status = false;
                } else {
                    $(".error-rec-mobile").empty();
                }
                if(status == true){
                     return status;
                } else {
                     return status;
                }
            }


            $.each($('.radio-btn'), function(key, value) {
                $(this).click(function(e) {
                    $('.radio-btn-selected')
                    .removeClass('radio-btn-selected')
                    .addClass('radio-btn');

                    $(this)
                    .removeClass('radio-btn')
                    .addClass('radio-btn-selected');

                    //do whatever you want on click
                });
            });

            $('#tabthree').click(function(){
                $('.term-condition').find("ol, ul").addClass('square-type-unordered');
            });

        // pay now
            // function payNow(recName,recEmail,recMsg){
            //     console.log(recName,recEmail,recMsg);
            //     $.ajaxSetup({
            //         headers: {
            //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            //         }
            //     });
            //     // var formData = new FormData();
            //     // formData.append( 'coupan',$('#coupan-code').val());
                 
                
            //     var type = "POST";
            //     var ajaxurl = "{{url('/check-user-validation')}}";

            //     return false;
            //     $.ajax({
            //         type: type,
            //         url: ajaxurl,
            //         contentType: 'application/json',
            //         // data: formData,
            //         processData: false,
            //         contentType: false,
            //         dataType: 'json',
            //         success: function (data) {
            //             debugger;
            //             console.log(data);
            //             $("#Modallogin").modal('show');
            //         },
            //         error: function (data) {
            //             console.log(data);
            //         }
            //     });
            //     return false;
            // }
        </script>
    @endpush
@endsection