<?php

// app/Http/Controllers/ExcelMergeController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class ExcelMergeController2  extends Controller
{
    public function showForm()
    {
        return view('admin.excel_merge');
    }

    public function merge(Request $request)
    {
        try {
            /*$request->validate([
                'file1' => 'required|file|mimes:xlsx,xls',
                'file2' => 'required|file|mimes:xlsx,xls',
            ]);*/
            $request->validate([
                'file1' => 'required|file',
                'file2' => 'required|file',
                ]);
            
            $file1 = $request->file('file1')->getPathname();
            $file2 = $request->file('file2')->getPathname();

             $sheet1 = \PhpOffice\PhpSpreadsheet\IOFactory::load($file1)->getActiveSheet()->toArray(null, true, true, true);
             $sheet2 = \PhpOffice\PhpSpreadsheet\IOFactory::load($file2)->getActiveSheet()->toArray(null, true, true, true);
            
             $headers1 = array_values(array_shift($sheet1));
             $headers2 = array_values(array_shift($sheet2));

              $emailKey1 = array_search('email', array_map('strtolower', $headers1));
              $emailKey2 = array_search('email', array_map('strtolower', $headers2));
              if ($emailKey1 === false || $emailKey2 === false) {
            return back()->with('error', 'Email column not found in one of the files.');
                }
              $mergedHeaders = array_merge($headers1, array_diff($headers2, [$headers2[$emailKey2]]));

        // Build lookup tables
                $lookup1 = [];
                foreach ($sheet1 as $row) {
                    $row = array_values($row);
                    $email = strtolower($row[$emailKey1]);
                    $lookup1[$email] = $row;
                }
                $lookup2 = [];
                foreach ($sheet2 as $row) {
                    $row = array_values($row);
                    $email = strtolower($row[$emailKey2]);
                    $lookup2[$email] = $row;
                }
                $allEmails = array_unique(array_merge(array_keys($lookup1), array_keys($lookup2)));
                $mergedData = [$mergedHeaders];

                foreach ($allEmails as $email) {
                    $row1 = $lookup1[$email] ?? array_fill(0, count($headers1), '');
                    $row2 = $lookup2[$email] ?? array_fill(0, count($headers2), '');

                    // Remove duplicate email from second row
                    if (isset($lookup2[$email])) {
                        unset($row2[$emailKey2]);
                        $row2 = array_values($row2);
                    } else {
                        $row2 = array_fill(0, count($headers2) - 1, '');
                    }

                    $mergedRow = array_merge($row1, $row2);
                    $mergedData[] = $mergedRow;
                }

        // Write merged data to Excel
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->fromArray($mergedData);

                $fileName = 'merged_fullouter_' . time() . '.xlsx';
                $filePath = 'public/merged_excels/' . $fileName;
                Storage::makeDirectory('public/merged_excels');

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save(storage_path('app/' . $filePath));

                return back()->with('file', asset('storage/merged_excels/' . $fileName));

           }
           catch (\Throwable $e) {
        \Log::error("Merge error: " . $e->getMessage());
        return back()->with('error', 'Merge failed: ' . $e->getMessage());
    }      
    }
}
