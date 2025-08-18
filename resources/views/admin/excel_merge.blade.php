@extends('voyager::master')

@section('content')
    <div class="container">
        <h3>Upload Two Excel Files to Merge</h3>

        @if(session('file'))
            <div class="alert alert-success">
                Merged file ready: 
                <a href="{{ session('file') }}" class="btn btn-success" download>Download Merged File</a>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('excel.merge') }}">
            @csrf
            <div class="form-group">
                <label>Excel File 1</label>
                <input type="file" name="file1" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Excel File 2</label>
                <input type="file" name="file2" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary mt-2">Merge & Generate File</button>
        </form>
    </div>
@if ($errors->any())
    <div class="alert alert-danger mt-2">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
