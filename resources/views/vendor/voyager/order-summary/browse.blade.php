@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing') . ' ' . $dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
        </h1>
        @can('add', app($dataType->model_name))
            <a href="{{ route('voyager.' . $dataType->slug . '.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete', app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
        @can('edit', app($dataType->model_name))
            @if (!empty($dataType->order_column) && !empty($dataType->order_display_column))
                <a href="{{ route('voyager.' . $dataType->slug . '.order') }}" class="btn btn-primary btn-add-new">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
        @endcan
        @can('delete', app($dataType->model_name))
            @if ($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes"
                    data-toggle="toggle" data-on="{{ __('voyager::bread.soft_deletes_off') }}"
                    data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach ($actions as $action)
            @if (method_exists($action, 'massAction'))
                @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
            @endif
        @endforeach
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        @if ($isServerSide)
                            <form method="get" class="form-search">
                                <div id="search-input">
                                    <div class="col-2">
                                        <select id="search_key" name="key">
                                            @foreach ($searchNames as $key => $name)
                                                <option value="{{ $key }}"
                                                    @if ($search->key == $key || (empty($search->key) && $key == $defaultSearchKey)) selected @endif>{{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <select id="filter" name="filter">
                                            <option value="contains" @if ($search->filter == 'contains') selected @endif>
                                                {{ __('voyager::generic.contains') }}</option>
                                            <option value="equals" @if ($search->filter == 'equals') selected @endif>=
                                            </option>
                                        </select>
                                    </div>
                                    <div class="input-group col-md-12">
                                        <input type="text" class="form-control"
                                            placeholder="{{ __('voyager::generic.search') }}" name="s"
                                            value="{{ $search->value }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info btn-lg" type="submit">
                                                <i class="voyager-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                @if (Request::has('sort_order') && Request::has('order_by'))
                                    <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                                    <input type="hidden" name="order_by" value="{{ Request::get('order_by') }}">
                                @endif
                            </form>
                        @endif
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        @if ($showCheckboxColumn)
                                            <th class="dt-not-orderable">
                                                <input type="checkbox" class="select_all">
                                            </th>
                                        @endif
                                        @foreach ($dataType->browseRows as $row)
                                            <th>
                                                @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                    <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                @endif
                                                {{ $row->getTranslatedAttribute('display_name') }}
                                                @if ($isServerSide)
                                                    @if ($row->isCurrentSortField($orderBy))
                                                        @if ($sortOrder == 'asc')
                                                            <i class="voyager-angle-up pull-right"></i>
                                                        @else
                                                            <i class="voyager-angle-down pull-right"></i>
                                                        @endif
                                                    @endif
                                                    </a>
                                                @endif
                                            </th>
                                        @endforeach
                                        <th class="actions text-right dt-not-orderable">
                                            {{ __('voyager::generic.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataTypeContent as $data)
                                        <tr>
                                            @if ($showCheckboxColumn)
                                                <td>
                                                    <input type="checkbox" name="row_id"
                                                        id="checkbox_{{ $data->getKey() }}" value="{{ $data->getKey() }}">
                                                </td>
                                            @endif
                                            @foreach ($dataType->browseRows as $row)
                                                @php
                                                    if ($data->{$row->field . '_browse'}) {
                                                        $data->{$row->field} = $data->{$row->field . '_browse'};
                                                    }
                                                @endphp

                                                <td>

                                                    @if ($row->field === 'summary_status')
                                                        {{-- Custom logic for summary_status --}}
                                                        @if ($data->summary_status === 'complete')
                                                            <span class="badge badge-success badge-padding">Complete</span>
                                                        @elseif ($data->summary_status === 'resend')
                                                            {{-- Resend Order Button --}}
                                                            <button type="button" class="badge badge-warning badge-padding"
                                                                id="resendOrderBtn" data-order-id="{{ $data->order_id }}"
                                                                data-toggle="modal" data-target="#confirmResendModal">
                                                                Resend Order
                                                            </button>
                                                        @else
                                                            <span class="badge badge-danger badge-padding">Incomplete</span>
                                                        @endif
                                                    @else
                                                        @switch($row->type)
                                                            @case('image')
                                                                <img src="@if (!filter_var($data->{$row->field}, FILTER_VALIDATE_URL)) {{ Voyager::image($data->{$row->field}) }} @else{{ $data->{$row->field} }} @endif"
                                                                    style="width:100px">
                                                            @break

                                                            @case('text')
                                                            @case('text_area')
                                                                <div>
                                                                    {{ mb_strlen($data->{$row->field}) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}
                                                                </div>
                                                            @break

                                                            @case('select_multiple')
                                                                @foreach ($data->{$row->field} as $item)
                                                                    {{ $item->{$row->field} . (!$loop->last ? ', ' : '') }}
                                                                @endforeach
                                                            @break

                                                            @default
                                                                {{ $data->{$row->field} }}
                                                        @endswitch
                                                    @endif
                                                </td>
                                            @endforeach


                                            <td class="no-sort no-click bread-actions">
                                                @foreach ($actions as $action)
                                                    @if (!method_exists($action, 'massAction'))
                                                        @include('voyager::bread.partials.actions', [
                                                            'action' => $action,
                                                        ])
                                                    @endif
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">
                                    {{ trans_choice('voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total(),
                                    ]) }}
                                </div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                        's' => $search->value,
                                        'filter' => $search->filter,
                                        'key' => $search->key,
                                        'order_by' => $orderBy,
                                        'sort_order' => $sortOrder,
                                        'showSoftDeleted' => $showSoftDeleted,
                                    ])->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }}
                        {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                            value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right"
                        data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->


    <!-- Confirmation Modal -->
    <!-- Modal Structure -->
    <!-- Modal Structure -->
    <div class="modal modal-warning fade" tabindex="-1" id="confirmResendModal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="{{ __('voyager::generic.close') }}" id="closeBtn">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">
                        <i class="voyager-refresh"></i> Resend Order Confirmation
                    </h4>
                </div>
                <div class="modal-body">
                    <p id="confirmationText" class="confirmationText">Are you sure you want to resend the order?</p>
                    <p id="waitingText" class="waitingText" style="display: none;">Please wait while we resend the
                        order...</p>

                    <div class="loader" id="resendLoader" style="display: none;">
                        <img src="{{ asset('images/Circles-menu-3.gif') }}" alt="Loading..."
                            style="width: 40px; height: 40px; margin: 10px auto; display: block;">
                    </div>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('resend.order') }}" id="resendOrderForm" method="POST">
                        {{ csrf_field() }}
                        <input type="hidden" name="order_id" id="order_id_input">
                        <button type="submit" class="btn btn-warning pull-right" id="confirmResendBtn">
                            Yes, Resend Order
                        </button>
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal"
                        id="cancelBtn">Cancel</button>
                </div>
            </div>
        </div>
    </div>




@stop

@section('css')
    @if (!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
    @endif
    <style>
        .badge-padding {
            font-weight: 800;
            padding: 0.4rem;
            border-radius: 25px;
            border: 0;
        }



        #resendLoader {
            display: none;
            position: relative;
        }




        #resendLoader img {
            width: 100px;
            height: 100px;
            margin: 10px auto;
            display: block;
        }

        #resendLoader.loader:after {
            content: none;
        }


        .waitingText {
            font-style: italic;
            text-align: center;
            margin-top: 10px;
            color: #555;
        }


        .confirmationText {
            font-weight: bold;
            /* Makes text bold */
            font-size: 1.2rem;
            /* Increases font size */
            color: #856404;
            /* Dark yellow color for the text */
            background-color: #fff3cd;
            /* Light yellow background color */
            padding: 10px;
            /* Adds padding for better spacing */
            border-radius: 5px;
            /* Rounded corners */
            border: 1px solid #ffeeba;
            /* Light yellow border */
            margin: 0;
            /* Removes default margin */
        }
    </style>

