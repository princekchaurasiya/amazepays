@extends('app')
@section('title')
Amazepay | Checkout
@endsection
@section('content')
<div class="faq-wrapper pt-4 pb-0">
   <div class="container">
      <h2 class="text-grey-900 fw-400 display1-size mb-4 pb-3  text-center">Checkout</h2>
      <div class="row">
         <form method="POST" name="customerData" action="{{url('payment-process')}}">
            @csrf
            <input type="hidden" name="merchant_id" value="{{config('auth.merchant_id')}}"/>
            <input type="hidden" name="redirect_url" value="{{ route('response_ccavenue') }}"/>
            <input type="hidden" name="language" value="EN"/>
            <input type="hidden" name="cancel_url" value="{{url('payment-process')}}"/>
            <div class="col-lg-7">
               <div class="table-content table-responsive mb-5 card border-0 bg-greyblue p-5">
                  <div class="page-title">
                     <h4 class="mont-font fw-500 font-xxl mb-5">Sender Details</h4>
                     <div class="row">
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">First Name</label>
                              <input type="text" name="billing_name" class="form-control"
                                 value="{{ \Auth::user()->name }}">
                           </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">Email</label>
                              <input type="text" name="billing_email" class="form-control"
                                 value="{{ \Auth::user()->email }}">
                           </div>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">Phone</label>
                              <input type="text" name="billing_tel" class="form-control">
                           </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">Postcode</label>
                              <input type="text" name="billing_zip" class="form-control">
                           </div>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">Address 1</label>
                              <input type="text" name="billing_address" class="form-control">
                           </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">Address 2</label>
                              <input type="text" name="billing_address_two" class="form-control">
                           </div>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">Town / City</label>
                              <input type="text" name="billing_city" class="form-control">
                           </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                           <div class="form-gorup">
                              <label class="mont-font fw-500 font-xsss" for="comment-name">State</label>
                              <input type="text" name="billing_state" class="form-control">
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-5 cart-item">
               <div class="row justify-content-center">
                  <div class="card border-0 mb-4">
                     <div class="card-header" id="headingTwo">
                        <div class="row">
                           <div class="col-lg-12 col-sm-12">
                              <div class="row order-data">
                                 <div class="mont-font col-md-6 col-sm-4 col-xs-6 order-summary"><span>Order
                                    Summary</span>
                                 </div>
                                 <div class="col-md-6 col-sm-4 col-xs-6"><a
                                    href="{{ route('get-product-sku', ['slug' => $qsProd->sku]) }}"
                                    class="float-right mont-font">Edit</a></div>
                              </div>
                              <div class="row cart-item-record">
                                 <input type="hidden" name="order_id" value="123654789"/>
                                 <div class="col-md-6 col-sm-4 col-xs-12">
                                    <img class="cart-coupan-img"
                                       src="{{ json_decode($qsProd['images'])->small == null ? URL::asset('/images/hamburger.jpg') : json_decode($qsProd['images'])->small }}"
                                       alt="Avatar" style="width:100%;">
                                 </div>
                                 <div class="col-md-6 col-sm-4 col-xs-9">
                                    <span class="product-name mont-font">{{ $qsProd->name }}</span>
                                    <div class="row item-qty-subtotal">
                                       <div class="col-md-4 col-sm-4 col-xs-6"><span>Qtn :
                                          {{ $qsProd->prodData['quantity'] }}</span>
                                       </div>
                                       <div class="col-md-8 col-sm-4 col-xs-6"><span>Subtotal
                                          :₹{{ $qsProd->prodData['denomination'] }}</span>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="row coupan-code">
                                 <div class="col-md-12 col-sm-4 col-xs-12">
                                    <input type="text" class="coupan-code-input mont-font"
                                       placeholder="Enter Coupan Code" id="coupan-code"><a
                                       href="#"
                                       id="apply-coupan"class="bg-current border-0 text-white apply-coupan-button mont-font ">Apply</a>
                                    <span class="custLoaderDiv">
                                    <img src="{{ URL::asset('images/preloader.svg') }}"
                                       alt="" id="custLoaderImage"
                                       class="custLoaderImage img-responsive hideLoader">
                                    </span>
                                    <div class="coupon-code-error-div"><span
                                       class="error-coupon-code"></span></div>
                                 </div>
                              </div>
                              <hr>
                              <div class="row total-amount">
                                 <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font"><span>Grand
                                    Total : </span>
                                 </div>
                                 <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                    <input type="hidden"
                                    value="{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}"
                                    id="grand-amount"><span>₹{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}</span>
                                 </div>
                                 <div class="coupan-code-amount" id="discountDiv">
                                    <div
                                       class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font apply-coupan">
                                       <span>Discount : </span>
                                    </div>
                                    <div
                                       class="col-md-6 col-sm-4 col-xs-3 amount mont-font apply-coupan-amount">
                                    </div>
                                 </div>
                                 <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font"><span>Payable
                                    Amount : </span>
                                 </div>
                                 <input type="hidden" name="currency" value="INR"/>
                                 <input type="hidden" name="amount" class="hidden-total-payable-amount" value="{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}">
                                 <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font total-payable-amount">
                                    <span> ₹{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}</span>
                                    
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="card shadow-none border-0">
                  <!-- <a href="{{ route('user-logout') }}" class="header-btn bg-dark fw-500 text-white font-xssss">Logout</a> -->
                  {{-- <INPUT TYPE="submit" value="Place Order" class="mont-font w-100 p-3 mt-3 mb-3 font-xsss text-center text-white bg-current rounded-lg text-uppercase fw-600 ls-3"
                     id="place-order" > --}}
                  <input class="mont-font w-100 p-3 mt-3 mb-3 font-xsss text-center text-white bg-current rounded-lg text-uppercase fw-600 ls-3"
                      type="submit" value="Place Order">

                    
               </div>
            </div>
         </form>
      </div>
   </div>
