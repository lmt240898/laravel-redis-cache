<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductFilterController extends Controller
{
    /**
     * Display the filter page with results.
     *
     * Filter criteria:
     *  1. category     - Dropdown
     *  2. brand        - Dropdown
     *  3. price_min / price_max - Price range
     *  4. rating       - Minimum rating (1-5)
     *  5. availability - in_stock / out_of_stock / all
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'brand', 'price_min', 'price_max', 'rating', 'availability']);
        $page = $request->get('page', 1);
        $cacheKey = "products:filter:" . md5(serialize($filters)) . ":page:{$page}";

        $startTime = microtime(true);
        $cacheHit = Cache::has($cacheKey);

        $products = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            $query = Product::query();

            $query->filterCategory($request->input('category'))
                  ->filterBrand($request->input('brand'))
                  ->filterPriceRange($request->input('price_min'), $request->input('price_max'))
                  ->filterRating($request->input('rating'))
                  ->filterAvailability($request->input('availability'));

            return $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();
        });

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);

        return view('products.filter', [
            'products'     => $products,
            'categories'   => Product::CATEGORIES,
            'brands'       => Product::BRANDS,
            'filters'      => $filters,
            'totalResults' => $products->total(),
            'queryTime'    => $queryTime,
            'cacheHit'     => $cacheHit,
        ]);
    }
}