@stop

@section('javascript')
    <!-- DataTables -->
    @if (!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script>
        $(document).ready(function() {
            @if (!$dataType->server_side)
                var table = $('#dataTable').DataTable({!! json_encode(
                    array_merge(
                        [
                            'order' => $orderColumn,
                            'language' => __('voyager::datatable'),
                            'columnDefs' => [['targets' => 'dt-not-orderable', 'searchable' => false, 'orderable' => false]],
                        ],
                        config('voyager.dashboard.data_tables', []),
                    ),
                    true,
                ) !!});
            @else
                $('#search-input select').select2({
                    minimumResultsForSearch: Infinity
                });
            @endif

            @if ($isModelTranslatable)
                $('.side-body').multilingual();
                //Reinitialise the multilingual features when they change tab
                $('#dataTable').on('draw.dt', function() {
                    $('.side-body').data('multilingual').init();
                })
            @endif
            $('.select_all').on('click', function(e) {
                $('input[name="row_id"]').prop('checked', $(this).prop('checked')).trigger('change');
            });



        });


        $(document).on('click', '#resendOrderBtn', function() {
            var orderId = $(this).data('order-id'); // Extract the order ID from the data attribute
            console.log(orderId); // Check the console for the order ID

            // Set the order ID in the hidden input of the modal
            $('#order_id_input').val(orderId);

            // Show the modal
            $('#confirmResendModal').modal('show');
        });

        document.getElementById('confirmResendBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent form submission on click

            // Disable the resend button to prevent multiple clicks
            this.disabled = true;
            this.textContent = "Processing...";

            // Hide the confirmation text and cancel button
            document.getElementById('confirmationText').style.display = 'none';
            document.getElementById('cancelBtn').style.display = 'none';
            document.getElementById('closeBtn').style.display = 'none';

            // Show the waiting message and loader
            document.getElementById('waitingText').style.display = 'block';
            document.getElementById('resendLoader').style.display = 'block';

            // Submit the form after a short delay
            setTimeout(() => {
                document.getElementById('resendOrderForm').submit();
            }, 500); // Adjust the delay as needed
        });




        var deleteFormAction;
        $('td').on('click', '.delete', function(e) {
            $('#delete_form')[0].action = '{{ route('voyager.' . $dataType->slug . '.destroy', '__id') }}'.replace(
                '__id', $(this).data('id'));
            $('#delete_modal').modal('show');
        });

        @if ($usesSoftDeletes)
            @php
                $params = [
                    's' => $search->value,
                    'filter' => $search->filter,
                    'key' => $search->key,
                    'order_by' => $orderBy,
                    'sort_order' => $sortOrder,
                ];
            @endphp
            $(function() {
                $('#show_soft_deletes').change(function() {
                    if ($(this).prop('checked')) {
                        $('#dataTable').before(
                            '<a id="redir" href="{{ route('voyager.' . $dataType->slug . '.index', array_merge($params, ['showSoftDeleted' => 1]), true) }}"></a>'
                        );
                    } else {
                        $('#dataTable').before(
                            '<a id="redir" href="{{ route('voyager.' . $dataType->slug . '.index', array_merge($params, ['showSoftDeleted' => 0]), true) }}"></a>'
                        );
                    }

                    $('#redir')[0].click();
                })
            })
        @endif
        $('input[name="row_id"]').on('change', function() {
            var ids = [];
            $('input[name="row_id"]').each(function() {
                if ($(this).is(':checked')) {
                    ids.push($(this).val());
                }
            });
            $('.selected_ids').val(ids);
        });
    </script>
@stop
