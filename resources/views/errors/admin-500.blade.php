@extends('voyager::master')

@section('page_title', '500 Internal Server Error')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-warning"></i>
        500 Internal Server Error
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="panel panel-bordered">
            <div class="panel-body">
                <div class="text-center" style="padding: 40px 20px;">
                    <h2>Oops! Something went wrong</h2>
                    <p class="text-muted" style="margin-top: 20px; font-size: 16px;">
                        We're sorry, but something went wrong on our end. We've been notified and are working to fix the issue.
                    </p>
                    <p class="text-muted" style="margin-top: 10px;">
                        Please try again later. If the problem persists, please contact the system administrator.
                    </p>
                    <div style="margin-top: 30px;">
                        <a href="{{ route('voyager.dashboard') }}" class="btn btn-primary">
                            <i class="voyager-home"></i> Go to Dashboard
                        </a>
                        <a href="javascript:history.back()" class="btn btn-default">
                            <i class="voyager-arrow-left"></i> Go Back
                        </a>
                    </div>
                    @if(config('app.debug') && isset($exception))
                        <div style="margin-top: 40px; padding: 20px; background: #f5f5f5; border-radius: 4px; text-align: left;">
                            <h4 style="color: #d9534f;">Debug Information:</h4>
                            <p><strong>Error:</strong> {{ $exception->getMessage() }}</p>
                            <p><strong>File:</strong> {{ $exception->getFile() }}</p>
                            <p><strong>Line:</strong> {{ $exception->getLine() }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
