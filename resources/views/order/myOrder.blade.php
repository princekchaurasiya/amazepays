@extends('layouts.app')
@section('title')
    Amazepay | My Order
@endsection
@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="dashboard-nav bg-white rounded-lg shadow-xs">
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i
                                class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg active"><a href="{{ route('myOrder') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            <li class="d-block rounded-lg"><a href="#"><i class="ti-power-off font-sm"></i><span>
                                        Logout</span></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-md-12"><span>Order Id</span></div>
                                    <div class="col-md-12">123</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Tracking Id</div>
                                    <div class="col-md-12">123</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-md-12"><span>Product Name</span></div>
                                    <div class="col-md-12">CNI IN</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Amount</div>
                                    <div class="col-md-12">₹10000</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-md-12"><span>Tracking Id</span></div>
                                    <div class="col-md-12">123123123</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Card Status</div>
                                    <div class="col-md-12">Completed</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-md-12"><span></span></div>
                                    <div class="col-md-12">123</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Tracking Id</div>
                                    <div class="col-md-12">123</div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-md-12">Order Id</div>
                                    <div class="col-md-12">123</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Tracking Id</div>
                                    <div class="col-md-12">123</div>
                                </div>
                            </div>
                            <div class="col-md-3">1232</div>
                            <div class="col-md-3">1232</div>
                            <div class="col-md-3">1232</div>
                        </div>
                        <hr>
                        <table class="table table-bordered">
                            <thead class="text-center">
                                <tr>

                                    <th>Product Name</th>
                                    <th>Refernce No</th>
                                    <th>Order Id</th>
                                    <th>Order Status</th>
                                    <th>Amount</th>
                                    <th>% Off</th>
                                </tr>
                            </thead>
                            <tbody>


                                {{-- @foreach ($orders as $item)
                                    <tr>
                                        {{dd($item)}}
                                        <td>{{ $item->product }}</td>
                                        @php
                                            $cards = json_decode($item->cards, true);
                                        @endphp
                                        {{dd($cards)}}
                                        <td>{{ $cards->sku }}</td>
                                        <td>{{ $item->order_status }}</td>
                                    </tr>
                                @endforeach --}}

                                {{-- @foreach ($orders as $item)
                                    <tr>
                                        <td>{{ $item->product }}</td>
                                        @php
                                            $cards = json_decode($item->cards, true);
                                        @endphp
                                        <td>{{ $cards['code'] }}</td>
                                        <td>{{ $cards['balance'] }}</td>
                                        <td>{{ $item->order_status }}</td>
                                    </tr>
                                @endforeach --}}
                                {{-- {{ dd($orders['product']) }}
                                @foreach ($orders['product'] as $product)
                                    Member ID: {{ $product['code'] }}
                                    Firstname: {{ $product['balance'] }}
                                @endforeach --}}

                                {{-- {{ dd($orders) }} --}}

                                {{-- @foreach ($orders as $item)
                                    <tr>
                                        <td>{{ $item['id'] }}</td>
                                        <td>{{ $item['reference_id'] }}</td>
                                        <td>{{ $item['order_id'] }}</td>
                                        <td>{{ $item['order_status'] }}</td>
                                    </tr>
                                    @php
                                        $products = json_decode($item['product'], true);
                                    @endphp
                                    @foreach ($products as $product)
                                        <tr>
                                            <td>{{ $product['code'] }}</td>
                                            <td>{{ $product['balance'] }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach --}}

                                @foreach ($orders as $item)
                               
                                   
                                    {{-- {{dd($product->code)}} --}}
                                        {{-- @foreach ($cardDetails as $details) --}}
                                            <tr class="text-center">
                                                <td>{{ $item['productName'] }}</td>
                                                <td>{{ $item['name'] }}</td>
                                                <td>{{ $item['reference_id'] }}</td>
                                                <td>{{ $item['order_id'] }}</td>
                                                <td>{{ $item['order_status'] }}</td>
                                                @foreach ($item['product'] as $product)
                                                    <td>{{ $product->code }}</td>
                                                    <td>{{ $product->balance }}</td>
                                                @endforeach

                                            </tr>
                                        {{-- @endforeach --}}
                                    
                                @endforeach
                                {{-- @foreach ($orders as $item)
                                    @php
                                        $products = json_decode($item['product'], true);
                                        $cardDetails = json_decode($item['cards'], true);
                                    @endphp
                                    @foreach ($products as $product)
                                        @php
                                            $recipientDetails = $product['recipientDetails'];
                                        @endphp
                                        <tr class="text-center">
                                            <td>{{ $product['productName'] }}</td>
                                            <td>{{ $item['reference_id'] }}</td>
                                            <td>{{ $item['order_id'] }}</td>
                                            <td>{{ $item['order_status'] }}</td>
                                            <td>{{ $product['cardNumber'] }}</td>
                                            <td>{{ $product['cardPin'] }}</td>
                                            <td>{{ $recipientDetails['name'] }}</td>
                                            <!-- Access other recipientDetails properties here -->
                                        </tr>
                                    @endforeach --}}
                                {{-- @endforeach --}}


                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script></script>
    @endpush
@endsection
