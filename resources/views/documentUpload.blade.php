@extends('layouts.app')
@section('title')
    <i class="fas fa-file-upload"></i> Document Upload
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 p-5">
                <div class="card">
                    <div class="card-header text-center" >
                        <h2 class="mb-0"><i class="fas fa-file-upload mr-2"></i>Upload your document</h2>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @elseif(session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                        <form action="{{ route('uploadData') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="input-group mb-3">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="document" name="document" aria-describedby="fileHelp">
                                    <label class="custom-file-label" for="document">Choose file</label>
                                </div>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload mr-2"></i> Import Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Show selected file name in the input field
        document.getElementById('document').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    </script>
@endpush
