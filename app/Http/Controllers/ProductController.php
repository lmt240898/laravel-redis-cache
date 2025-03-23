<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProductController extends Controller
{
    /**
     * Display product listing with pagination.
     *
     * CASE 1: Cache-Aside Pattern + TTL
     * - Check cache first, if miss → query DB → store in cache
     *
     * CASE 4: Cache Stampede Prevention (Mutex Lock)
     * - When cache miss, only ONE request queries DB (via lock)
     * - Other requests wait briefly then read from cache
     */
    public function index(Request $request)
    {

        $page = $request->get('page', 1);
        $cacheKey = "products:page:{$page}";

        $startTime = microtime(true);

        $cacheHit = Cache::has($cacheKey);

        $products = Cache::get($cacheKey);

        if ($products !== null) {
            // Cache HIT — return immediately
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);
            return view('products.index', compact('products', 'queryTime', 'cacheHit'));
        }

        // Cache Miss — acquire lock to prevent stampede
        $lock = Cache::lock("lock:{$cacheKey}", 10);
        if ($lock->get()) {
            try {
                $products = Cache::get($cacheKey);

                if ($products === null) {
                    $products = Product::orderBy('created_at', 'desc')->paginate(15);
                    Cache::put($cacheKey, $products, now()->addMinutes(10));
                }
            } finally {
               $lock->release();
            }
        } else {
            // Lock bị request khác giữ — chờ 500ms rồi đọc cache
            usleep(500000);
            $products = Cache::get($cacheKey);

            // Fallback: nếu vẫn chưa có (trường hợp hiếm), query DB trực tiếp
            if ($products === null) {
                $products = Product::orderBy('created_at', 'desc')->paginate(15);
            }
        }
       
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);

        return view('products.index', compact('products', 'queryTime', 'cacheHit'));
    }

    /**
     * Show product detail page.
     *
     * CASE 5 — RACE CONDITION: Atomic View Counter
     * -----------------------------------------------
     * VẤN ĐỀ: Nếu dùng PHP đọc → tăng → ghi:
     *   $views = Redis::get("views");  // User A đọc: 100
     *                                  // User B cũng đọc: 100 (cùng lúc)
     *   Redis::set("views", $views+1); // User A ghi: 101
     *                                  // User B ghi: 101 (mất 1 lượt!)
     *
     * GIẢI PHÁP: Redis::incr() — atomic, đọc-tăng-ghi trong 1 lệnh duy nhất
     *   Redis::incr("views");  // Luôn chính xác dù 1000 request đồng thời
     */
    public function show(Product $product)
    {
        // Atomic increment — Redis thực hiện trong 1 lệnh, không ai chen được
        $views = Redis::incr("product:{$product->id}:views");

        // Cache product detail (Case 1: Cache-Aside)
        $product = Cache::remember("product:{$product->id}", now()->addMinutes(30), function () use ($product) {
            return $product->fresh();
        });

        return view('products.show', compact('product', 'views'));
    }

    /**
     * Purchase a product (decrement stock).
     *
     * CASE 5 — RACE CONDITION: Atomic Stock Decrement (Lua Script)
     * ---------------------------------------------------------------
     * VẤN ĐỀ: Nếu dùng PHP check → decrement:
     *   $stock = Redis::get("stock");   // User A đọc: 1
     *   if ($stock > 0) {               // User A: true
     *                                   // User B cũng đọc: 1, cũng true
     *       Redis::decr("stock");       // User A giảm: 0
     *   }                               // User B cũng giảm: -1  ← OVERSOLD!
     *
     * GIẢI PHÁP: Lua Script — gộp check + decrement thành 1 thao tác atomic
     *   Redis chạy Lua script từ đầu đến cuối mà không bị request khác chen vào
     *   → Check stock > 0 VÀ giảm stock trong cùng 1 lệnh → không bao giờ oversold
     *
     * TẠI SAO KHÔNG DÙNG Redis::decr()?
     *   Redis::decr() chỉ giảm, KHÔNG check điều kiện
     *   → stock có thể giảm xuống -1, -2... (oversold)
     *   Lua Script = check + decrement trong 1 thao tác atomic
     */
    public function purchase(Request $request, Product $product)
    {
        $stockKey = "product:{$product->id}:stock";

        // Đảm bảo Redis có stock data (sync từ DB lần đầu)
        if (Redis::get($stockKey) === null) {
            Redis::set($stockKey, $product->stock);
        }

        // Lua Script: check stock > 0 → decrement → return remaining
        // Toàn bộ script chạy ATOMIC trong Redis server
        $script = <<<'LUA'
            local stock = tonumber(redis.call('GET', KEYS[1]))
            if stock and stock > 0 then
                redis.call('DECRBY', KEYS[1], 1)
                return stock - 1
            end
            return -1
        LUA;

        $remainingStock = Redis::eval($script, 1, $stockKey);

        if ($remainingStock < 0) {
            return back()->with('error', 'Out of stock!');
        }

        // Sync stock xuống DB (eventual consistency)
        $product->decrement('stock');

        // Invalidate product cache (Case 2: Event-based Invalidation)
        try {
            Cache::forget("product:{$product->id}");
            $this->clearProductListCache();
        } catch (\Exception $e) {
            Log::warning("Cache invalidation failed after purchase", [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return back()->with('success', "Purchased! {$remainingStock} items remaining.");
    }

    /**
     * Show the create product form.
     */
    public function create()
    {
        return view('products.create', [
            'categories' => Product::CATEGORIES,
            'brands'     => Product::BRANDS,
        ]);
    }

    /**
     * Store a new product.
     *
     * CASE 2: Cache Invalidation (Event-Based)
     * CASE 3: Data Consistency — DB update first, cache delete after (try-catch)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category'    => 'required|string|in:' . implode(',', array_keys(Product::CATEGORIES)),
            'brand'       => 'required|string|in:' . implode(',', array_keys(Product::BRANDS)),
            'stock'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
            'rating'      => 'required|numeric|min:0|max:5',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Product::create($validated);

        try {
            $this->clearProductListCache();
        } catch (\Exception $e) {
            Log::warning("Cache invalidation failed", [
                'action' => 'create',
                'error'      => $e->getMessage(),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show the edit product form.
     */
    public function edit(Product $product)
    {
        return view('products.edit', [
            'product'    => $product,
            'categories' => Product::CATEGORIES,
            'brands'     => Product::BRANDS,
        ]);
    }

    /**
     * Update an existing product.
     *
     * CASE 2: Cache Invalidation — forget product detail + list cache
     * CASE 3: Data Consistency — DB first, cache after, try-catch safety
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category'    => 'required|string|in:' . implode(',', array_keys(Product::CATEGORIES)),
            'brand'       => 'required|string|in:' . implode(',', array_keys(Product::BRANDS)),
            'stock'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
            'rating'      => 'required|numeric|min:0|max:5',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $product->update($validated);
        try {
            Cache::forget("product:{$product->id}");
            $this->clearProductListCache();
        } catch (\Exception $e) {
            Log::warning("Cache invalidation failed", [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Delete a product.
     *
     * CASE 2: Cache Invalidation
     * CASE 3: Data Consistency
     */
    public function destroy(Product $product)
    {
        $product->delete();
        try {
            Cache::forget("product:{$product->id}");
            $this->clearProductListCache();
        } catch (\Exception $e) {
            Log::warning("Cache invalidation failed", [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Clear paginated product list cache.
     * Covers first 10 pages which handle most traffic.
     */
    private function clearProductListCache(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Cache::forget("products:page:{$i}");
        }
    }
}

