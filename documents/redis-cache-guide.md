# Redis Cache Guide - Laravel Real-World Cases
> Tài liệu tracking & hướng dẫn toàn diện về Redis Cache trong Laravel

---

## 📋 Mục lục
1. [Redis là gì & Tại sao cần Cache?](#1-redis-là-gì--tại-sao-cần-cache)
2. [Cấu hình Redis trong Laravel](#2-cấu-hình-redis-trong-laravel)
3. [Cache Patterns cơ bản](#3-cache-patterns-cơ-bản)
4. [Real-World Cases](#4-real-world-cases)
5. [Các vấn đề & giải pháp](#5-các-vấn-đề--giải-pháp)
6. [Redis nâng cao trong Laravel](#6-redis-nâng-cao-trong-laravel)
7. [Monitoring & Debugging](#7-monitoring--debugging)
8. [Checklist triển khai](#8-checklist-triển-khai)

---

## 1. Redis là gì & Tại sao cần Cache?

### Redis là gì?
- In-memory data store (lưu trữ dữ liệu trên RAM)
- Hỗ trợ nhiều kiểu dữ liệu: String, Hash, List, Set, Sorted Set
- Tốc độ đọc/ghi cực nhanh (~100,000 ops/giây)
- Persistence: có thể lưu xuống disk (RDB, AOF)

### Tại sao cần Cache?
- **Giảm tải database**: Tránh truy vấn lặp đi lặp lại
- **Tăng tốc response time**: Từ ~200ms (query DB) → ~1ms (đọc Redis)
- **Scale horizontally**: Redis Cluster cho hệ thống lớn
- **Giảm chi phí infrastructure**: Ít DB connections hơn

### Khi nào KHÔNG nên cache?
- Data thay đổi liên tục (mỗi request đều khác)
- Data nhạy cảm (thông tin cá nhân, payment)
- Lượng truy cập thấp (cache overhead > benefit)

---

## 2. Cấu hình Redis trong Laravel

### 2.1. File `.env`
```env
CACHE_DRIVER=redis
REDIS_HOST=redis          # tên service trong docker-compose
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis      # hoặc predis

# Optional: Redis cho session & queue
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2.2. File `config/database.php` - Redis connections
```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '0'),
    ],

    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),  // DB riêng cho cache
    ],

    'session' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => '2',  // DB riêng cho session
    ],
],
```

### 2.3. File `config/cache.php`
```php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',  // dùng connection 'cache' ở trên
        'lock_connection' => 'default',
    ],
],
```

---

## 3. Cache Patterns cơ bản

### 3.1. Cache-Aside (Lazy Loading) ⭐ Phổ biến nhất
```
Request → Kiểm tra Cache → HIT? → Trả về data
                          → MISS? → Query DB → Lưu Cache → Trả về data
```

```php
// Laravel implementation
$products = Cache::remember('products:all', 3600, function () {
    return Product::all();
});
```

**Ưu điểm**: Chỉ cache data thực sự được request
**Nhược điểm**: Request đầu tiên luôn chậm (cold start)

### 3.2. Write-Through
```
Write Request → Ghi DB → Ghi Cache → Response
```

```php
// Khi tạo/update product
$product = Product::create($data);
Cache::put("product:{$product->id}", $product, 3600);
Cache::forget('products:all');  // invalidate list cache
```

**Ưu điểm**: Cache luôn có data mới nhất
**Nhược điểm**: Write chậm hơn (ghi 2 nơi)

### 3.3. Write-Behind (Write-Back)
```
Write Request → Ghi Cache → Response (async ghi DB sau)
```
- Dùng Laravel Queue để ghi DB bất đồng bộ
- Rủi ro mất data nếu Redis crash trước khi ghi DB

### 3.4. Cache Invalidation Strategies
```php
// 1. Time-based (TTL)
Cache::put('key', $value, now()->addHours(1));

// 2. Event-based (xóa cache khi data thay đổi)
// Trong Model Observer hoặc Event Listener
Cache::forget('products:all');
Cache::forget("product:{$id}");

