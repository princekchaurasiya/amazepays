@extends('layouts.app')
@section('title')
Amazepay | Profile
@endsection
@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="dashboard-nav bg-white rounded-lg shadow-xs">
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                            <!-- <li class="d-block rounded-lg"><a href="dashboard.html"><i class="ti-package font-sm"></i><span> Dashboard</span></a></li> -->
                            <!-- <li class="d-block rounded-lg"><a href="stat.html"><i class="ti-pie-chart font-sm"></i><span> Stat</span></a></li> -->
                            <!-- <li class="d-block rounded-lg"><a href="email-box.html"><i class="ti-email font-sm"></i><span> Email</span></a></li> -->
                            <!-- <li class="d-block rounded-lg"><a href="message.html"><i class="ti-comments font-sm"></i><span> Message</span></a></li> -->
                            <!-- <li class="d-block rounded-lg"><a href="saved.html"><i class="ti-heart font-sm"></i><span> Bookmark</span></a></li> -->
                            <li class="d-block rounded-lg active"><a href="{{route('profile')}}"><i class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{route('myOrder')}}"><i class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg "><a href="{{route('change-password')}}"><i class="ti-lock font-sm"></i><span> Chnage Password</span></a></li>
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            <li class="d-block rounded-lg"><a href="#"><i class="ti-power-off font-sm"></i><span> Logout</span></a></li>

                            <div class="card d-none-md w-100 mt-3 shadow-none pt-0 border-0">
                                <div class="card-body b-r-15 overflow-hidden position-relative bg-lightblue rounded-lg p-4 z-index bg-no-repeat bg-image-right" style="background-image: url(https://via.placeholder.com/300x300.png); ">
                                    <h3 class="text-grey-700 font-md lh-2 fw-900 mb-3">Online <br>Recharge</h3>
                                    <a href="#" class="btn b-r-15 bg-white shadow-lg fw-700 font-xssss lh-30 w100 text-center text-grey-900">Buy Now</a>
                                </div>
                            </div>
                        </ul>
                    </div> 
                </div>

                <div class="col-lg-9">
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        <div class="row">
                            <div class="col-lg-4 offset-sm-4 text-center">
                                <figure class="avatar ml-auto mr-auto mb-0 mt-2 w100"><img src="https://via.placeholder.com/300x300.png" alt="image" class="shadow-sm rounded-lg w-100"></figure>
                                <h2 class="fw-900 font-sm text-grey-900 mt-3">Surfiya Zakir</h2>
                                <h4 class="text-grey-500 fw-500 mb-3 font-xsss mb-4">Brooklyn</h4>    
                                <!-- <a href="#" class="p-3 alert-primary text-primary font-xsss fw-500 mt-2 rounded-lg">Upload New Photo</a> -->
                            </div>
                        </div>  
                        <form action="#">
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">First Name</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Last Name</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Email</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Phone</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Country</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Address</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Twon / City</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Postcode</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <div class="card mt-3 border-0">
                                        <div class="card-body d-flex justify-content-between align-items-end p-0">
                                            <div class="form-group mb-0 w-100">
                                                <input type="file" name="file" id="file" class="input-file">
                                                <label for="file" class="rounded-lg text-center bg-white btn-tertiary js-labelFile p-4 w-100 border-dashed">
                                                <i class="ti-cloud-down large-icon mr-3 d-block"></i>
                                                <span class="js-fileName">Drag and drop or click to replace</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <label class="mont-font fw-600 font-xsss" for="comment-name">Description</label>
                                    <textarea class="form-control mb-0 p-3 h100 bg-greylight lh-16" rows="5" placeholder="Write your message..." spellcheck="false"></textarea>
                                </div>

                                <div class="col-lg-12 mb-5">
                                    <a href="#" class="bg-current text-center text-white font-xsss fw-600 p-3 w175 rounded-lg d-inline-block">Save</a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <h4 class="mb-4 font-xs fw-700 mont-font">Social Network</h4>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Facebook</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Twitter</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Linkedin</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Instagram</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-12 mb-5 mt-2">
                                    <a href="#" class="bg-current text-center text-white font-xsss fw-600 p-3 w175 rounded-lg d-inline-block">Save</a>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-lg-12">
                                    <h4 class="mb-4 font-xs fw-700 mont-font mt-3">Contact Information</h4>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Country</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">City</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Address</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Pincode</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>        
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <div id="map" class="rounded-lg overflow-hidden" style="height: 200px;"></div>
                                        <script src="//maps.googleapis.com/maps/api/js?key=AIzaSyCOdKtT5fapH3_OfhV3HFeZjqFs4OfNIew&callback=mapinitialize" type="text/javascript"></script>
                                        <script type="text/javascript">
                                            function mapinitialize() {
                                                var latlng = new google.maps.LatLng(-33.86938,151.104000);
                                                var myOptions = {
                                                    zoom: 12,
                                                    center: latlng,
                                                    scrollwheel: false,
                                                    scaleControl: false,
                                                    disableDefaultUI: true,
                                                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                                                    // Google Map Color Styles
                                                    styles: [{"featureType":"all","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"administrative.province","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.neighborhood","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"},{"color":"#cee9de"},{"saturation":"2"},{"weight":"0.80"}]},{"featureType":"poi.attraction","elementType":"geometry.fill","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#f5d6d6"}]},{"featureType":"road.highway","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.icon","stylers":[{"hue":"#ff0000"},{"visibility":"on"}]},{"featureType":"road.highway.controlled_access","elementType":"labels.text","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway.controlled_access","elementType":"labels.icon","stylers":[{"visibility":"on"},{"hue":"#0064ff"},{"gamma":"1.44"},{"lightness":"-3"},{"weight":"1.69"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road.local","elementType":"labels.text","stylers":[{"visibility":"simplified"},{"weight":"0.31"},{"gamma":"1.43"},{"lightness":"-5"},{"saturation":"-22"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"transit.line","elementType":"all","stylers":[{"visibility":"on"},{"hue":"#ff0000"}]},{"featureType":"transit.station.airport","elementType":"all","stylers":[{"visibility":"simplified"},{"hue":"#ff0045"}]},{"featureType":"transit.station.bus","elementType":"all","stylers":[{"visibility":"on"},{"hue":"#00d1ff"}]},{"featureType":"transit.station.bus","elementType":"labels.text","stylers":[{"visibility":"simplified"}]},{"featureType":"transit.station.rail","elementType":"all","stylers":[{"visibility":"simplified"},{"hue":"#00cbff"}]},{"featureType":"transit.station.rail","elementType":"labels.text","stylers":[{"visibility":"simplified"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#46bcec"},{"visibility":"on"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"weight":"1.61"},{"color":"#cde2e5"},{"visibility":"on"}]}]
                                                };
                                                var map = new google.maps.Map(document.getElementById("map"),myOptions);
                                                
                                                var image = "images/map-marker.png";
                                                var image = new google.maps.MarkerImage("images/map-marker.png", null, null, null, new google.maps.Size(50,50));
                                                var marker = new google.maps.Marker({
                                                    map: map, 
                                                    icon: image,
                                                    position: map.getCenter()
                                                });
                                                
                                                var contentString = '<b>Office</b><br>Streetname 13<br>50001 Sydney';
                                                var infowindow = new google.maps.InfoWindow({
                                                    content: contentString
                                                });
                                                            
                                                google.maps.event.addListener(marker, 'click', function() {
                                                    infowindow.open(map,marker);
                                                });
                                                                
                                                    
                                            }
                                            mapinitialize();
                                        </script>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-0 mt-2 pl-0">
                                    <a href="#" class="bg-current text-center text-white font-xsss fw-600 p-3 w175 rounded-lg d-inline-block">Save</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    
    <script>
        //   $(".dashboard-tab").height( $('.dashboard-nav').height() - 65 );
        //     $(".dashboard-tab").niceScroll({
        //         cursorcolor: "#999", // change cursor color in hex
        //   });
    </script>
    @endpush
@endsection