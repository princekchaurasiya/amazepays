@extends('voyager::master')

@section('page_title', 'API Management')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-cloud-download"></i> API Management
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="row" style="align-items: flex-start;">
            <!-- Left Column: API Commands -->
            <div class="col-md-8">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">API Commands</h3>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted">Click the buttons below to manually trigger API commands. Each command will run in the background and you'll see the status in the output panel on the right.</p>
                        
                        <!-- Woohoo API Section -->
                        <h4 class="mt-4 mb-3"><i class="voyager-cloud-download"></i> Woohoo API</h4>
                        <div class="row equal-height-cards">
                            <!-- Generate Bearer Token -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-key"></i> Generate Bearer Token</h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Generate a new bearer token for Woohoo API authentication</p>
                                        </div>
                                        <div>
                                            <button id="btn-generate-token" class="btn btn-primary btn-sm" data-command="bearer-token" style="font-size: 12px; padding: 6px 12px;">
                                                <i class="voyager-key"></i> Generate Token
                                            </button>
                                            <div id="status-bearer-token" class="mt-2" style="min-height: 20px; line-height: 20px; font-size: 12px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Category Data -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-categories"></i> Fetch Category Data</h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and update category data from Woohoo API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-category" class="btn btn-success btn-sm" data-command="category" style="font-size: 12px; padding: 6px 12px;">
                                                <i class="voyager-categories"></i> Fetch Categories
                                            </button>
                                            <div id="status-category" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Product List -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-list"></i> Fetch Product List</h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and update product list from Woohoo API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-product-list" class="btn btn-info btn-sm" data-command="product-list">
                                                <i class="voyager-list"></i> Fetch Product List
                                            </button>
                                            <div id="status-product-list" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Product Data -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-data"></i> Fetch Product Data</h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and update detailed product data from Woohoo API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-product-data" class="btn btn-warning btn-sm" data-command="product-data">
                                                <i class="voyager-data"></i> Fetch Product Data
                                            </button>
                                            <div id="status-product-data" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KGen API Section -->
                        <h4 class="mt-4 mb-3"><i class="voyager-bag"></i> KGen API</h4>
                        <div class="row equal-height-cards">
                            <!-- Fetch KGen Products -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-list"></i> Fetch KGen Products                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and store all KGen products from the API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-kgen-products" class="btn btn-primary btn-sm" data-command="kgen-products">
                                                <i class="voyager-list"></i> Fetch KGen Products
                                            </button>
                                            <div id="status-kgen-products" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Featured KGen Products -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-star"></i> Fetch Featured KGen Products                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and store featured KGen products for homepage (limit: 8)</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-featured-kgen-products" class="btn btn-success btn-sm" data-command="featured-kgen-products">
                                                <i class="voyager-star"></i> Fetch Featured Products
                                            </button>
                                            <div id="status-featured-kgen-products" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch KGen Wallet Balance -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-wallet"></i> Fetch KGen Wallet Balance                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and store current KGen wallet balance from API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-kgen-wallet-balance" class="btn btn-primary btn-sm" data-command="kgen-wallet-balance">
                                                <i class="voyager-wallet"></i> Fetch Wallet Balance
                                            </button>
                                            <div id="status-kgen-wallet-balance" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Value Design API Section -->
                        <h4 class="mt-4 mb-3"><i class="voyager-star"></i> Value Design API</h4>
                        <div class="row equal-height-cards">
                            <!-- Fetch VD Brands -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-star"></i> Fetch VD Brands                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch and store all Value Design brands from the API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-vd-brands" class="btn btn-primary btn-sm" data-command="vd-brands">
                                                <i class="voyager-star"></i> Fetch VD Brands
                                            </button>
                                            <div id="status-vd-brands" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sync VD Stores -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-shop"></i> Sync VD Stores                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Sync store data for all Value Design brands</p>
                                        </div>
                                        <div>
                                            <button id="btn-sync-vd-stores" class="btn btn-success btn-sm" data-command="vd-stores">
                                                <i class="voyager-shop"></i> Sync VD Stores
                                            </button>
                                            <div id="status-vd-stores" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Get VD Wallet Balance -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-wallet"></i> Get VD Wallet Balance                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Get current wallet balance from Value Design API</p>
                                        </div>
                                        <div>
                                            <button id="btn-get-vd-wallet-balance" class="btn btn-info btn-sm" data-command="vd-wallet-balance">
                                                <i class="voyager-wallet"></i> Get Wallet Balance
                                            </button>
                                            <div id="status-vd-wallet-balance" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lysto (Athena Gift Card) API Section -->
                        <h4 class="mt-4 mb-3"><i class="voyager-gift"></i> Lysto (Athena Gift Card) API</h4>
                        <div class="row equal-height-cards">
                            <!-- Fetch Lysto Gift Cards -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-gift"></i> Fetch Lysto Gift Cards                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Fetch gift cards list from Lysto (Athena) API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-lysto-gift-cards" class="btn btn-success btn-sm" data-command="lysto-gift-cards">
                                                <i class="voyager-gift"></i> Fetch Gift Cards
                                            </button>
                                            <div id="status-lysto-gift-cards" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Get Lysto Wallet Balance -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-wallet"></i> Get Lysto Wallet Balance                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Get current wallet balance from Lysto (Athena) API</p>
                                        </div>
                                        <div>
                                            <button id="btn-get-lysto-wallet-balance" class="btn btn-success btn-sm" data-command="lysto-wallet-balance">
                                                <i class="voyager-wallet"></i> Get Wallet Balance
                                            </button>
                                            <div id="status-lysto-wallet-balance" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Export Section -->
                        <h4 class="mt-4 mb-3"><i class="voyager-download"></i> Data Export</h4>
                        <div class="row equal-height-cards">
                            <!-- Export User Payment Details (Joined) -->
                            <div class="col-md-4 mb-3">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 160px; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
                                        <div>
                                            <h5 style="font-size: 14px; margin-bottom: 8px;"><i class="voyager-download"></i> Export User & Payment Details                                            </h5>
                                            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Export joined data: Users + Payment Details (Unlimit Payments) by email</p>
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.api.export-user-payment-details') }}" class="btn btn-success btn-sm" id="btn-export-user-payment-details" style="font-size: 12px; padding: 6px 12px;">
                                                <i class="voyager-download"></i> Export Excel
                                            </a>
                                            <div class="mt-2" style="min-height: 30px; line-height: 30px;">
                                                <small class="text-muted">Downloads Excel file with joined data</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Note about other features -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5><i class="voyager-info-circled"></i> Additional Features</h5>
                                    <p class="mb-1"><strong>Value Design View Pages (Admin Only):</strong></p>
                                    <ul class="mb-1">
                                        <li><strong>Show Brands:</strong> <a href="{{ route('admin.vd.brands') }}" target="_blank">View Brands</a> - Browse all stored brands</li>
                                        <li><strong>Filter Stores:</strong> <a href="{{ route('admin.stores.filter') }}" target="_blank">Filter Stores</a> - Filter and search stores</li>
                                        <li><strong>VD Dashboard:</strong> <a href="{{ route('admin.vd.dashboard') }}" target="_blank">VD Dashboard</a> - Value Design operations dashboard</li>
                                    </ul>
                                    <p class="mb-1"><strong>EVC Operations:</strong> EVC Request and Status require order details. Use the <a href="{{ route('admin.evc.request') }}" target="_blank">EVC Request</a> and <a href="{{ route('admin.evc.form') }}" target="_blank">EVC Status</a> pages for these operations.</p>
                                    <p class="mb-1"><strong>KGen Wallet:</strong> View wallet balance history in <a href="{{ url('/admin/k-gen-wallet-balances') }}" target="_blank">KGen Wallet Balances</a> (Voyager BREAD) or check <a href="{{ route('admin.kgen.transactions.index') }}" target="_blank">KGen Transactions</a>.</p>
                                    <p class="mb-0"><strong>Lysto (Athena Gift Card):</strong> All operations return JSON responses. Use API Management buttons above to fetch gift cards and wallet balance. Access <a href="{{ route('admin.lysto.dashboard') }}" target="_blank">Dashboard</a> for testing (Admin Only).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Fixed Command Output -->
            <div class="col-md-4">
                <div class="panel panel-bordered command-output-fixed">
                    <div class="panel-heading">
                        <h3 class="panel-title">Command Output</h3>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <pre id="command-output" class="bg-dark text-light p-3">Waiting for command execution...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .equal-height-cards {
            display: flex;
            flex-wrap: wrap;
        }
        .equal-height-cards > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }
        .equal-height-cards .panel {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .equal-height-cards .panel-body {
            flex: 1;
        }
        /* Compact card styling */
        .equal-height-cards .panel-body h5 {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .equal-height-cards .panel-body p.text-muted {
            font-size: 12px;
            margin-bottom: 0;
            line-height: 1.3;
        }
        .equal-height-cards .btn-sm {
            font-size: 12px;
            padding: 6px 12px;
            white-space: nowrap;
        }
        /* Fixed output panel */
        #command-output {
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        /* Fixed command output panel - truly fixed, never scrolls, aligned with API Commands */
        .command-output-fixed {
            position: fixed !important;
            top: 167px;
            right: 30px;
            width: calc(33.333% - 45px);
            max-width: 400px;
            max-height: calc(100vh - 180px);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .command-output-fixed .panel-heading {
            flex-shrink: 0;
            background: #fff;
            z-index: 1001;
        }
        .command-output-fixed .panel-body {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .command-output-fixed #command-output {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            margin: 0;
            border-radius: 0;
            font-size: 12px;
            line-height: 1.4;
            min-height: 0;
        }
        /* Ensure left column doesn't overlap with fixed panel */
        @media (min-width: 992px) {
            .col-md-8 {
                padding-right: calc(33.333% + 20px);
            }
        }
    </style>
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            // Function to update status
            function updateStatus(command, status, message) {
                const statusDiv = $('#status-' + command);
                const icon = status === 'success' ? '✓' : status === 'error' ? '✗' : '⟳';
                const className = status === 'success' ? 'text-success' : status === 'error' ? 'text-danger' : 'text-info';
                
                statusDiv.html(`<span class="${className}"><strong>${icon}</strong> ${message}</span>`);
            }

            // Function to update output
            function updateOutput(message, isError = false) {
                const outputDiv = $('#command-output');
                const timestamp = new Date().toLocaleTimeString();
                const className = isError ? 'text-danger' : 'text-success';
                const prefix = isError ? '[ERROR]' : '[INFO]';
                
                outputDiv.append(`<span class="${className}">[${timestamp}] ${prefix} ${message}</span>\n`);
                outputDiv.scrollTop(outputDiv[0].scrollHeight);
            }

            // Function to execute command
            function executeCommand(command, buttonId) {
                const button = $(buttonId);
                const originalText = button.html();
                const commandName = button.data('command');
                
                // Disable button and show loading
                button.prop('disabled', true);
                button.html('<i class="voyager-refresh"></i> Processing...');
                updateStatus(commandName, 'processing', 'Processing...');
                updateOutput(`Starting ${command} command...`);

                // Determine the route based on command
                let route = '';
                switch(command) {
                    case 'bearer-token':
                        route = '{{ route("admin.api.generate-bearer-token") }}';
                        break;
                    case 'category':
                        route = '{{ route("admin.api.fetch-category") }}';
                        break;
                    case 'product-list':
                        route = '{{ route("admin.api.fetch-product-list") }}';
                        break;
                    case 'product-data':
                        route = '{{ route("admin.api.fetch-product-data") }}';
                        break;
                    case 'kgen-products':
                        route = '{{ route("admin.api.fetch-kgen-products") }}';
                        break;
                    case 'featured-kgen-products':
                        route = '{{ route("admin.api.fetch-featured-kgen-products") }}';
                        break;
                    case 'vd-brands':
                        route = '{{ route("admin.api.fetch-vd-brands") }}';
                        break;
                    case 'vd-stores':
                        route = '{{ route("admin.api.sync-vd-stores") }}';
                        break;
                    case 'vd-wallet-balance':
                        route = '{{ route("admin.api.get-vd-wallet-balance") }}';
                        break;
                    case 'kgen-wallet-balance':
                        route = '{{ route("admin.api.fetch-kgen-wallet-balance") }}';
                        break;
                    case 'lysto-gift-cards':
                        route = '{{ route("admin.api.fetch-lysto-gift-cards") }}';
                        break;
                    case 'lysto-wallet-balance':
                        route = '{{ route("admin.api.get-lysto-wallet-balance") }}';
                        break;
                }

                // Make AJAX request
                $.ajax({
                    url: route,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: command === 'featured-kgen-products' ? { limit: 8 } : {},
                    success: function(response) {
                        button.prop('disabled', false);
                        button.html(originalText);
                        
                        if (response.status === 'success') {
                            updateStatus(commandName, 'success', response.message);
                            updateOutput(response.message);
                            if (response.output) {
                                updateOutput(response.output);
                            }
                            toastr.success(response.message);
                        } else {
                            updateStatus(commandName, 'error', response.message || 'Failed');
                            updateOutput(response.message || 'Command failed', true);
                            if (response.output) {
                                updateOutput(response.output, true);
                            }
                            toastr.error(response.message || 'Command failed');
                        }
                    },
                    error: function(xhr) {
                        button.prop('disabled', false);
                        button.html(originalText);
                        
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while executing the command';
                        updateStatus(commandName, 'error', errorMessage);
                        updateOutput(errorMessage, true);
                        if (xhr.responseJSON?.output) {
                            updateOutput(xhr.responseJSON.output, true);
                        }
                        toastr.error(errorMessage);
                    }
                });
            }

            // Bind click events for Woohoo API
            $('#btn-generate-token').click(function() {
                executeCommand('bearer-token', this);
            });

            $('#btn-fetch-category').click(function() {
                executeCommand('category', this);
            });

            $('#btn-fetch-product-list').click(function() {
                executeCommand('product-list', this);
            });

            $('#btn-fetch-product-data').click(function() {
                executeCommand('product-data', this);
            });

            // Bind click events for KGen API
            $('#btn-fetch-kgen-products').click(function() {
                executeCommand('kgen-products', this);
            });

            $('#btn-fetch-featured-kgen-products').click(function() {
                executeCommand('featured-kgen-products', this);
            });

            // Bind click events for Value Design API
            $('#btn-fetch-vd-brands').click(function() {
                executeCommand('vd-brands', this);
            });

            $('#btn-sync-vd-stores').click(function() {
                executeCommand('vd-stores', this);
            });

            $('#btn-get-vd-wallet-balance').click(function() {
                executeCommand('vd-wallet-balance', this);
            });

            // Bind click events for KGen Wallet Balance
            $('#btn-fetch-kgen-wallet-balance').click(function() {
                executeCommand('kgen-wallet-balance', this);
            });

            // Bind click events for Lysto API
            $('#btn-fetch-lysto-gift-cards').click(function() {
                executeCommand('lysto-gift-cards', this);
            });

            $('#btn-get-lysto-wallet-balance').click(function() {
                executeCommand('lysto-wallet-balance', this);
            });
        });
    </script>
@stop
