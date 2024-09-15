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
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i
                                class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg active"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('my-order') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('userLogOut') }}"><i
                                        class="ti-power-off font-sm"></i><span> Logout</span></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9">
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

                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        @if (Auth::check())
                            <!-- Check if user is authenticated -->
                            <form action="{{ route('update-profile') }}" method="POST" id="profileForm">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="name">Name</label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ Auth::user()->name }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="email">Email</label>
                                            <input name="email" class="form-control" value="{{ Auth::user()->email }}">
                                        </div>
                                    </div>
                                    {{-- <div class="col-lg-12 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="phone">Phone</label>
                                            <input type="text" name="phone" class="form-control"
                                                value="{{ Auth::user()->mobile }}">
                                        </div>
                                    </div> --}}
                                    <div class="col-lg-12 mb-5">
                                        <button type="submit"
                                            class="form-control rounded-lg h20 float-left bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w100">Update</button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {
                // @if (session('login_required'))
                //     // Automatically trigger the login modal on page load
                //     $('#Modallogin').modal('show');

                //     // Redirect to unauthorized page when the modal is closed
                //     $('#Modallogin').on('hidden.bs.modal', function() {
                //         window.location.href = "{{ route('unauthorized') }}";
                //     });
                // @endif



                $.validator.addMethod("regex", function(value, element, regexp) {
                    var re = new RegExp(regexp);
                    return this.optional(element) || re.test(value);
                }, "Please check your input.");

                $('#profileForm button[type="submit"]').on('click', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    $("#profileForm").validate({
                        rules: {
                            name: {
                                required: true,
                                maxlength: 30,
                                regex: /^[a-zA-Z\s]*$/
                            },
                            email: {
                                required: true,
                                email: true
                            },
                            // phone: {
                            //     required: true,
                            //     digits: true,
                            //     maxlength: 10,
                            // }
                        },
                        messages: {
                            name: {
                                required: "Please enter your name",
                                maxlength: "Your name must not exceed 30 characters",
                                regex: "Special characters are not allowed in the name"
                            },
                            email: {
                                required: "Please enter your email",
                                email: "Please enter a valid email address"
                            },
                            // phone: {
                            //     required: "Please enter your phone number",
                            //     digits: "Please enter a valid phone number",
                            //     maxlength: "Your phone number must not exceed 10 digits"
                            // }
                        },
                        submitHandler: function(form) {
                            form.submit(); // Submit the form if validation is successful
                        }
                    });

                    $("#profileForm").submit(); // Trigger form validation and submission
                });
            });
        </script>
    @endpush
@endsection
