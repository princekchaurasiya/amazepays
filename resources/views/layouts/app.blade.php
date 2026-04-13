<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title> @yield('title')</title>
        @yield('css')
        @include('layouts.partials.css-links')
        
        <style>
            .verify-email {
                margin-top: 10px;
                width: 100%;
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            /* Working time ribbon */
            .working-ribbon {
                background:rgb(253, 13, 13,0.5);
                color: #fff;
                width: 100%;
                overflow: hidden;
                white-space: nowrap;
                position: relative;
                z-index: 1030; /* above navbar backgrounds */
            }
            .working-ribbon-inner {
                display: inline-block;
                padding: 8px 0;
                animation: ribbon-marquee 18s linear infinite;
            }
            .working-ribbon-text {
                font-weight: 600;
                letter-spacing: 0.3px;
            }
            @keyframes ribbon-marquee {
                0% { transform: translateX(100%); }
                100% { transform: translateX(-100%); }
            }
            
            .verify-email:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            }
            
            .alert {
                border-radius: 10px;
                border: none;
            }
            
            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border-left: 4px solid #28a745;
            }
            
            .alert-info {
                background-color: #d1ecf1;
                color: #0c5460;
                border-left: 4px solid #17a2b8;
            }
            
            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border-left: 4px solid #dc3545;
            }
            .position-relative { position: relative; }
            .stock-badge {
                position: absolute;
                top: 12px;
                left: 12px;
                background: rgba(0,0,0,0.7);
                color: #fff;
                padding: 6px 10px;
                font-size: 12px;
                border-radius: 4px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
        </style>
    </head>
    <body class="color-theme-blue open-font bg-gray-50 antialiased">
        <div class="container-fluid p-0">
            <div class="preloader"></div>
            <div class="main-wrapper">
                @include('layouts.partials.navbar')
                @if(request()->routeIs('home'))
                @endif
            </div>
            <main class="min-h-[50vh]">
                @yield('content')
            </main>
            @include('layouts.partials.footer')
        </div>
        @include('layouts.partials.auth-modal')

        @include('layouts.partials.script-links')
        @include('layouts.partials.app-inline-scripts')
        @stack('scripts')

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Account Restricted</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Error content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:{{ config('app.support_email', 'support@amazepay.com') }}" class="btn btn-primary">Contact Support</a>
                </div>
            </div>
        </div>
    </div>

    </body>
</html>
