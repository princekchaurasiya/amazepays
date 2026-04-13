<?php

namespace App\Imports;

use App\Models\KgenProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        // Try to find the product in Product by SKU
        $product = Product::where('sku', $row['sku'])->first();

        if ($product) {
            $product->update([
                'discount_percentage' => $row['discount_from_amazepay'],
            ]);

            Log::info("Product ({$row['sku']}) discount updated successfully.");

            return null;
        }

        // If not found in Product, try KgenProduct using ProductID
        $kgenproduct = KgenProduct::where('ProductID', $row['sku'])->first();

        if ($kgenproduct) {
            $kgenproduct->update([
                'discount_percentage' => $row['discount_from_amazepay'],
            ]);

            Log::info("KgenProduct ({$row['sku']}) discount updated successfully.");

            return null;
        }

        // Log if not found in either
        Log::warning("Product with SKU {$row['sku']} not found in Product or KgenProduct.");

        return null;
    }
}
