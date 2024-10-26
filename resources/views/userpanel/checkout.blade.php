@extends('layouts.app')
@section('title')
    Amazepay | Checkout
@endsection
@section('content')
    <div class="container">
        <div class="faq-wrapper pt-4 pb-0">

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

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <h2 class="text-grey-900 fw-400 display1-size mb-4 pb-3 text-center">Checkout</h2>


            <form method="POST" name="customerData" action="{{ url('payment-process') }}" id="checkoutForm">
                @csrf
                <div class="row">
                    <div class="col-lg-7">
                        <div class="table-content table-responsive mb-5 card border-0 bg-greyblue p-5">
                            <div class="page-title">
                                <h4 class="mont-font fw-500 font-xxl mb-5">Sender Details</h4>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Full Name</label>
                                            <input type="text" name="billing_name" class="form-control billingFormInput"
                                                value="{{ old('billing_name', Auth::user()->name ?? '') }}" maxlength="255">
                                            @error('billing_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Email</label>
                                            <input type="text" name="billing_email" class="form-control billingFormInput"
                                                value="{{ old('billing_email', Auth::user()->email ?? '') }}"
                                                maxlength="255">
                                            @error('billing_email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Phone</label>
                                            <input type="text" name="billing_tel"
                                                class="form-control billingFormInput inputDiv"
                                                value="{{ old('billing_tel', Auth::user()->mobile ?? '') }}" maxlength="10">
                                            @error('billing_tel')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Postcode</label>
                                            <input type="text" name="billing_zip" class="form-control billingFormInput"
                                                value="{{ old('billing_zip', Auth::user()->billing_zip ?? '') }}"
                                                maxlength="6">
                                            @error('billing_zip')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Address 1</label>
                                            <input type="text" name="billing_address"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_address', Auth::user()->billing_address ?? '') }}"
                                                maxlength="255">
                                            @error('billing_address')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Address 2</label>
                                            <input type="text" name="billing_address_two"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_address_two', Auth::user()->billing_address_two ?? '') }}"
                                                maxlength="255">
                                            @error('billing_address_two')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Town / City</label>
                                            <input type="text" name="billing_city" class="form-control billingFormInput"
                                                value="{{ old('billing_city', Auth::user()->billing_city ?? '') }}"
                                                maxlength="255">
                                            @error('billing_city')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">State</label>
                                            <input type="text" name="billing_state" class="form-control billingFormInput"
                                                value="{{ old('billing_state', Auth::user()->billing_state ?? '') }}"
                                                maxlength="255">
                                            @error('billing_state')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Country</label>
                                            <select name="billing_country" id="billing_country"
                                                class="form-control billingFormInput">
                                                <!-- Default option -->
                                                <option value="">Select a country</option>
                                                <!-- India as the first option and selected by default -->
                                                <option value="India"
                                                    {{ old('billing_country', Auth::user()->billing_country ?? 'India') == 'India' ? 'selected' : '' }}>
                                                    India</option>
                                                <!-- Other countries -->
                                                <option value="Afghanistan">Afghanistan</option>

                                                <option value="Aland Islands">Aland Islands</option>

                                                <option value="Albania">Albania</option>

                                                <option value="Algeria">Algeria</option>

                                                <option value="American Samoa">American Samoa</option>

                                                <option value="Andorra">Andorra</option>

                                                <option value="Angola">Angola</option>

                                                <option value="Anguilla">Anguilla</option>

                                                <option value="Antarctica">Antarctica</option>

                                                <option value="Antigua and Barbuda">Antigua and Barbuda</option>

                                                <option value="Argentina">Argentina</option>

                                                <option value="Armenia">Armenia</option>

                                                <option value="Aruba">Aruba</option>

                                                <option value="Australia">Australia</option>

                                                <option value="Austria">Austria</option>

                                                <option value="Azerbaijan">Azerbaijan</option>

                                                <option value="Bahamas">Bahamas</option>

                                                <option value="Bahrain">Bahrain</option>

                                                <option value="Bangladesh">Bangladesh</option>

                                                <option value="Barbados">Barbados</option>

                                                <option value="Belarus">Belarus</option>

                                                <option value="Belgium">Belgium</option>

                                                <option value="Belize">Belize</option>

                                                <option value="Benin">Benin</option>

                                                <option value="Bermuda">Bermuda</option>

                                                <option value="Bhutan">Bhutan</option>

                                                <option value="Bolivia">Bolivia</option>

                                                <option value="Bonaire">Bonaire</option>

                                                <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>

                                                <option value="Botswana">Botswana</option>

                                                <option value="Bouvet Island">Bouvet Island</option>

                                                <option value="Brazil">Brazil</option>

                                                <option value="British Indian Ocean Territory">British Indian Ocean
                                                    Territory</option>

                                                <option value="Brunei Darussalam">Brunei Darussalam</option>

                                                <option value="Bulgaria">Bulgaria</option>

                                                <option value="Burkina Faso">Burkina Faso</option>

                                                <option value="Burundi">Burundi</option>

                                                <option value="Cambodia">Cambodia</option>

                                                <option value="Cameroon">Cameroon</option>

                                                <option value="Canada">Canada</option>

                                                <option value="Cape Verde">Cape Verde</option>

                                                <option value="Cayman Islands">Cayman Islands</option>

                                                <option value="Central African Republic">Central African Republic</option>

                                                <option value="Chad">Chad</option>

                                                <option value="Chile">Chile</option>

                                                <option value="China">China</option>

                                                <option value="Christmas Island">Christmas Island</option>

                                                <option value="Cocos Islands">Cocos Islands</option>

                                                <option value="Colombia">Colombia</option>

                                                <option value="Comoros">Comoros</option>

                                                <option value="Congo">Congo</option>

                                                <option value="Democratic Republic of the Congo">Democratic Republic of the
                                                    Congo</option>

                                                <option value="Cook Islands">Cook Islands</option>

                                                <option value="Costa Rica">Costa Rica</option>

                                                <option value="Cote dIvoire">Cote dIvoire</option>

                                                <option value="Croatia">Croatia</option>

                                                <option value="Cuba">Cuba</option>

                                                <option value="Curacao">Curacao</option>

                                                <option value="Cyprus">Cyprus</option>

                                                <option value="Czech Republic">Czech Republic</option>

                                                <option value="Denmark">Denmark</option>

                                                <option value="Djibouti">Djibouti</option>

                                                <option value="Dominica">Dominica</option>

                                                <option value="Dominican Republic">Dominican Republic</option>

                                                <option value="Ecuador">Ecuador</option>

                                                <option value="Egypt">Egypt</option>

                                                <option value="El Salvador">El Salvador</option>

                                                <option value="Equatorial Guinea">Equatorial Guinea</option>

                                                <option value="Eritrea">Eritrea</option>

                                                <option value="Estonia">Estonia</option>

                                                <option value="Ethiopia">Ethiopia</option>

                                                <option value="Falkland Islands">Falkland Islands</option>

                                                <option value="Faroe Islands">Faroe Islands</option>

                                                <option value="Fiji">Fiji</option>

                                                <option value="Finland">Finland</option>

                                                <option value="France">France</option>

                                                <option value="French Guiana">French Guiana</option>

                                                <option value="French Polynesia">French Polynesia</option>

                                                <option value="French Southern Territories">French Southern Territories
                                                </option>

                                                <option value="Gabon">Gabon</option>

                                                <option value="Gambia">Gambia</option>

                                                <option value="Georgia">Georgia</option>

                                                <option value="Germany">Germany</option>

                                                <option value="Ghana">Ghana</option>

                                                <option value="Gibraltar">Gibraltar</option>

                                                <option value="Greece">Greece</option>

                                                <option value="Greenland">Greenland</option>

                                                <option value="Grenada">Grenada</option>

                                                <option value="Guadeloupe">Guadeloupe</option>

                                                <option value="Guam">Guam</option>

                                                <option value="Guatemala">Guatemala</option>

                                                <option value="Guernsey">Guernsey</option>

                                                <option value="Guinea">Guinea</option>

                                                <option value="Guinea Bissau">Guinea Bissau</option>

                                                <option value="Guyana">Guyana</option>

                                                <option value="Haiti">Haiti</option>

                                                <option value="Heard Island and McDonald Islands">Heard Island and McDonald
                                                    Islands</option>

                                                <option value="Vatican City">Vatican City</option>

                                                <option value="Honduras">Honduras</option>

                                                <option value="Hong Kong">Hong Kong</option>

                                                <option value="Hungary">Hungary</option>

                                                <option value="Iceland">Iceland</option>



                                                <option value="Indonesia">Indonesia</option>

                                                <option value="Iran">Iran</option>

                                                <option value="Iraq">Iraq</option>

                                                <option value="Ireland">Ireland</option>

                                                <option value="Isle of Man">Isle of Man</option>

                                                <option value="Israel">Israel</option>

                                                <option value="Italy">Italy</option>

                                                <option value="Jamaica">Jamaica</option>

                                                <option value="Japan">Japan</option>

                                                <option value="Jersey">Jersey</option>

                                                <option value="Jordan">Jordan</option>

                                                <option value="Kazakhstan">Kazakhstan</option>

                                                <option value="Kenya">Kenya</option>

                                                <option value="Kiribati">Kiribati</option>

                                                <option value="North Korea">North Korea</option>

                                                <option value="South Korea">South Korea</option>

                                                <option value="Kuwait">Kuwait</option>

                                                <option value="Kyrgyzstan">Kyrgyzstan</option>

                                                <option value="Laos">Laos</option>

                                                <option value="Latvia">Latvia</option>

                                                <option value="Lebanon">Lebanon</option>

                                                <option value="Lesotho">Lesotho</option>

                                                <option value="Liberia">Liberia</option>

                                                <option value="Libya">Libya</option>

                                                <option value="Liechtenstein">Liechtenstein</option>

                                                <option value="Lithuania">Lithuania</option>

                                                <option value="Luxembourg">Luxembourg</option>

                                                <option value="Macao">Macao</option>

                                                <option value="Republic of Macedonia">Republic of Macedonia</option>

                                                <option value="Madagascar">Madagascar</option>

                                                <option value="Malawi">Malawi</option>

                                                <option value="Malaysia">Malaysia</option>

                                                <option value="Maldives">Maldives</option>

                                                <option value="Mali">Mali</option>

                                                <option value="Malta">Malta</option>

                                                <option value="Marshall Islands">Marshall Islands</option>

                                                <option value="Martinique">Martinique</option>

                                                <option value="Mauritania">Mauritania</option>

                                                <option value="Mauritius">Mauritius</option>

                                                <option value="Mayotte">Mayotte</option>

                                                <option value="Mexico">Mexico</option>

                                                <option value="Federated States of Micronesia">Federated States of
                                                    Micronesia</option>

                                                <option value="Moldova">Moldova</option>

                                                <option value="Monaco">Monaco</option>

                                                <option value="Mongolia">Mongolia</option>

                                                <option value="Montenegro">Montenegro</option>

                                                <option value="Montserrat">Montserrat</option>

                                                <option value="Morocco">Morocco</option>

                                                <option value="Mozambique">Mozambique</option>

                                                <option value="Myanmar">Myanmar</option>

                                                <option value="Namibia">Namibia</option>

                                                <option value="Nauru">Nauru</option>

                                                <option value="Nepal">Nepal</option>

                                                <option value="Netherlands">Netherlands</option>

                                                <option value="New Caledonia">New Caledonia</option>

                                                <option value="New Zealand">New Zealand</option>

                                                <option value="Nicaragua">Nicaragua</option>

                                                <option value="Niger">Niger</option>

                                                <option value="Nigeria">Nigeria</option>

                                                <option value="Niue">Niue</option>

                                                <option value="Norfolk Island">Norfolk Island</option>

                                                <option value="Northern Mariana Islands">Northern Mariana Islands</option>

                                                <option value="Norway">Norway</option>

                                                <option value="Oman">Oman</option>

                                                <option value="Pakistan">Pakistan</option>

                                                <option value="Palau">Palau</option>

                                                <option value="Palestine">Palestine</option>

                                                <option value="Panama">Panama</option>

                                                <option value="Papua New Guinea">Papua New Guinea</option>

                                                <option value="Paraguay">Paraguay</option>

                                                <option value="Peru">Peru</option>

                                                <option value="Philippines">Philippines</option>

                                                <option value="Pitcairn">Pitcairn</option>

                                                <option value="Poland">Poland</option>

                                                <option value="Portugal">Portugal</option>

                                                <option value="Puerto Rico">Puerto Rico</option>

                                                <option value="Qatar">Qatar</option>

                                                <option value="Reunion">Reunion</option>

                                                <option value="Romania">Romania</option>

                                                <option value="Russian Federation">Russian Federation</option>

                                                <option value="Rwanda">Rwanda</option>

                                                <option value="Saint Barthelemy">Saint Barthelemy</option>

                                                <option value="Saint Helena">Saint Helena</option>

                                                <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>

                                                <option value="Saint Lucia">Saint Lucia</option>

                                                <option value="Saint Martin">Saint Martin</option>

                                                <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon
                                                </option>

                                                <option value="Saint Vincent and the Grenadines">Saint Vincent and the
                                                    Grenadines</option>

                                                <option value="Samoa">Samoa</option>

                                                <option value="San Marino">San Marino</option>

                                                <option value="Sao Tome and Principe">Sao Tome and Principe</option>

                                                <option value="Saudi Arabia">Saudi Arabia</option>

                                                <option value="Senegal">Senegal</option>

                                                <option value="Serbia">Serbia</option>

                                                <option value="Seychelles">Seychelles</option>

                                                <option value="Sierra Leone">Sierra Leone</option>

                                                <option value="Singapore">Singapore</option>

                                                <option value="Sint Maarten Dutch part">Sint Maarten Dutch part</option>

                                                <option value="Slovakia">Slovakia</option>

                                                <option value="Slovenia">Slovenia</option>

                                                <option value="Solomon Islands">Solomon Islands</option>

                                                <option value="Somalia">Somalia</option>

                                                <option value="South Africa">South Africa</option>

                                                <option value="South Georgia and the South Sandwich Islands">South Georgia
                                                    and the South Sandwich Islands</option>

                                                <option value="South Sudan">South Sudan</option>

                                                <option value="Spain">Spain</option>

                                                <option value="Sri Lanka">Sri Lanka</option>

                                                <option value="Sudan">Sudan</option>

                                                <option value="Suriname">Suriname</option>

                                                <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>

                                                <option value="Swaziland">Swaziland</option>

                                                <option value="Sweden">Sweden</option>

                                                <option value="Switzerland">Switzerland</option>

                                                <option value="Syrian Arab Republic">Syrian Arab Republic</option>

                                                <option value="Taiwan">Taiwan</option>

                                                <option value="Tajikistan">Tajikistan</option>

                                                <option value="Tanzania">Tanzania</option>

                                                <option value="Thailand">Thailand</option>

                                                <option value="East Timor">East Timor</option>

                                                <option value="Togo">Togo</option>

                                                <option value="Tokelau">Tokelau</option>

                                                <option value="Tonga">Tonga</option>

                                                <option value="Trinidad and Tobago">Trinidad and Tobago</option>

                                                <option value="Tunisia">Tunisia</option>

                                                <option value="Turkey">Turkey</option>

                                                <option value="Turkmenistan">Turkmenistan</option>

                                                <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>

                                                <option value="Tuvalu">Tuvalu</option>

                                                <option value="Uganda">Uganda</option>

                                                <option value="Ukraine">Ukraine</option>

                                                <option value="United Arab Emirates">United Arab Emirates</option>

                                                <option value="United Kingdom">United Kingdom</option>

                                                <option value="United States">United States</option>

                                                <option value="United States Minor Outlying Islands">United States Minor
                                                    Outlying Islands</option>

                                                <option value="Uruguay">Uruguay</option>

                                                <option value="Uzbekistan">Uzbekistan</option>

                                                <option value="Vanuatu">Vanuatu</option>

                                                <option value="Venezuela">Venezuela</option>

                                                <option value="Viet Nam">Viet Nam</option>

                                                <option value="British Virgin Islands">British Virgin Islands</option>

                                                <option value="United States Virgin Islands">United States Virgin Islands
                                                </option>

                                                <option value="Wallis and Futuna">Wallis and Futuna</option>

                                                <option value="Western Sahara">Western Sahara</option>

                                                <option value="Yemen">Yemen</option>

                                                <option value="Zambia">Zambia</option>

                                                <option value="Zimbabwe">Zimbabwe</option>

                                                <!-- Add more countries as needed -->
                                            </select>
                                            @error('billing_country')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">GST Number
                                                (Optional)</label>
                                            <input type="text" name="billing_gst_number"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_gst_number', Auth::user()->billing_gst_number ?? '') }}"
                                                maxlength="15">
                                            @error('billing_gst_number')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    {{-- @php
                        // Fetch product details and calculate total amount and discount
                        $denomination = $qsProd->prodData['denomination'];
                        $quantity = $qsProd->prodData['quantity'];
                        $discountPercentage = $qsProd->discount_percentage;

                        // Calculate total and discounted amounts
                        $totalAmount = $denomination * $quantity;
                        $discountAmount = $totalAmount * ($discountPercentage / 100);
                        $totalPayableAmountAfterDiscount = $totalAmount - $discountAmount;

                        // Store the values in the session
                        session([
                            'denomination' => $denomination,
                            'quantity' => $quantity,
                            'discount_percentage' => $discountPercentage,
                            'total_amount' => $totalAmount,
                            'discount_amount' => $discountAmount,
                            'total_payable_amount_after_discount' => $totalPayableAmountAfterDiscount,
                        ]);
                    @endphp --}}



                    <div class="col-lg-5 cart-item">
                        <div class="row justify-content-center">
                            <div class="card border-0 mb-4">
                                <div class="card-header" id="headingTwo">
                                    <div class="row">
                                        <div class="col-lg-12 col-sm-12">
                                            <div class="row order-data">
                                                <div class="mont-font col-md-6 col-sm-4 col-xs-6 order-summary">
                                                    <span>Order Summary</span>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xp-6">
                                                    <a href="{{ route('get-product-by-slug', ['slug' => $qsProd->slug]) }}"
                                                        class="float-right mont-font">Edit</a>
                                                </div>
                                            </div>
                                            <div class="row cart-item-record">
                                                <div class="col-md-5 col-sm-4 col-xs-12">
                                                    <img class="cart-coupan-img"
                                                        src="{{ $qsProd['images']->small ?? URL::asset('/images/hamburger.jpg') }}"
                                                        alt="Avatar" style="width:100%;">
                                                </div>
                                                <div class="col-md-7 col-sm-4 col-xs-12">
                                                    <span class="product-name mont-font">{{ $qsProd->name }}</span>
                                                    <div class="row item-qty-subtotal">
                                                        <div class="col-12">
                                                            <span>Denomination: ₹{{ $qsOrder->denomination }}</span>
                                                        </div>
                                                        <div class="col-12">
                                                            <span>Qty: {{ $qsOrder->quantity }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>

                                            <div class="row total-amount justify-content-center">
                                                <div class="row">
                                                    <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                        <span>Grand Total:</span>
                                                    </div>
                                                    <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                                        <span>₹{{ $qsOrder->grand_payable_amount }}</span>
                                                    </div>
                                                </div>

                                                <div class="row coupan-code-amount" id="discountDiv">
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font apply-coupan">
                                                        <span>Discount:</span>
                                                    </div>
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-3 amount mont-font apply-coupan-amount">
                                                        ₹{{ $qsOrder->discounted_amount_value }}
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                        <span>Payable Amount:</span>
                                                    </div>
                                                    <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                                        <span>₹{{ $qsOrder->amount_payable_after_discount }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-none border-0">
                            <input
                                class="mont-font w-100 p-3 mt-3 mb-3 font-xsss text-center text-white bg-current rounded-lg text-uppercase fw-600 ls-3"
                                type="submit" value="Place Order" id="placeOrder">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {


                @if (!auth()->check())
                    // Show login modal if user is not authenticated
                    setTimeout(function() {
                        $('#Modallogin').modal('show');
                    }, 1000);
                @endif


                let debounceTimeout;

                function updateSessionData() {

                    var formData = {
                        billing_name: $('input[name="billing_name"]').val(),
                        billing_email: $('input[name="billing_email"]').val(),
                        billing_tel: $('input[name="billing_tel"]').val(),
                        billing_zip: $('input[name="billing_zip"]').val(),
                        billing_address: $('input[name="billing_address"]').val(),
                        billing_address_two: $('input[name="billing_address_two"]').val(),
                        billing_city: $('input[name="billing_city"]').val(),
                        billing_state: $('input[name="billing_state"]').val(),
                        billing_country: $('input[name="billing_country"]').val(),
                        billing_gst_number: $('input[name="billing_gst_number"]').val(),
                        // Add other form fields as needed
                    };

                    // AJAX POST request to Laravel backend to update session data
                    $.ajax({
                        url: '{{ route('updateSessionData') }}', // Replace with your Laravel route
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('Session data updated successfully');
                        },
                        error: function(xhr, status, error) {
                            console.error('Error updating session data:', error);
                        }
                    });
                }

                // Debounced function
                function debouncedUpdateSessionData() {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(updateSessionData,
                        600); // Adjust the delay as needed (600ms in this example)
                }

                // Bind debouncedUpdateSessionData function to the input event of input fields
                $('input[name="billing_name"], input[name="billing_email"], input[name="billing_tel"], input[name="billing_zip"], input[name="billing_address"], input[name="billing_address_two"], input[name="billing_city"], input[name="billing_state"], input[name="billing_country"], input[name="billing_gst_number"]')
                    .on('input', function() {
                        debouncedUpdateSessionData();
                    });

                // Optionally bind updateSessionData function to form submit event
                $('form').submit(function(event) {
                    updateSessionData();
                });



                var storageData = JSON.parse(window.localStorage.getItem('data'));

                $("#checkoutForm").validate({
                    rules: {
                        billing_name: {
                            required: true,
                            lattersonly: true,
                            maxlength: 30
                        },
                        billing_email: {
                            required: true,
                            email: true,
                            maxlength: 50
                        },
                        billing_tel: {
                            required: true,
                            indianNumber: true,
                            minlength: 10,
                            maxlength: 10
                        },
                        billing_zip: {
                            required: true,
                            number: true,
                            minlength: 6,
                            maxlength: 6
                        },
                        billing_address: {
                            required: true,
                            maxlength: 50
                        },
                        billing_address_two: {
                            required: true,
                            maxlength: 50
                        },
                        billing_city: {
                            required: true,
                            maxlength: 40
                        },
                        billing_state: {
                            required: true,
                            maxlength: 40
                        },
                        billing_country: {
                            required: true,
                            maxlength: 40
                        },
                        billing_gst_number: {
                            maxlength: 15
                        }
                    },
                    messages: {
                        billing_name: {
                            required: "Please enter a name",
                            lattersonly: "Please enter a valid name",
                            maxlength: "Name cannot exceed 255 characters"
                        },
                        billing_email: {
                            required: "Please enter an email",
                            email: "Please enter a valid email",
                            maxlength: "Email cannot exceed 50 characters"
                        },
                        billing_tel: {
                            required: "Please enter your phone number",
                            indianNumber: "Please enter a valid Indian number",
                            minlength: "Phone number must be 10 digits",
                            maxlength: "Phone number must be 10 digits"
                        },
                        billing_zip: {
                            required: "Please enter your zip code",
                            number: "Please enter a valid zip code",
                            minlength: "Zip code must be 6 digits",
                            maxlength: "Zip code must be 6 digits"
                        },
                        billing_address: {
                            required: "Please enter your address",
                            maxlength: "Address cannot exceed 50 characters"
                        },
                        billing_address_two: {
                            required: "Please enter your address",
                            maxlength: "Address cannot exceed 50 characters"
                        },
                        billing_city: {
                            required: "Please enter your city",
                            maxlength: "City cannot exceed 40 characters"
                        },
                        billing_state: {
                            required: "Please enter your state",
                            maxlength: "State cannot exceed 40 characters"
                        },
                        billing_country: {
                            required: "Please enter your country",
                            maxlength: "Country cannot exceed 40 characters"
                        },
                        billing_gst_number: {
                            maxlength: "GST number cannot exceed 15 characters"
                        }
                    },
                    submitHandler: function(form) {
                        form.submit();
                    }
                });

                jQuery.validator.addMethod('lattersonly', function(value, element) {
                    return /^[a-zA-Z\s-]+$/.test(value);
                }, "Please enter a valid name");

                jQuery.validator.addMethod('indianNumber', function(value, element) {
                    return /^[6-9]\d{9}$/.test(value);
                }, "Please enter a valid Indian number");
            });
        </script>
    @endpush
@endsection
