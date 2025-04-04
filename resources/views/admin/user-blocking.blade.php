@extends('voyager::master')

@section('page_title', 'User Restriction Management')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-lock"></i> User Restriction Management
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Restrict User</h3>
                    </div>
                    <div class="panel-body">
                        <form id="restrict-form" class="form-edit-add">
                            <div class="form-group">
                                <label for="mobile">Mobile Number</label>
                                <input type="text" class="form-control" id="mobile" name="mobile" required pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number">
                            </div>
                            <div class="form-group">
                                <label for="restriction_type">Restriction Type</label>
                                <select class="form-control" id="restriction_type" name="restriction_type" required>
                                    <option value="">Select Restriction Type</option>
                                    <option value="login">Login Block</option>
                                    <option value="transaction">Transaction Block</option>
                                    <option value="feature">Feature Restriction</option>
                                </select>
                            </div>
                            <div class="form-group feature-options" style="display: none;">
                                <label>Restricted Features</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="features[]" value="checkout"> Checkout
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="features[]" value="payment"> Payment
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reason">Restriction Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Restriction</button>
                        </form>
                    </div>
                </div>

                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Remove Restriction</h3>
                    </div>
                    <div class="panel-body">
                        <form id="unrestrict-form" class="form-edit-add">
                            <div class="form-group">
                                <label for="unrestrict_mobile">Mobile Number</label>
                                <input type="text" class="form-control" id="unrestrict_mobile" name="mobile" required pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number">
                            </div>
                            <div class="form-group">
                                <label for="unrestrict_type">Restriction Type</label>
                                <select class="form-control" id="unrestrict_type" name="restriction_type" required>
                                    <option value="">Select Restriction Type</option>
                                    <option value="login">Login Block</option>
                                    <option value="transaction">Transaction Block</option>
                                    <option value="feature">Feature Restriction</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning">Remove Restriction</button>
                        </form>
                    </div>
                </div>

                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Restricted Users</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Restrictions</th>
                                    <th>Reason</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody id="restricted-users">
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $(document).ready(function () {
            // Show/hide feature options based on restriction type
            $('#restriction_type').change(function() {
                if ($(this).val() === 'feature') {
                    $('.feature-options').show();
                } else {
                    $('.feature-options').hide();
                }
            });

            // Load restricted users
            function loadRestrictedUsers() {
                $.get('{{ route("admin.check.user.restrictions") }}', function(response) {
                    console.log('Response:', response); // Debug log
                    if (response.status === 'success' && response.users) {
                        let html = '';
                        if (response.users.length === 0) {
                            html = '<tr><td colspan="5" class="text-center">No restricted users found</td></tr>';
                        } else {
                            response.users.forEach(function(user) {
                                let restrictions = [];
                                if (user.is_blocked) restrictions.push('Login Blocked');
                                if (!user.can_transact) restrictions.push('Transaction Blocked');
                                if (user.restricted_features) {
                                    try {
                                        let features = typeof user.restricted_features === 'string' 
                                            ? JSON.parse(user.restricted_features) 
                                            : user.restricted_features;
                                        if (Array.isArray(features) && features.length > 0) {
                                            restrictions.push('Features: ' + features.join(', '));
                                        }
                                    } catch (e) {
                                        console.error('Error parsing restricted features:', e);
                                    }
                                }
                                
                                html += `
                                    <tr>
                                        <td>${user.name || 'N/A'}</td>
                                        <td>${user.mobile || 'N/A'}</td>
                                        <td>${restrictions.length > 0 ? restrictions.join('<br>') : 'None'}</td>
                                        <td>${user.restriction_reason || 'No reason provided'}</td>
                                        <td>${user.updated_at || 'N/A'}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#restricted-users').html(html);
                    } else {
                        console.error('Invalid response:', response); // Debug log
                        toastr.error('Failed to load restricted users');
                        $('#restricted-users').html('<tr><td colspan="5" class="text-center">Error loading data</td></tr>');
                    }
                }).fail(function(error) {
                    console.error('Error:', error);
                    toastr.error('Failed to load restricted users');
                    $('#restricted-users').html('<tr><td colspan="5" class="text-center">Error loading data</td></tr>');
                });
            }

            // Apply restriction
            $('#restrict-form').submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                if ($('#restriction_type').val() === 'feature') {
                    let features = $('input[name="features[]"]:checked').map(function() {
                        return $(this).val();
                    }).get();
                    if (features.length === 0) {
                        toastr.error('Please select at least one feature to restrict');
                        return;
                    }
                    formData += '&features=' + JSON.stringify(features);
                }

                $.post('{{ route("admin.block.user") }}', formData, function(response) {
                    if (response.status === 'success') {
                        toastr.success('Restriction applied successfully');
                        loadRestrictedUsers();
                        $('#restrict-form')[0].reset();
                        $('.feature-options').hide();
                    } else {
                        toastr.error(response.message || 'Failed to apply restriction');
                    }
                }).fail(function(error) {
                    console.error('Error:', error);
                    toastr.error('Failed to apply restriction');
                });
            });

            // Remove restriction
            $('#unrestrict-form').submit(function(e) {
                e.preventDefault();
                $.post('{{ route("admin.unblock.user") }}', $(this).serialize(), function(response) {
                    if (response.status === 'success') {
                        toastr.success('Restriction removed successfully');
                        loadRestrictedUsers();
                        $('#unrestrict-form')[0].reset();
                    } else {
                        toastr.error(response.message || 'Failed to remove restriction');
                    }
                }).fail(function(error) {
                    console.error('Error:', error);
                    toastr.error('Failed to remove restriction');
                });
            });

            // Initial load of restricted users
            loadRestrictedUsers();
        });
    </script>
@stop 