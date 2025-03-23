<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    // -------------------------------------------------------
    // Product name templates by category
    // -------------------------------------------------------
    const PRODUCT_NAMES = [
        'electronics' => [
            'Wireless Bluetooth Headphones',
            'Smart Watch Pro',
            'Portable Speaker',
            'USB-C Hub Adapter',
            'Mechanical Keyboard RGB',
            'Wireless Mouse Ergonomic',
            'Laptop Stand Aluminum',
            'Power Bank 20000mAh',
            'Webcam HD 1080p',
            'Noise Cancelling Earbuds',
            'Tablet Stand Adjustable',
            'Fast Charger 65W',
            'External SSD 1TB',
            'Monitor Light Bar',
            'Smart Home Hub',
            'Wireless Charging Pad',
            'Action Camera 4K',
            'Bluetooth Tracker',
            'Digital Drawing Tablet',
            'Mini Projector HD',
        ],
        'clothing' => [
            'Classic Fit T-Shirt',
            'Slim Jogger Pants',
            'Winter Down Jacket',
            'Casual Hoodie Pullover',
            'Denim Jeans Straight',
            'Running Shorts Dry Fit',
            'Polo Shirt Cotton',
            'Windbreaker Lightweight',
            'Thermal Base Layer',
            'Graphic Print Sweater',
            'Cargo Pants Relaxed',
            'V-Neck Cardigan Knit',
            'Athletic Tank Top',
            'Waterproof Rain Coat',
            'Fleece Zip Jacket',
            'Linen Shirt Breathable',
            'Chino Pants Classic',
            'Performance Leggings',
            'Oversized Crew Neck Tee',
            'Quilted Vest Insulated',
        ],
        'books' => [
            'Clean Code Handbook',
            'Design Patterns Explained',
            'The Art of Strategy',
            'Data Science from Scratch',
            'Thinking Fast and Slow',
            'Atomic Habits Guide',
            'System Design Interview',
            'The Lean Startup',
            'Deep Work Focus Guide',
            'JavaScript The Good Parts',
            'Refactoring Legacy Code',
            'Domain Driven Design',
            'The Pragmatic Programmer',
            'Algorithms Illustrated',
            'Microservices Patterns',
            'Head First SQL',
            'Learning Python 5th Edition',
            'Effective Java 3rd Edition',
            'Modern PHP Development',
            'Kubernetes in Action',
        ],
        'sports' => [
            'Running Shoes Lightweight',
            'Yoga Mat Non-Slip',
            'Dumbbell Set Adjustable',
            'Resistance Bands Kit',
            'Jump Rope Speed',
            'Cycling Gloves Padded',
            'Tennis Racket Carbon',
            'Swimming Goggles Anti-Fog',
            'Hiking Backpack 40L',
            'Camping Tent 2-Person',
            'Fitness Tracker Waterproof',
            'Basketball Indoor Outdoor',
            'Football Training Ball',
            'Boxing Gloves Pro',
            'Pull Up Bar Doorway',
            'Foam Roller Muscle',
            'Badminton Racket Set',
            'Golf Balls Premium',
            'Ski Goggles UV Protection',
            'Ice Climbing Boots',
        ],
        'home' => [
            'Coffee Maker Automatic',
            'Air Purifier HEPA',
            'Robot Vacuum Smart',
            'Electric Kettle 1.7L',
            'Stainless Steel Cookware Set',
            'Throw Blanket Flannel',
            'LED Desk Lamp Dimmable',
            'Plant Pot Ceramic Set',
            'Kitchen Knife Set Chef',
            'Smart Light Bulb WiFi',
            'Essential Oil Diffuser',
            'Bathroom Towel Set Premium',
            'Storage Organizer Bamboo',
            'Blender High Speed',
            'Non-Stick Pan Ceramic',
            'Candle Set Scented',
            'Door Mat Welcome',
            'Pillow Memory Foam',
            'Wall Clock Minimalist',
            'Laundry Hamper Foldable',
        ],
    ];

    // -------------------------------------------------------
    // Brands mapped to categories (realistic associations)
    // -------------------------------------------------------
    const CATEGORY_BRANDS = [
        'electronics' => ['samsung', 'apple', 'sony', 'logitech'],
        'clothing'    => ['nike', 'adidas', 'uniqlo'],
        'books'       => ['penguin'],
        'sports'      => ['nike', 'adidas', 'coleman'],
        'home'        => ['ikea', 'samsung'],
    ];

    // -------------------------------------------------------
    // Price ranges by category (VND)
    // -------------------------------------------------------
    const PRICE_RANGES = [
        'electronics' => [200000, 15000000],
        'clothing'    => [150000, 3000000],
        'books'       => [80000, 500000],
        'sports'      => [100000, 5000000],
        'home'        => [50000, 8000000],
    ];

    public function definition(): array
    {
        $category = $this->faker->randomElement(array_keys(self::PRODUCT_NAMES));
        $brand    = $this->faker->randomElement(self::CATEGORY_BRANDS[$category]);
        $priceRange = self::PRICE_RANGES[$category];

        $rawPrice = $this->faker->numberBetween($priceRange[0], $priceRange[1]);
        $price    = round($rawPrice / 1000) * 1000;

        $stockOptions = array_merge([0, 0], range(1, 100));

        $name = $this->faker->randomElement(self::PRODUCT_NAMES[$category]);

        return [
            'name'        => $name,
            'slug'        => \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::random(8),
            'description' => $this->faker->sentence(rand(10, 25)),
            'price'       => $price,
            'category'    => $category,
            'brand'       => $brand,
            'stock'       => $this->faker->randomElement($stockOptions),
            'is_active'   => $this->faker->boolean(90),
            'rating'      => round($this->faker->randomFloat(1, 1.0, 5.0), 1),
        ];
    }
}
