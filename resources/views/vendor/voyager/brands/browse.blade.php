@extends('voyager::master')

@section('content')
    <div class="page-content browse container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h1 class="panel-title">Brands</h1>
                    </div>
                    <div class="panel-body">
                        <a href="{{ route('brands.export') }}" class="btn btn-success" style="margin-bottom: 15px;">
                            <i class="voyager-download"></i> Export Brands to Excel
                        </a>
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    @foreach($dataType->browseRows as $row)
                                        <th>{{ $row->getTranslatedAttribute('display_name') }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dataTypeContent as $data)
                                    <tr>
                                        @foreach($dataType->browseRows as $row)
                                            @php
                                                $value = $data->{$row->field};
                                            @endphp
                                            <td>
                                                @if(is_array($value))
                                                    {{ json_encode($value) }}
                                                @elseif(is_string($value) && Str::startsWith($value, '['))
                                                    {{ implode(', ', json_decode($value, true) ?? []) }}
                                                @else
                                                    {{ Str::limit(strip_tags($value), 150) }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