// 3. Tag-based (Laravel Cache Tags - chỉ hỗ trợ Redis/Memcached)
Cache::tags(['products'])->put('products:all', $products, 3600);
Cache::tags(['products'])->put("product:{$id}", $product, 3600);
// Xóa tất cả cache có tag 'products'
Cache::tags(['products'])->flush();
```

---

## 4. Real-World Cases

### Case 1: Cache danh sách Products (có pagination)
```php
// Controller: ProductController@index
public function index(Request $request)
{
    $page = $request->get('page', 1);
    $perPage = $request->get('per_page', 15);
    $cacheKey = "products:list:page_{$page}:per_{$perPage}";

    $products = Cache::tags(['products'])->remember($cacheKey, 3600, function () use ($perPage) {
        return Product::paginate($perPage);
    });

    return response()->json($products);
}
```
> ⚠️ **Lưu ý**: Pagination cache cần key riêng cho mỗi page

### Case 2: Cache Product detail
```php
public function show($id)
{
    $product = Cache::tags(['products', "product:{$id}"])
        ->remember("product:detail:{$id}", 3600, function () use ($id) {
            return Product::with('category', 'reviews')->findOrFail($id);
        });

    return response()->json($product);
}
```

### Case 3: Cache với Filter/Search params
```php
public function index(Request $request)
{
    // Tạo cache key từ tất cả filter params
    $filters = $request->only(['category', 'search', 'min_price', 'max_price', 'sort']);
    $cacheKey = 'products:filter:' . md5(json_encode($filters));

    $products = Cache::tags(['products'])->remember($cacheKey, 1800, function () use ($request) {
        $query = Product::query();

        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        return $query->paginate(15);
    });

    return response()->json($products);
}
```

### Case 4: Invalidation khi CRUD (Model Observer)
```php
// app/Observers/ProductObserver.php
class ProductObserver
{
    public function created(Product $product)
    {
        Cache::tags(['products'])->flush(); // Xóa tất cả cache products
    }

    public function updated(Product $product)
    {
        Cache::tags(['products'])->flush();
        // Hoặc xóa chính xác:
        // Cache::forget("product:detail:{$product->id}");
    }

    public function deleted(Product $product)
    {
        Cache::tags(['products'])->flush();
    }
}
```

### Case 5: Cache Aggregated Data (thống kê)
```php
// Tổng số products, giá trung bình, etc.
$stats = Cache::remember('products:stats', 3600, function () {
    return [
        'total' => Product::count(),
        'avg_price' => Product::avg('price'),
        'categories' => Product::distinct('category')->count(),
        'out_of_stock' => Product::where('stock', 0)->count(),
    ];
});
```

### Case 6: Cache Warming (tạo cache trước)
```php
// app/Console/Commands/WarmProductCache.php
class WarmProductCache extends Command
{
    protected $signature = 'cache:warm-products';

    public function handle()
    {
        // Cache trang đầu tiên (được truy cập nhiều nhất)
        Cache::tags(['products'])->put('products:list:page_1:per_15',
            Product::paginate(15), 3600
        );

        // Cache top categories
        $categories = Product::distinct()->pluck('category');
        foreach ($categories as $category) {
            $key = 'products:filter:' . md5(json_encode(['category' => $category]));
            Cache::tags(['products'])->put($key,
                Product::where('category', $category)->paginate(15), 3600
            );
        }

        $this->info('Product cache warmed successfully!');
    }
}

// Lên lịch chạy tự động trong app/Console/Kernel.php
$schedule->command('cache:warm-products')->hourly();
```

### Case 7: Rate Limiting với Redis
```php
// Giới hạn API calls
// Trong RouteServiceProvider hoặc middleware
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

// Custom rate limit cho specific action
public function store(Request $request)
{
    $key = 'product:create:' . $request->ip();
    if (Cache::has($key) && Cache::get($key) >= 10) {
        return response()->json(['error' => 'Too many requests'], 429);
    }
    Cache::increment($key);
    Cache::put($key, Cache::get($key, 0), 60); // reset sau 60s

    // ... create product
}
```

### Case 8: Distributed Lock (tránh Race Condition)
```php
use Illuminate\Support\Facades\Cache;

// Tránh 2 request cùng update stock
public function purchaseProduct($id, $quantity)
{
    $lock = Cache::lock("product:purchase:{$id}", 10); // lock 10 giây

    if ($lock->get()) {
        try {
            $product = Product::findOrFail($id);
            if ($product->stock < $quantity) {
                return response()->json(['error' => 'Insufficient stock'], 400);
            }
            $product->decrement('stock', $quantity);
            return response()->json(['success' => true]);
        } finally {
            $lock->release();
        }
    }

    return response()->json(['error' => 'Could not acquire lock'], 409);
}
```

### Case 9: Session Storage với Redis
```php
// .env
SESSION_DRIVER=redis

