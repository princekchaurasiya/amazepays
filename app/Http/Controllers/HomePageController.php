<?php

namespace App\Http\Controllers;

use App\Models\Slide; // Import the Slide model
use App\Models\QsProduct; // Import the QsProduct model
use App\Models\Home; // Import the Home model
use App\Models\AmazepayCategory;
use App\Models\AmazepayBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomePageController extends Controller
{
    public function homePage()
    {
        try {
            // Fetch categories and brands
            $categories = AmazepayCategory::orderBy('order')->get();
            $brands = AmazepayBrand::orderBy('order')->get();

            // Fetch all products and decode necessary fields
            $allProducts = QsProduct::orderByRaw('IFNULL(priority, 999999) ASC')->get();

            $allProducts->map(function ($item) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images);
            });

            // Fetch home settings including priority_product_to_show
            $homeSettings = Home::first();
            $priorityProductLimit = $homeSettings->priority_product_to_show ?? 10; // Default to 10 if not set

            // Split the products into two groups: priority and no priority
            $priorityProducts = $allProducts->filter(function ($product) {
                return !is_null($product->priority);
            })->take($priorityProductLimit); // Limit the number of priority products

            $noPriorityProducts = $allProducts->filter(function ($product) {
                return is_null($product->priority);
            });

            // Fetch all slides
            $slides = Slide::where('status', 1) // Ensure the status is active
                ->where('display_on_page', 'homepage') // Filter based on display location
                ->orderBy('priority', 'asc') // Sort by priority
                ->orderByRaw('priority IS NULL ASC') // Ensure null priorities are last
                ->get();

            // Retrieve slugs for slides
            foreach ($slides as $slide) {
                $slide->slug = $this->getSlug($slide);
            }

            // Return the view with the fetched data
            return view('layouts/homepage.index', compact('slides', 'homeSettings', 'allProducts', 'categories', 'brands', 'priorityProducts', 'noPriorityProducts')); // Pass data to the view
        } catch (\Exception $e) {
            Log::error('Error fetching data in homePage method', ['error' => $e->getMessage()]);
            // Handle the exception as needed, e.g., return an error view or message
            return response()->view('errors.500', [], 500);
        }
    }

    // Method to retrieve the slug
    private function getSlug($slide)
    {
        if ($slide->product_id) {
            $product = QsProduct::find($slide->product_id);
            return $product ? $product->slug : null;
        } elseif ($slide->category_id) {
            $category = AmazepayCategory::find($slide->category_id);
            return $category ? $category->slug : null;
        } elseif ($slide->brand_id) {
            $brand = AmazepayBrand::find($slide->brand_id);
            return $brand ? $brand->slug : null;
        }

        return null; // Return null if no slug is found
    }
}
