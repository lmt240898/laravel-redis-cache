<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    // -------------------------------------------------------
    // Seed configuration
    // -------------------------------------------------------
    const TOTAL_PRODUCTS = 5000;
    const MIN_PER_CATEGORY = 800;
    const OUT_OF_STOCK_COUNT = 500;

    public function run(): void
    {
        $categories = array_keys(Product::CATEGORIES);

        // Phase 1: seed minimum per category to ensure even distribution
        foreach ($categories as $category) {
            Product::factory()
                ->count(self::MIN_PER_CATEGORY)
                ->create(['category' => $category]);
        }

        // Phase 2: fill remaining with random categories
        $remaining = self::TOTAL_PRODUCTS - (count($categories) * self::MIN_PER_CATEGORY);
        if ($remaining > 0) {
            Product::factory()->count($remaining)->create();
        }

        // Phase 3: force some products to be out of stock (for availability filter)
        Product::inRandomOrder()
            ->limit(self::OUT_OF_STOCK_COUNT)
            ->update(['stock' => 0]);

        $this->command->info("Seeded " . Product::count() . " products.");
        $this->command->info("Out of stock: " . Product::where('stock', 0)->count());
        $this->command->table(
            ['Category', 'Count'],
            collect($categories)->map(fn($c) => [$c, Product::where('category', $c)->count()])->toArray()
        );
    }
}
