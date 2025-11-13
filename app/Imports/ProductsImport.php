<?php

namespace App\Imports;

use App\Models\QsProduct;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
{
    // Try to find the product in QsProduct by SKU
    $product = QsProduct::where('sku', $row['sku'])->first();

    if ($product) {
        $product->update([
            'discount_percentage' => $row['discount_from_amazepay'],
        ]);

        Log::info("QsProduct ({$row['sku']}) discount updated successfully.");
        return null;
    }

    // If not found in QsProduct, try KgenProduct using ProductID
    $kgenproduct = \App\Models\KgenProduct::where('ProductID', $row['sku'])->first();

    if ($kgenproduct) {
        $kgenproduct->update([
            'discount_percentage' => $row['discount_from_amazepay'],
        ]);

        Log::info("KgenProduct ({$row['sku']}) discount updated successfully.");
        return null;
    }

    // Log if not found in either
    Log::warning("Product with SKU {$row['sku']} not found in QsProduct or KgenProduct.");

    return null;
}

}
