@extends('voyager::master')

@section('page_title', 'Woohoo API Management')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-cloud-download"></i> Woohoo API Management
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">API Commands</h3>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted">Click the buttons below to manually trigger Woohoo API commands. Each command will run in the background and you'll see the status below.</p>
                        
                        <div class="row equal-height-cards">
                            <!-- Generate Bearer Token -->
                            <div class="col-md-6 mb-4">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 220px; display: flex; flex-direction: column; justify-content: space-between;">
                                        <div>
                                            <h4><i class="voyager-key"></i> Generate Bearer Token</h4>
                                            <p class="text-muted">Generate a new bearer token for Woohoo API authentication</p>
                                        </div>
                                        <div>
                                            <button id="btn-generate-token" class="btn btn-primary btn-lg" data-command="bearer-token">
                                                <i class="voyager-key"></i> Generate Token
                                            </button>
                                            <div id="status-bearer-token" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Category Data -->
                            <div class="col-md-6 mb-4">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 220px; display: flex; flex-direction: column; justify-content: space-between;">
                                        <div>
                                            <h4><i class="voyager-categories"></i> Fetch Category Data</h4>
                                            <p class="text-muted">Fetch and update category data from Woohoo API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-category" class="btn btn-success btn-lg" data-command="category">
                                                <i class="voyager-categories"></i> Fetch Categories
                                            </button>
                                            <div id="status-category" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Product List -->
                            <div class="col-md-6 mb-4">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 220px; display: flex; flex-direction: column; justify-content: space-between;">
                                        <div>
                                            <h4><i class="voyager-list"></i> Fetch Product List</h4>
                                            <p class="text-muted">Fetch and update product list from Woohoo API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-product-list" class="btn btn-info btn-lg" data-command="product-list">
                                                <i class="voyager-list"></i> Fetch Product List
                                            </button>
                                            <div id="status-product-list" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetch Product Data -->
                            <div class="col-md-6 mb-4">
                                <div class="panel panel-default h-100">
                                    <div class="panel-body text-center" style="min-height: 220px; display: flex; flex-direction: column; justify-content: space-between;">
                                        <div>
                                            <h4><i class="voyager-data"></i> Fetch Product Data</h4>
                                            <p class="text-muted">Fetch and update detailed product data from Woohoo API</p>
                                        </div>
                                        <div>
                                            <button id="btn-fetch-product-data" class="btn btn-warning btn-lg" data-command="product-data">
                                                <i class="voyager-data"></i> Fetch Product Data
                                            </button>
                                            <div id="status-product-data" class="mt-2" style="min-height: 30px; line-height: 30px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Log Area -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Command Output</h3>
                                    </div>
                                    <div class="panel-body">
                                        <pre id="command-output" class="bg-dark text-light p-3" style="min-height: 200px; max-height: 400px; overflow-y: auto; border-radius: 4px;">Waiting for command execution...</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        route = '{{ route("admin.woohoo.generate-token") }}';
                        break;
                    case 'category':
                        route = '{{ route("admin.woohoo.fetch-category") }}';
                        break;
                    case 'product-list':
                        route = '{{ route("admin.woohoo.fetch-product-list") }}';
                        break;
                    case 'product-data':
                        route = '{{ route("admin.woohoo.fetch-product-data") }}';
                        break;
                }

                // Make AJAX request
                $.ajax({
                    url: route,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
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
                            toastr.error(response.message || 'Command failed');
                        }
                    },
                    error: function(xhr) {
                        button.prop('disabled', false);
                        button.html(originalText);
                        
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while executing the command';
                        updateStatus(commandName, 'error', errorMessage);
                        updateOutput(errorMessage, true);
                        toastr.error(errorMessage);
                    }
                });
            }

            // Bind click events
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
        });
    </script>
@stop
