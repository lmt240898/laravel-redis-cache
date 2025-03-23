@extends('layouts.app')

@section('title', 'Filter Products')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-funnel"></i> Filter Products</h1>
    <p class="text-muted">Search and filter across {{ $totalResults }} results</p>
</div>

<div class="alert {{ $cacheHit ? 'alert-success' : 'alert-warning' }} d-flex align-items-center justify-content-between mb-3">
    <div>
        <i class="bi {{ $cacheHit ? 'bi-lightning-charge-fill' : 'bi-database' }}"></i>
        <strong>{{ $cacheHit ? 'CACHE HIT' : 'CACHE MISS' }}</strong>
        — Data loaded in <strong>{{ $queryTime }}ms</strong>
        {{ $cacheHit ? '(from Redis)' : '(from MySQL → cached to Redis)' }}
    </div>
    <span class="badge {{ $cacheHit ? 'bg-success' : 'bg-warning text-dark' }}">
        {{ $queryTime }}ms
    </span>
</div>

<div class="row g-4">
    {{-- Filter Sidebar --}}
    <div class="col-lg-3">
        <div class="filter-sidebar">
            <h5><i class="bi bi-sliders"></i> Filters</h5>

            <form action="{{ route('products.filter') }}" method="GET">
                {{-- 1. Category --}}
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select form-select-sm" id="category" name="category">
                        <option value="">All Categories</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['category'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Brand --}}
                <div class="mb-3">
                    <label for="brand" class="form-label">Brand</label>
                    <select class="form-select form-select-sm" id="brand" name="brand">
                        <option value="">All Brands</option>
                        @foreach ($brands as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['brand'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. Price Range --}}
                <div class="mb-3">
                    <label class="form-label">Price Range (VND)</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm"
                                   name="price_min" placeholder="Min"
                                   value="{{ $filters['price_min'] ?? '' }}">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm"
                                   name="price_max" placeholder="Max"
                                   value="{{ $filters['price_max'] ?? '' }}">
                        </div>
                    </div>
                </div>

                {{-- 4. Rating --}}
                <div class="mb-3">
                    <label for="rating" class="form-label">Min Rating</label>
                    <select class="form-select form-select-sm" id="rating" name="rating">
                        <option value="">Any Rating</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ ($filters['rating'] ?? '') == $i ? 'selected' : '' }}>
                                {{ $i }}+ ★
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- 5. Availability --}}
                <div class="mb-4">
                    <label for="availability" class="form-label">Availability</label>
                    <select class="form-select form-select-sm" id="availability" name="availability">
                        <option value="">All</option>
                        <option value="in_stock" {{ ($filters['availability'] ?? '') === 'in_stock' ? 'selected' : '' }}>
                            In Stock
                        </option>
                        <option value="out_of_stock" {{ ($filters['availability'] ?? '') === 'out_of_stock' ? 'selected' : '' }}>
                            Out of Stock
                        </option>
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                    <a href="{{ route('products.filter') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i> Clear All
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Results Grid --}}
    <div class="col-lg-9">
        @if ($products->count() > 0)
            <div class="row g-3">
                @foreach ($products as $product)
                <div class="col-md-4">
                    <div class="card product-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge-category badge-{{ $product->category }}">
                                    {{ \App\Models\Product::CATEGORIES[$product->category] ?? $product->category }}
                                </span>
                                @if ($product->stock > 0)
                                    <span class="stock-badge bg-success bg-opacity-10 text-success">In Stock</span>
                                @else
                                    <span class="stock-badge bg-danger bg-opacity-10 text-danger">Out of Stock</span>
                                @endif
                            </div>

                            <h6 class="product-name">{{ $product->name }}</h6>

                            <p class="text-muted small mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $product->description }}
                            </p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">
                                        {{ \App\Models\Product::BRANDS[$product->brand] ?? $product->brand }}
                                    </span>
                                    <span class="rating-stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= round($product->rating))
                                                <i class="bi bi-star-fill"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                        <small class="text-muted">{{ $product->rating }}</small>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag fs-5">{{ number_format($product->price) }}₫</span>
                                    <span class="text-muted small">Stock: {{ $product->stock }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size: 3rem; color: var(--text-muted);"></i>
                <h5 class="mt-3 text-muted">No products match your filters</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
                <a href="{{ route('products.filter') }}" class="btn btn-outline-primary">
                    <i class="bi bi-x-lg"></i> Clear Filters
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
