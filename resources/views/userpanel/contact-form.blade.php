@extends('layouts.app')
@section('title')
Amazepay | Contact
@endsection
@section('content')


<div class="section">
            <div id="map" class="rounded-lg overflow-hidden" style="height: 150px;"></div>
                {{-- <script src="//maps.googleapis.com/maps/api/js?key=AIzaSyCOdKtT5fapH3_OfhV3HFeZjqFs4OfNIew&callback=mapinitialize" type="text/javascript"></script>
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
                </script> --}}
            </div>
        </div>

        <div class="map-wrapper pb-7">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="contact-wrap bg-white shadow-lg rounded-lg position-relative">
                            <h1 class="text-grey-900 fw-700 display3-size mb-5 lh-1">Contact us</h1>
                            <form action="#">
                                <div class="row">
                                    <div class="col-lg-6 col-md-12">
                                        <div class="form-group mb-3">
                                            <input type="text" class="form-control h60 bg-color-none text-grey-700" value="Name">                        
                                        </div>        
                                    </div>
                                    <div class="col-lg-6 col-md-12">
                                        <div class="form-group mb-3">
                                            <input type="text" class="form-control h60 bg-color-none text-grey-700" value="Email">                        
                                        </div>        
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group mb-3 md-mb25">
                                            <textarea class="w-100 h125 p-3 form-control">Message</textarea>
                                        </div>
                                        <div class="form-check text-left mt-3 float-left md-mb25">
                                            <input type="checkbox" class="form-check-input mt-2" id="exampleCheck1">
                                            <label class="form-check-label font-xsss text-grey-500 fw-500" for="exampleCheck1">I agree to the term of this <a href="#" class="text-grey-600 fw-600">Privacy Policy</a></label>
                                        </div>
                                        <a href="#" class="form-control rounded-lg h60 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w175">Submit</a>
                                    </div>
                                </div>
                                
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-12 offset-lg-1 col-xl-12 offset-xl-1">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 md-mb25">
                                
                                <!-- <i class="fa-solid fa-map-location-dot font-md float-left mr-3 contact-us"></i> -->
                                <h4 class="text-grey-900 fw-600 font-xl ls-2">Address</h4>
                                <h4 class="font-xsss lh-24 fw-500 text-grey-500 mt-4">98-103, 4 Floor, Aditya Industrial Estate Co-op Premises Ltd Mindspace Behind Evershine 
                                    Mall Off Link Road Malad (West)  <br/>Mumbai, Maharashtra 400064</h4>
                            </div>

                            <div class="col-lg-4 col-md-4 md-mb25">
                                <!-- <i class="fa-solid fa-map-location-dot font-md float-left mr-3 contact-us"></i> -->
                                <h4 class="text-grey-900 fw-600 font-xl ls-2">Email Us</h4>
                                <h5 class="font-xsss lh-24 fw-500 text-grey-500 mt-4 mb-0">support@amazepay.in</h5>
                            </div>

                            <div class="col-lg-4 col-md-4 md-mb25">
                                <!-- <i class="fa-solid fa-map-location-dot font-md float-left mr-3 contact-us"></i> -->
                                <h4 class="text-grey-900 fw-600 font-xl ls-2">Conatct Us</h4>
                                <h5 class="font-xsss lh-24 fw-500 text-grey-500 mt-0">+91-98211 99497</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@push('scripts')
  
@endpush