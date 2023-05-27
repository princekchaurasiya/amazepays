@extends('app')
@section('title')
    Amazepay | Payment
@endsection
@section('content')
    <h1>CCAvenue Payment Gateway Integration</h1>
    <div id="ccav-payment-form">

        <form name="frmPayment" action="{{url('payment-process')}}" method="POST">
            @csrf
            {{-- <input type="hidden" name="merchant_id" value="{{ config('auth.merchant_id') }}"> --}}
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <table width="40%" height="100" border='1' align="center"><caption><font size="4" color="blue"><b>Integration Kit</b></font></caption></table>
                <table width="50%" height="100" border='1' align="center">
                    <tr>
                        <td>Parameter Name:</td><td>Parameter Value:</td>
                    </tr>
                    <tr>
                        <td colspan="2"> Compulsory information</td>
                    </tr>
                    <tr>
                        <td>TID	:</td><td><input type="text" name="tid" id="tid" value="123123123" readonly /></td>
                    </tr>
                    <tr>
                        <td>Merchant Id	:</td><td><input type="text" name="merchant_id" value="{{ config('auth.merchant_id') }}"/></td>
                    </tr>
                    <tr>
                        <td>Order Id	:</td><td><input type="text" name="order_id" value="123654789"/></td>
                    </tr>
                    <tr>
                        <td>Amount	:</td><td><input type="text" name="amount" value="10.00"/></td>
                    </tr>
                    <tr>
                        <td>Currency	:</td><td><input type="text" name="currency" value="INR"/></td>
                    </tr>
                    <tr>
                        <td>Redirect URL	:</td><td><input type="text" name="redirect_url" value="http://localhost/ccavResponseHandler.php"/></td>
                    </tr>
                     <tr>
                         <td>Cancel URL	:</td><td><input type="text" name="cancel_url" value="http://localhost/ccavResponseHandler.php"/></td>
                     </tr>
                     <tr>
                        <td>Language	:</td><td><input type="text" name="language" value="EN"/></td>
                    </tr>
                     <tr>
                         <td colspan="2">Billing information(optional):</td>
                     </tr>
                    <tr>
                        <td>Billing Name	:</td><td><input type="text" name="billing_name" value="Charli"/></td>
                    </tr>
                    <tr>
                        <td>Billing Address	:</td><td><input type="text" name="billing_address" value="Room no 1101, near Railway station Ambad"/></td>
                    </tr>
                    <tr>
                        <td>Billing City	:</td><td><input type="text" name="billing_city" value="Indore"/></td>
                    </tr>
                    <tr>
                        <td>Billing State	:</td><td><input type="text" name="billing_state" value="MH"/></td>
                    </tr>
                    <tr>
                        <td>Billing Zip	:</td><td><input type="text" name="billing_zip" value="425001"/></td>
                    </tr>
                    <tr>
                        <td>Billing Country	:</td><td><input type="text" name="billing_country" value="India"/></td>
                    </tr>
                    <tr>
                        <td>Billing Tel	:</td><td><input type="text" name="billing_tel" value="9999999999"/></td>
                    </tr>
                    <tr>
                        <td>Billing Email	:</td><td><input type="text" name="billing_email" value="test@test.com"/></td>
                    </tr>
                    <tr>
                        <td colspan="2">Shipping information(optional)</td>
                    </tr>
                    <tr>
                        <td>Shipping Name	:</td><td><input type="text" name="delivery_name" value="Chaplin"/></td>
                    </tr>
                    <tr>
                        <td>Shipping Address	:</td><td><input type="text" name="delivery_address" value="room no.701 near bus stand"/></td>
                    </tr>
                    <tr>
                        <td>shipping City	:</td><td><input type="text" name="delivery_city" value="Hyderabad"/></td>
                    </tr>
                    <tr>
                        <td>shipping State	:</td><td><input type="text" name="delivery_state" value="Andhra"/></td>
                    </tr>
                    <tr>
                        <td>shipping Zip	:</td><td><input type="text" name="delivery_zip" value="425001"/></td>
                    </tr>
                    <tr>
                        <td>shipping Country	:</td><td><input type="text" name="delivery_country" value="India"/></td>
                    </tr>
                    <tr>
                        <td>Shipping Tel	:</td><td><input type="text" name="delivery_tel" value="5555555555"/></td>
                    </tr>
                    <tr>
                        <td>Merchant Param1	:</td><td><input type="text" name="merchant_param1" value="additional Info."/></td>
                    </tr>
                    <tr>
                        <td>Merchant Param2	:</td><td><input type="text" name="merchant_param2" value="additional Info."/></td>
                    </tr>
                    <tr>
                        <td>Merchant Param3	:</td><td><input type="text" name="merchant_param3" value="additional Info."/></td>
                    </tr>
                    <tr>
                        <td>Merchant Param4	:</td><td><input type="text" name="merchant_param4" value="additional Info."/></td>
                    </tr>
                    <tr>
                        <td>Merchant Param5	:</td><td><input type="text" name="merchant_param5" value="additional Info."/></td>
                    </tr>
                     
                     <tr>
                         <td colspan="2">Payment information:</td>
                     </tr>
                     <tr> <td> Payment Option: </td> 
                           <td> 
                                   <input class="payOption" type="radio" name="payment_option" value="OPTCRDC">Credit Card
                                   <input class="payOption" type="radio" name="payment_option" value="OPTDBCRD">Debit Card  <br/>
                                   <input class="payOption" type="radio" name="payment_option" value="OPTNBK">Net Banking 
                                   <input class="payOption" type="radio" name="payment_option" value="OPTCASHC">Cash Card <br/>
                                   <input class="payOption" type="radio" name="payment_option" value="OPTMOBP">Mobile Payments
                                   <input class="payOption" type="radio" name="payment_option" value="OPTEMI">EMI
                            <input class="payOption" type="radio" name="payment_option" value="OPTWLT">Wallet
                            </td>
                     </tr>
                     
                     <!-- EMI section start -->
                     
                     <tr >
                     <td  colspan="2">
                      <div id="emi_div" style="display: none">
                         <table border="1" width="100%">
                         <tr> <td colspan="2">EMI Section </td></tr>
                         <tr> <td> Emi plan id: </td>
                            <td><input readonly="readonly" type="text" id="emi_plan_id"  name="emi_plan_id" value=""/> </td>
                         </tr>
                         <tr> <td> Emi tenure id: </td>
                            <td><input readonly="readonly" type="text" id="emi_tenure_id" name="emi_tenure_id" value=""/>  </td>
                         </tr>
                         <tr><td>Pay Through</td>
                             <td>
                                 <select name="emi_banks"  id="emi_banks">
                                 </select>
                             </td>
                        </tr>
                        <tr><td colspan="2">
                             <div id="emi_duration" class="span12">
                                <span class="span12 content-text emiDetails">EMI Duration</span>
                                <table id="emi_tbl" cellpadding="0" cellspacing="0" border="1" >
                                </table> 
                            </div>
                            </td>
                        </tr>
                        <tr>
                             <td id="processing_fee" colspan="2">
                            </td>
                        </tr>
                        </table>
                    </div>
                    </td>
                    </tr>
                    <!-- EMI section end -->
                     
                     
                     <tr> <td> Card Type: </td>
                         <td><input type="text" id="card_type" name="card_type" value="" readonly="readonly"/></td>
                     </tr>
                    
                    <tr> <td> Card Name: </td>
                         <td> <select name="card_name" id="card_name"> <option value="">Select Card Name</option> </select> </td>
                    </tr>
                    
                    <tr> <td> Data Accepted At </td>
                         <td><input type="text" id="data_accept" name="data_accept" readonly="readonly"/></td>
                    </tr>
                     
                     <tr> <td> Card Number: </td>
                        <td> <input type="text" id="card_number" name="card_number" value=""/>e.g. 4111111111111111 </td>
                     </tr>
                      <tr> <td> Expiry Month: </td>
                           <td> <input type="text" name="expiry_month" value=""/>e.g. 07 </td>
                     </tr>
                      <tr> <td> Expiry Year: </td>
                             <td> <input type="text" name="expiry_year" value=""/>e.g. 2027</td>
                     </tr>
                      <tr> <td> CVV Number:</td>
                           <td> <input type="text" name="cvv_number" value=""/>e.g. 328</td>
                     </tr>
                     <tr> <td> Issuing Bank:</td>
                        <td><input type="text" name="issuing_bank" value=""/>e.g. State Bank Of India</td>
                     </tr>
                 <tr> 
                    <td> Mobile Number:</td>
                            <td><input type="text" name="mobile_number" value=""/>e.g. 9770707070</td>
                     </tr>
                <tr> 
                    <td> MMID:</td>
                            <td><input type="text" name="mm_id" value=""/>e.g. 1234567</td>
                     </tr>
                <tr> 
                    <td> OTP:</td>
                            <td><input type="text" name="otp" value=""/>e.g. 123456</td>
                     </tr>
                 <tr> 
                    <td> Promotions:</td>
                            <td> <select name="promo_code" id="promo_code"> <option value="">All Promotions &amp; Offers</option> </select> </td>
                     </tr>
                     
                    <tr>
                        <td></td><td><INPUT TYPE="submit" value="CheckOut"></td>
                    </tr>
                  </table>
              </form>







        {{-- <form name="frmPayment" action="{{url('payment-process')}}" method="POST">
            @csrf
            <input type="hidden" name="merchant_id" value="{{ config('auth.merchant_id') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="language" value="EN">
            <input type="hidden" name="amount" value="1">
            <input type="hidden" name="currency" value="INR">
            <input type="hidden" name="redirect_url" value="{{ route('payment-success') }}">
            <input type="hidden" name="cancel_url" value="{{ route('payment-failed') }}">
            <div>
                <input type="text" name="billing_name" value="" class="form-field" placeholder="Billing Name">
                <input type="text" name="billing_address" value="" class="form-field"
                    placeholder="Billing Address">
            </div>
            <div>
                <input type="text" name="billing_state" value="" class="form-field" placeholder="State">
                <input type="text" name="billing_zip" value="" class="form-field" placeholder="Zipcode">
            </div>
            <div>
                <input type="text" name="billing_country" value="" class="form-field" placeholder="Country">
                <input type="text" name="billing_tel" value="" class="form-field" placeholder="Phone">
            </div>
            <div>
                <input type="text" name="billing_email" value="" class="form-field" placeholder="Email">
            </div>
            <div>
                <button class="btn-payment" type="submit">Pay Now</button>
            </div>
        </form> --}}
    </div>
@endsection