// Tự động lưu session vào Redis thay vì file
// Lợi ích: share session giữa nhiều server (horizontal scaling)
// Không cần code gì thêm - Laravel xử lý tự động
```

### Case 10: Queue Driver với Redis
```php
// .env
QUEUE_CONNECTION=redis

// Dispatch job
ProcessProduct::dispatch($product);

// Job class bình thường, Redis chỉ là storage cho queue
class ProcessProduct implements ShouldQueue
{
    public function handle()
    {
        // heavy processing...
    }
}
```

---

## 5. Các vấn đề & giải pháp

### 5.1. Cache Stampede (Thundering Herd) 🔴
**Vấn đề**: Khi cache expire, hàng trăm requests đồng thời đều query DB

**Giải pháp - Atomic Lock**:
```php
$products = Cache::lock('products:lock', 10)->block(5, function () {
    return Cache::remember('products:all', 3600, function () {
        return Product::all();
    });
});
```

**Giải pháp - Stale-While-Revalidate** (tự implement):
```php
// Lưu data với TTL dài, nhưng track thời gian "fresh"
$cacheKey = 'products:all';
$freshKey = 'products:all:fresh_until';

$products = Cache::get($cacheKey);
$isFresh = Cache::has($freshKey);

if (!$products) {
    // Cache hoàn toàn miss
    $products = Product::all();
    Cache::put($cacheKey, $products, 7200);     // TTL dài
    Cache::put($freshKey, true, 3600);           // "fresh" 1 giờ
} elseif (!$isFresh) {
    // Data có nhưng đã "stale" → trả về cũ, async refresh
    Cache::put($freshKey, true, 3600);
    dispatch(function () use ($cacheKey) {
        Cache::put($cacheKey, Product::all(), 7200);
    });
}
```

### 5.2. Cache Penetration 🔴
**Vấn đề**: Liên tục query key không tồn tại → luôn miss → luôn query DB

**Giải pháp - Cache null result**:
```php
public function show($id)
{
    $cacheKey = "product:{$id}";
    $product = Cache::get($cacheKey);

    if ($product === 'NULL_PLACEHOLDER') {
        return response()->json(['error' => 'Not found'], 404);
    }

    if (!$product) {
        $product = Product::find($id);
        if (!$product) {
            Cache::put($cacheKey, 'NULL_PLACEHOLDER', 300); // cache null 5 phút
            return response()->json(['error' => 'Not found'], 404);
        }
        Cache::put($cacheKey, $product, 3600);
    }

    return response()->json($product);
}
```

**Giải pháp - Bloom Filter**: Kiểm tra key có tồn tại trước khi query (advanced)

### 5.3. Cache Avalanche 🔴
**Vấn đề**: Nhiều cache keys expire cùng lúc → DB bị overwhelm

**Giải pháp - Random TTL**:
```php
// Thêm random offset vào TTL
$ttl = 3600 + rand(0, 600); // 1h ± 10 phút
Cache::put($key, $value, $ttl);
```

**Giải pháp - Cache warming**: Refresh cache trước khi expire (xem Case 6)

### 5.4. Memory Management
```bash
# Kiểm tra memory usage
docker-compose exec redis redis-cli INFO memory

# Cấu hình max memory trong redis.conf hoặc docker-compose
# maxmemory 256mb
# maxmemory-policy allkeys-lru  (xóa key ít dùng nhất khi hết memory)
```

**Eviction Policies**:
| Policy | Mô tả |
|--------|--------|
| `noeviction` | Trả lỗi khi hết memory (default) |
| `allkeys-lru` | Xóa key ít dùng nhất ⭐ Recommended |
| `allkeys-lfu` | Xóa key ít frequent nhất |
| `volatile-lru` | Chỉ xóa key có TTL, theo LRU |
| `volatile-ttl` | Xóa key sắp expire nhất |

---

## 6. Redis nâng cao trong Laravel

### 6.1. Redis Pub/Sub (Real-time Events)
```php
// Publisher (broadcast event)
use Illuminate\Support\Facades\Redis;

Redis::publish('product-updates', json_encode([
    'action' => 'updated',
    'product_id' => $product->id,
]));

