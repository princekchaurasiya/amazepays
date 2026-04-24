<?php

namespace App\Http\Controllers;

use App\Imports\ProductsImport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class DocumentController extends Controller
{
    public function uploadData(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'document' => 'required|file|mimes:xlsx|max:2048',
            ], [
                'document.required' => 'Please select an Excel file to upload.',
                'document.file' => 'The uploaded file must be a valid file.',
                'document.mimes' => 'The uploaded file must be an Excel file with .xlsx extension.',
                'document.max' => 'The uploaded file size should not exceed 2MB.',
            ]);

            // Get the uploaded file
            $file = $request->file('document');

            // Check if file is provided
            if (! $file) {
                throw new Exception('File cannot be empty. Please select an Excel file to upload.');
            }

            // Perform the import
            Excel::import(new ProductsImport, $file);

            // Log successful upload
            Log::info('Data Imported successfully');

            // Flash success message
            return redirect()->back()->with('success', 'Data Imported successfully');
        } catch (Exception $e) {
            // Log the error
            Log::error('Error during upload: '.$e->getMessage());

            // Flash error message
            return redirect()
                ->back()
                ->with('error', 'There was an error during the upload: '.$e->getMessage());
        }
    }
}