</div>
@push('scripts')
<script type="text/javascript">
   $(document).ready(function() {
       var storageData = JSON.parse(window.localStorage.getItem('data'));
       console.log(storageData);
   });
   $('.coupan-code-amount').css('display', 'none');
   $('#remove-coupan-code').css('display', 'none');
   
   // final step of order place api
   $('#place-order').click(function(e) {
       $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
       });
       e.preventDefault();
       var formData = new FormData();
       formData.append('coupan', $('#coupan-code').val());
   
   
       var type = "POST";
       var ajaxurl = "{{ url('/') }}";
       $.ajax({
           type: type,
           url: ajaxurl,
           contentType: 'application/json',
           data: formData,
           processData: false,
           contentType: false,
           dataType: 'json',
           success: function(data) {
               debugger;
               console.log(data);
           },
           error: function(data) {
               console.log(data);
           }
       });
       return false;
   });
   
   // Apply Coupan
   function couponCodeasd() {
       $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
       });
   
       var formData = new FormData();
       formData.append('coupan', $('#coupan-code').val());
       formData.append('grand_total', $('#grand-amount').val());
   
       var type = "POST";
       var ajaxurl = "{{ url('/apply-coupan') }}";
       $.ajax({
           type: type,
           url: ajaxurl,
           contentType: 'application/json',
           data: formData,
           processData: false,
           contentType: false,
           dataType: 'json',
           success: function(data) {
               $('.apply-coupan-amount').text(data.coupan + '%');
               $('.hidden-total-payable-amount').val(data.aftApplyCoupan);
               $('.coupan-code-amount').css('display', 'contents');
               $('#remove-coupan-code').css('display', 'contents');
               $('.total-payable-amount').text('₹' + data.aftApplyCoupan);
              
           },
           error: function(data) {
               console.log(data);
           }
       });
   }
   // $('#apply-coupan').click( function(e) {
   
   //     return false;
   // });
   
   // on click apply coupon code starts here
   
   const applyButton = $('#apply-coupan');
   const loaderImage = $('#custLoaderImage');
   isHideLoaderPresent = loaderImage.hasClass('hideLoader');
   const inputFeild = $('#coupan-code');
   const discoutDiv = $('#discoutDiv');
   const coupanCodeInput = $('#coupan-code').val();
   // couponCode = false;
   couponCode = true;
   
   function showLoader() {
       if (isHideLoaderPresent) {
           loaderImage.removeClass("hideLoader");
           setTimeout(function() {
               loaderImage.addClass('hideLoader');
           }, 1000);
       }
   };
   
   function applyDiscount() {
       if (applyButton.html() === "Apply") {
           couponCodeasd();
           applyButton.html("Remove");
           applyButton.addClass("red");
           inputFeild.addClass("custDisabled");
           $(".error-coupon-code").text('Coupon apllied successfully');
           $(".error-coupon-code").addClass('greenColor');
           console.log(applyButton.html());
       } else {
           removeDiscount();
           applyButton.html("Apply");
           applyButton.removeClass("red");
           inputFeild.removeClass("custDisabled");
           discoutDiv.css("display", "none");
           $(".error-coupon-code").css('display', 'none');
       };
   };
   
   
   
   
   applyButton.click(function(e) {
       e.preventDefault();
       if (!($("#coupan-code").val() == "")) { // value not empty
           if (couponCode) {
               showLoader();
               applyDiscount();
           } else {
               $(".error-coupon-code").text('This is not a valid code');
               $(".error-coupon-code").addClass('redColor');
   
           }
       } else {
           $(".error-coupon-code").text('Coupon code can not be BLANK');
           $(".error-coupon-code").addClass('redColor');
       };
   });
   
   // on click apply button code ends here
   
   //  coupon code blank validation code starts here 
   
   
   
   //  coupon code blank validation code ends here
   // remove copuan code
   
   function removeDiscount() {
       $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
       });
       var formData = new FormData();
       formData.append('grand_total', $('#grand-amount').val());
   
   
       var type = "POST";
       var ajaxurl = "{{ url('/remove-apply-coupan') }}";
       $.ajax({
           type: type,
           url: ajaxurl,
           contentType: 'application/json',
           data: formData,
           processData: false,
           contentType: false,
           dataType: 'json',
           success: function(data) {
            debugger;
               $('.apply-coupan-amount').text(data.coupan);
               $('.coupan-code-amount').css('display', 'none');
               $('#remove-coupan-code').css('display', 'none');
               $('#coupan-code').val('');
               $('.total-payable-amount').text('₹' + data.grandTotal);
               $('.hidden-total-payable-amount').val(data.grandTotal);
           },
           error: function(data) {
               console.log(data);
           }
       });
   };
   
   // payment process script for ccavenue
   
   // $(function(){
   // /* json object contains
   // 1) payOptType - Will contain payment options allocated to the merchant. Options may include Credit Card, Net Banking, Debit Card, Cash Cards or Mobile Payments.
   // 2) cardType - Will contain card type allocated to the merchant. Options may include Credit Card, Net Banking, Debit Card, Cash Cards or Mobile Payments.
   // 3) cardName - Will contain name of card. E.g. Visa, MasterCard, American Express or and bank name in case of Net banking. 
   // 4) status - Will help in identifying the status of the payment mode. Options may include Active or Down.
   // 5) dataAcceptedAt - It tell data accept at CCAvenue or Service provider
   // 6)error -  This parameter will enable you to troubleshoot any configuration related issues. It will provide error description.
   // */	  
   // var jsonData;
   // var access_code="{{config('auth.access_code')}}"; // shared by CCAVENUE 
   // var amount="6000.00";
   // var currency="INR";
   
   
   // $.ajax({
   // url:'https://test.ccavenue.com/transaction/transaction.do?command=getJsonData&access_code='+access_code+'&currency='+currency+'&amount='+amount,
   // dataType: 'jsonp',
   // jsonp: false,
   // jsonpCallback: 'processData',
   // success: function (data) { 
   //      jsonData = data;
   //      // processData method for reference
   //      processData(data); 
   // // get Promotion details
   //      $.each(jsonData, function(index,value) {
   // if(value.Promotions != undefined  && value.Promotions !=null){  
   // var promotionsArray = $.parseJSON(value.Promotions);
   //      	$.each(promotionsArray, function() {
   // console.log(this['promoId'] +" "+this['promoCardName']);	
   // var	promotions=	"<option value="+this['promoId']+">"
   // +this['promoName']+" - "+this['promoPayOptTypeDesc']+"-"+this['promoCardName']+" - "+currency+" "+this['discountValue']+"  "+this['promoType']+"</option>";
   // $("#promo_code").find("option:last").after(promotions);
   // });
   // }
   // });
   // },
   // error: function(xhr, textStatus, errorThrown) {
   //    alert('An error occurred! ' + ( errorThrown ? errorThrown :xhr.status ));
   //    //console.log("Error occured");
   // }
   // });
   
   // $(".payOption").click(function(){
   // var paymentOption="";
   // var cardArray="";
   // var payThrough,emiPlanTr;
   // var emiBanksArray,emiPlansArray;
   
   // paymentOption = $(this).val();
   // $("#card_type").val(paymentOption.replace("OPT",""));
   // $("#card_name").children().remove(); // remove old card names from old one
   // $("#card_name").append("<option value=''>Select</option>");
   // $("#emi_div").hide();
   
   // //console.log(jsonData);
   // $.each(jsonData, function(index,value) {
   // 	//console.log(value);
   // 	  if(paymentOption !="OPTEMI"){
   //  	 if(value.payOpt==paymentOption){
   //  		cardArray = $.parseJSON(value[paymentOption]);
   //      	$.each(cardArray, function() {
   //       	$("#card_name").find("option:last").after("<option class='"+this['dataAcceptedAt']+" "+this['status']+"'  value='"+this['cardName']+"'>"+this['cardName']+"</option>");
   //      	});
   //       }
   //    }
      
   //    if(paymentOption =="OPTEMI"){
   //     if(value.payOpt=="OPTEMI"){
   //     	$("#emi_div").show();
   //     	$("#card_type").val("CRDC");
   //     	$("#data_accept").val("Y");
   //     	$("#emi_plan_id").val("");
   // $("#emi_tenure_id").val("");
   // $("span.emi_fees").hide();
   //     	$("#emi_banks").children().remove();
   //     	$("#emi_banks").append("<option value=''>Select your Bank</option>");
   //     	$("#emi_tbl").children().remove();
       	
   //          emiBanksArray = $.parseJSON(value.EmiBanks);
   //          emiPlansArray = $.parseJSON(value.EmiPlans);
   //      	$.each(emiBanksArray, function() {
   //       	payThrough = "<option value='"+this['planId']+"' class='"+this['BINs']+"' id='"+this['subventionPaidBy']+"' label='"+this['midProcesses']+"'>"+this['gtwName']+"</option>";
   //       	$("#emi_banks").append(payThrough);
   //      	});
        	
   //      	emiPlanTr="<tr><td>&nbsp;</td><td>EMI Plan</td><td>Monthly Installments</td><td>Total Cost</td></tr>";
   
   //      	$.each(emiPlansArray, function() {
   //       	emiPlanTr=emiPlanTr+
   // "<tr class='tenuremonth "+this['planId']+"' id='"+this['tenureId']+"' style='display: none'>"+
   // "<td> <input type='radio' name='emi_plan_radio' id='"+this['tenureMonths']+"' value='"+this['tenureId']+"' class='emi_plan_radio' > </td>"+
   // "<td>"+this['tenureMonths']+ "EMIs. <label class='merchant_subvention'>@ <label class='emi_processing_fee_percent'>"+this['processingFeePercent']+"</label>&nbsp;%p.a</label>"+
   // "</td>"+
   // "<td>"+this['currency']+"&nbsp;"+this['emiAmount'].toFixed(2)+
   // "</td>"+
   // "<td><label class='currency'>"+this['currency']+"</label>&nbsp;"+ 
   // "<label class='emiTotal'>"+this['total'].toFixed(2)+"</label>"+
   // "<label class='emi_processing_fee_plan' style='display: none;'>"+this['emiProcessingFee'].toFixed(2)+"</label>"+
   // "<label class='planId' style='display: none;'>"+this['planId']+"</label>"+
   // "</td>"+
   // "</tr>";
   // });
   // $("#emi_tbl").append(emiPlanTr);
   //       } 
   //       }
   // });
   
   // });
   
   
   // $("#card_name").click(function(){
   // if($(this).find(":selected").hasClass("DOWN")){
   // alert("Selected option is currently unavailable. Select another payment option or try again later.");
   // }
   // if($(this).find(":selected").hasClass("CCAvenue")){
   // $("#data_accept").val("Y");
   // }else{
   // $("#data_accept").val("N");
   // }
   // });
   
   // // Emi section start      
   // $("#emi_banks").live("change",function(){
   // if($(this).val() != ""){
   // 		var cardsProcess="";
   // 		$("#emi_tbl").show();
   // 		cardsProcess=$("#emi_banks option:selected").attr("label").split("|");
   // $("#card_name").children().remove();
   // $("#card_name").append("<option value=''>Select</option>");
   // $.each(cardsProcess,function(index,card){
   // $("#card_name").find("option:last").after("<option class=CCAvenue value='"+card+"' >"+card+"</option>");
   // });
   // $("#emi_plan_id").val($(this).val());
   // $(".tenuremonth").hide();
   // $("."+$(this).val()+"").show();
   // $("."+$(this).val()).find("input:radio[name=emi_plan_radio]").first().attr("checked",true);
   // $("."+$(this).val()).find("input:radio[name=emi_plan_radio]").first().trigger("click");
   
   // if($("#emi_banks option:selected").attr("id")=="Customer"){
   // $("#processing_fee").show();
   // }else{
   // $("#processing_fee").hide();
   // }
   
   // }else{
   // $("#emi_plan_id").val("");
   // $("#emi_tenure_id").val("");
   // $("#emi_tbl").hide();
   // }
   
   
   
   // $("label.emi_processing_fee_percent").each(function(){
   // if($(this).text()==0){
   // $(this).closest("tr").find("label.merchant_subvention").hide();
   // }
   // });
   
   // });
   
   // $(".emi_plan_radio").live("click",function(){
   // var processingFee="";
   // $("#emi_tenure_id").val($(this).val());
   // processingFee=
   // "<span class='emi_fees' >"+
   // "Processing Fee:"+$(this).closest('tr').find('label.currency').text()+"&nbsp;"+
   // "<label id='processingFee'>"+$(this).closest('tr').find('label.emi_processing_fee_plan').text()+
   // "</label><br/>"+
   //     			"Processing fee will be charged only on the first EMI."+
   //     	"</span>";
   //  $("#processing_fee").children().remove();
   //  $("#processing_fee").append(processingFee);
    
   //  // If processing fee is 0 then hiding emi_fee span
   //  if($("#processingFee").text()==0){
   //  	$(".emi_fees").hide();
   //  }
   
   // });
   
   
   // $("#card_number").focusout(function(){
   // /*
   // emi_banks(select box) option class attribute contains two fields either allcards or bin no supported by that emi 
   // */ 
   // if($('input[name="payment_option"]:checked').val() == "OPTEMI"){
   // if(!($("#emi_banks option:selected").hasClass("allcards"))){
   // if(!$('#emi_banks option:selected').hasClass($(this).val().substring(0,6))){
   // alert("Selected EMI is not available for entered credit card.");
   // }
   // }
   // }
   
   // });
   
   
   // // Emi section end 		
   
   
   // // below code for reference 
   
   // function processData(data){
   // var paymentOptions = [];
   // var creditCards = [];
   // var debitCards = [];
   // var netBanks = [];
   // var cashCards = [];
   // var mobilePayments=[];
   // $.each(data, function() {
   // // this.error shows if any error   	
   //  console.log(this.error);
   //   paymentOptions.push(this.payOpt);
   //   switch(this.payOpt){
   //     case 'OPTCRDC':
   //     	var jsonData = this.OPTCRDC;
   //      	var obj = $.parseJSON(jsonData);
   //      	$.each(obj, function() {
   //      		creditCards.push(this['cardName']);
   //     	});
   //     break;
   //     case 'OPTDBCRD':
   //     	var jsonData = this.OPTDBCRD;
   //      	var obj = $.parseJSON(jsonData);
   //      	$.each(obj, function() {
   //      		debitCards.push(this['cardName']);
   //     	});
   //     break;
   //   	case 'OPTNBK':
   //    	var jsonData = this.OPTNBK;
   //      var obj = $.parseJSON(jsonData);
   //      $.each(obj, function() {
   //       	netBanks.push(this['cardName']);
   //      });
   //     break;
       
   //     case 'OPTCASHC':
   //       var jsonData = this.OPTCASHC;
   //       var obj =  $.parseJSON(jsonData);
   //       $.each(obj, function() {
   //       	cashCards.push(this['cardName']);
   //       });
   //      break;
          
   //       case 'OPTMOBP':
   //       var jsonData = this.OPTMOBP;
   //       var obj =  $.parseJSON(jsonData);
   //       $.each(obj, function() {
   //       	mobilePayments.push(this['cardName']);
   //       });
   //   }
     
   // });
   
   // //console.log(creditCards);
   // // console.log(debitCards);
   // // console.log(netBanks);
   // // console.log(cashCards);
   // //  console.log(mobilePayments);
   
   // }
   // });
   
</script>
@endpush
@endsection