<?php

/**
 * ProductHelper Usage Examples
 * 
 * This file contains examples of how to use ProductHelper in different scenarios.
 * You can use these patterns anywhere in your application.
 */

namespace App\Helpers;

use App\Models\QsProduct;

class ProductHelperUsageExamples
{
    /**
     * Example 1: Decode JSON fields from raw database data
     */
    public function example1_decodeRawData()
    {
        // When you have raw data (array or object) with JSON strings
        $rawProduct = [
            'price' => '{"type":"RANGE","min":"100","max":"10000"}',
            'currency' => '{"code":"INR","symbol":"₹"}',
            'images' => '["image1.jpg","image2.jpg"]',
        ];

        // Decode all fields
        $price = ProductHelper::decodePrice($rawProduct['price']);
        $currency = ProductHelper::decodeCurrency($rawProduct['currency']);
        $images = ProductHelper::decodeImages($rawProduct['images']);

        // Or process the entire product at once
        $processed = ProductHelper::processProductData($rawProduct);
    }

    /**
     * Example 2: Extract range information from price structure
     */
    public function example2_extractRange()
    {
        $price = [
            'cpg' => [
                '' => [
                    'type' => 'RANGE',
                    'min' => '100',
                    'max' => '10000',
                    'denominations' => ['100', '1000', '2000']
                ]
            ]
        ];

        // Extract range info
        $range = ProductHelper::extractRange($price);
        // Returns: ['min' => '100', 'max' => '10000', 'type' => 'RANGE', 'denominations' => [...]]

        // Or get individual values
        $minPrice = ProductHelper::getMinPrice($price);
        $maxPrice = ProductHelper::getMaxPrice($price);
        $type = ProductHelper::getPriceType($price);
        $denominations = ProductHelper::getDenominations($price);
    }

    /**
     * Example 3: Check price type and format display
     */
    public function example3_checkAndFormat()
    {
        $price = ['cpg' => ['' => ['type' => 'SLAB', 'min' => '500', 'max' => '1000', 'denominations' => ['500', '1000']]]];

        // Check price type
        if (ProductHelper::isSlabPricing($price)) {
            // Handle SLAB pricing
            $formatted = ProductHelper::getFormattedPriceRange($price);
            // Returns: "₹500, ₹1,000"
        }

        if (ProductHelper::isRangePricing($price)) {
            // Handle RANGE pricing
            $formatted = ProductHelper::getFormattedPriceRange($price);
            // Returns: "₹100 - ₹10,000"
        }
    }

    /**
     * Example 4: Use with Eloquent models
     */
    public function example4_withModels()
    {
        // Method 1: Use model methods (recommended - uses helper internally)
        $product = QsProduct::find(1);
        $minPrice = $product->getMinPrice();
        $maxPrice = $product->getMaxPrice();
        $formatted = $product->getFormattedPriceRange();

        // Method 2: Use helper directly with model's price
        $price = $product->price; // Already decoded by model accessor
        $range = ProductHelper::extractRange($price);
        $formatted = ProductHelper::getFormattedPriceRange($price);
    }

    /**
     * Example 5: Process multiple products
     */
    public function example5_processMultiple()
    {
        $products = QsProduct::all();

        // Process all products at once
        ProductHelper::processProducts($products);

        // Now all products have decoded price, currency, images
        foreach ($products as $product) {
            $price = $product->price; // Already decoded
            $images = $product->images; // Already decoded
        }
    }

    /**
     * Example 6: Use in controllers with raw data
     */
    public function example6_inControllers()
    {
        // When fetching from API or external source
        $apiResponse = [
            'price' => json_encode(['type' => 'RANGE', 'min' => '100', 'max' => '10000']),
            'currency' => json_encode(['code' => 'INR']),
        ];

        // Decode and extract info
        $price = ProductHelper::decodePrice($apiResponse['price']);
        $range = ProductHelper::extractRange($price);
        $formatted = ProductHelper::getFormattedPriceRange($price);

        return [
            'min_price' => $range['min'],
            'max_price' => $range['max'],
            'formatted_price' => $formatted,
        ];
    }

    /**
     * Example 7: Use in Blade templates
     */
    public function example7_inBlade()
    {
        // In your Blade template, you can use model methods:
        /*
        @if($product->isSlabPricing())
            <span>Available in: {{ $product->getFormattedPriceRange() }}</span>
        @elseif($product->isRangePricing())
            <span>Price Range: {{ $product->getFormattedPriceRange() }}</span>
        @endif
        */
    }
}
