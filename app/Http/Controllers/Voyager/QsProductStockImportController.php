<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use App\Models\QsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class QsProductStockImportController extends Controller
{
    public function uploadDisabledProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid file uploaded', 'errors' => $validator->errors()], 422);
        }

        $path = $request->file('file')->getRealPath();

        try {
            $sheets = Excel::toArray([], $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Excel parse failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to read Excel file'], 500);
        }

        if (empty($sheets) || empty($sheets[0])) {
            return response()->json(['message' => 'Excel file is empty'], 422);
        }

        $rows = $sheets[0];

        // Normalize headers
        $header = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, array_shift($rows));

        $skuIndex = array_search('sku', $header, true);
        $nameIndex = array_search('product name', $header, true);
        $commentIndex = array_search('comments', $header, true);

        if ($skuIndex === false) {
            return response()->json(['message' => 'SKU column is required in the sheet'], 422);
        }

        $updated = 0;
        $notFound = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sku = isset($row[$skuIndex]) ? trim((string) $row[$skuIndex]) : '';
            $name = $nameIndex !== false && isset($row[$nameIndex]) ? trim((string) $row[$nameIndex]) : '';
            $comment = $commentIndex !== false && isset($row[$commentIndex]) ? trim((string) $row[$commentIndex]) : null;

            // Try updating by SKU if present
            if ($sku !== '') {
                $product = QsProduct::where('sku', $sku)->first();
                if (!$product && $name !== '') {
                    // Fallback: try by product name if SKU not found
                    $product = QsProduct::where('name', $name)->first();
                }
            } else {
                // No SKU provided: try by product name if available
                $product = $name !== '' ? QsProduct::where('name', $name)->first() : null;
            }

            if ($product) {
                $product->out_of_stock = true;
                $product->out_of_stock_comment = $comment;
                $product->save();
                $updated++;
            } else {
                // Preserve existing response message format (SKUs list). Only push SKU when present.
                if ($sku !== '') {
                    $notFound[] = $sku;
                }
            }
        }

        return response()->json([
            'message' => 'Upload processed successfully',
            'updated' => $updated,
            'not_found' => $notFound,
        ]);
    }
}


