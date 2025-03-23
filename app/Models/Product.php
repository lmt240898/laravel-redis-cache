<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'category',
        'brand',
        'stock',
        'is_active',
        'rating',
    ];

    protected $casts = [
        'price'     => 'decimal:0',
        'rating'    => 'decimal:1',
        'is_active' => 'boolean',
        'stock'     => 'integer',
    ];

    // -------------------------------------------------------
    // Categories & Brands constants (for filter dropdowns)
    // -------------------------------------------------------
    const CATEGORIES = [
        'electronics' => 'Electronics',
        'clothing'    => 'Clothing',
        'books'       => 'Books',
        'sports'      => 'Sports',
        'home'        => 'Home & Kitchen',
    ];

    const BRANDS = [
        'samsung'  => 'Samsung',
        'apple'    => 'Apple',
        'sony'     => 'Sony',
        'nike'     => 'Nike',
        'adidas'   => 'Adidas',
        'ikea'     => 'IKEA',
        'penguin'  => 'Penguin Books',
        'coleman'  => 'Coleman',
        'logitech' => 'Logitech',
        'uniqlo'   => 'Uniqlo',
    ];

    // -------------------------------------------------------
    // Auto-generate slug
    // -------------------------------------------------------
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
        });
    }

    // -------------------------------------------------------
    // Filter scopes
    // -------------------------------------------------------
    public function scopeFilterCategory($query, ?string $category)
    {
        return $category ? $query->where('category', $category) : $query;
    }

    public function scopeFilterBrand($query, ?string $brand)
    {
        return $brand ? $query->where('brand', $brand) : $query;
    }

    public function scopeFilterPriceRange($query, ?string $min, ?string $max)
    {
        if ($min) $query->where('price', '>=', $min);
        if ($max) $query->where('price', '<=', $max);
        return $query;
    }

    public function scopeFilterRating($query, ?string $rating)
    {
        return $rating ? $query->where('rating', '>=', $rating) : $query;
    }

    public function scopeFilterAvailability($query, ?string $availability)
    {
        return match ($availability) {
            'in_stock'    => $query->where('stock', '>', 0),
            'out_of_stock' => $query->where('stock', 0),
            default       => $query,
        };
    }
}
