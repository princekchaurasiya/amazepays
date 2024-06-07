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


        // Find the existing product by SKU
        $product = QsProduct::where('sku', $row['sku'])->first();



        // If the product exists, update its discount_percentage
        if ($product) {
            $product->update([
                'discount_percentage' => $row['discount_from_amazepay'],

            ]);

            // Return null since we don't need to create a new model instance
            return null;

        }
        Log::info("Product discount percentage updated successfully");
        // Optionally, log or handle cases where the product is not found
        Log::warning("Product with SKU {$row['sku']} not found.");

        // If product does not exist, we don't return a model instance
        return null;
    }

}
