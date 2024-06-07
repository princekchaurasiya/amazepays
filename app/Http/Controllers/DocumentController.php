<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentController extends Controller
{
    public function uploadData(Request $request)
    {
        // Validate that the uploaded file is an xlsx file and its size is less than or equal to 2MB
        $request->validate([
            'document' => 'required|file|mimes:xlsx|max:2048',
        ]);

        try {
            // Get the uploaded file
            $file = $request->file('document');

            // Perform the import
            Excel::import(new ProductsImport(), $file);

            // Log successful upload
            Log::info('Data Imported successfully');

            // Flash success message
            return redirect()->back()->with('success', 'Data Imported successfully');
        } catch (Exception $e) {
            // Log the error
            Log::error('Error during upload: ' . $e->getMessage());

            // Flash error message
            return redirect()
                ->back()
                ->with('error', 'There was an error during upload: ' . $e->getMessage());
        }
    }
}