// Subscriber (artisan command)
Redis::subscribe(['product-updates'], function ($message) {
    $data = json_decode($message, true);
    // Handle real-time update...
});
```

### 6.2. Redis Pipeline (batch operations)
```php
// Gửi nhiều commands cùng lúc (giảm network round-trips)
Redis::pipeline(function ($pipe) use ($products) {
    foreach ($products as $product) {
        $pipe->set("product:{$product->id}", json_encode($product));
        $pipe->expire("product:{$product->id}", 3600);
    }
});
```

### 6.3. Redis Sorted Set (Leaderboard/Ranking)
```php
// Top products theo views
Redis::zadd('product:views', $viewCount, $productId);

// Lấy top 10
$topProducts = Redis::zrevrange('product:views', 0, 9, 'WITHSCORES');
```

### 6.4. Sử dụng Redis trực tiếp (không qua Cache facade)
```php
use Illuminate\Support\Facades\Redis;

// String
Redis::set('key', 'value');
Redis::get('key');

// Hash
Redis::hset('product:1', 'name', 'iPhone');
Redis::hget('product:1', 'name');
Redis::hgetall('product:1');

// List (queue)
Redis::lpush('queue', 'job1');
Redis::rpop('queue');

// Set
Redis::sadd('categories', 'Electronics');
Redis::smembers('categories');

// TTL
Redis::setex('key', 3600, 'value');  // set với TTL
Redis::ttl('key');                    // kiểm tra TTL còn lại
```

---

## 7. Monitoring & Debugging

### 7.1. Redis CLI Commands
```bash
# Kết nối vào Redis container
docker-compose exec redis redis-cli

# Xem tất cả keys
KEYS *

# Xem keys theo pattern
KEYS products:*

# Xem giá trị của key
GET product:1

# Xem TTL còn lại
TTL product:1

# Monitor tất cả commands real-time
MONITOR

# Thống kê
INFO stats
INFO memory
INFO keyspace

# Xóa tất cả data
FLUSHDB       # xóa DB hiện tại
FLUSHALL      # xóa tất cả DB
```

### 7.2. Laravel Telescope
```php
// Cài đặt
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

// Truy cập: http://localhost:8080/telescope
// → Tab "Cache" để xem tất cả cache hits/misses/writes
```

### 7.3. Debug trong code
```php
// Kiểm tra cache hit/miss
$start = microtime(true);
$products = Cache::get('products:all');
$time = microtime(true) - $start;
// Cache hit: ~0.001s | Cache miss: null

// Log cache events
Cache::listen(function ($event) {
    if ($event instanceof \Illuminate\Cache\Events\CacheMissed) {
        Log::info("Cache MISS: {$event->key}");
    }
    if ($event instanceof \Illuminate\Cache\Events\CacheHit) {
        Log::debug("Cache HIT: {$event->key}");
    }
});
```

---

## 8. Checklist triển khai

### Phase 1: Setup cơ bản
- [ ] Verify Redis container chạy OK (`docker-compose exec redis redis-cli ping` → PONG)
- [ ] Cấu hình `.env` (CACHE_DRIVER=redis)
- [ ] Test `Cache::put()` và `Cache::get()` cơ bản

### Phase 2: Cache API responses
- [ ] Cache `GET /api/products` (danh sách + pagination)
- [ ] Cache `GET /api/products/{id}` (chi tiết)
- [ ] Cache filter/search results
- [ ] Implement cache invalidation khi create/update/delete

### Phase 3: Advanced
- [ ] Implement Cache Tags cho product group
- [ ] Xử lý Cache Stampede (dùng Lock)
- [ ] Xử lý Cache Penetration (cache null)
- [ ] Random TTL để tránh Cache Avalanche
- [ ] Cache Warming command

### Phase 4: Mở rộng
- [ ] Chuyển Session sang Redis
- [ ] Chuyển Queue sang Redis
- [ ] Redis Pub/Sub cho real-time
- [ ] Monitoring với Telescope

---

## 📝 Ghi chú & Tracking

| Ngày | Nội dung | Trạng thái |
|------|----------|------------|
| 2026-03-18 | Khởi tạo project Laravel + Docker | 🔄 Đang làm |
| | Implement cache cơ bản | ⏳ Chờ |
| | Implement cache nâng cao | ⏳ Chờ |
| | Test & optimize | ⏳ Chờ |
