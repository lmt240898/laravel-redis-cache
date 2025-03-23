@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> {{ $product->name }}</h1>
        <p class="text-muted mb-0">
            {{ \App\Models\Product::CATEGORIES[$product->category] ?? $product->category }}
            · {{ \App\Models\Product::BRANDS[$product->brand] ?? $product->brand }}
        </p>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row g-4">
    {{-- Product Info --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge-category badge-{{ $product->category }} fs-6">
                        {{ \App\Models\Product::CATEGORIES[$product->category] ?? $product->category }}
                    </span>
                    @if ($product->stock > 0)
                        <span class="badge bg-success fs-6">In Stock ({{ $product->stock }})</span>
                    @else
                        <span class="badge bg-danger fs-6">Out of Stock</span>
                    @endif
                </div>

                <h2 class="mb-3">{{ $product->name }}</h2>

                <p class="text-muted">{{ $product->description ?? 'No description available.' }}</p>

                <hr>

                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-muted small">Price</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format($product->price) }}₫</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Rating</div>
                        <div class="fs-4 fw-bold">
                            {{ $product->rating }} <i class="bi bi-star-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Stock</div>
                        <div class="fs-4 fw-bold">{{ $product->stock }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Status</div>
                        <div class="fs-4 fw-bold">
                            @if ($product->is_active)
                                <span class="text-success">Active</span>
                            @else
                                <span class="text-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar: Views + Purchase --}}
    <div class="col-md-4">
        {{-- CASE 5: Atomic View Counter --}}
        <div class="card mb-3">
            <div class="card-body text-center p-4">
                <i class="bi bi-eye-fill text-primary" style="font-size: 2rem;"></i>
                <h3 class="mt-2 mb-0">{{ number_format($views) }}</h3>
                <p class="text-muted mb-0">Total Views</p>
                <hr>
                <small class="text-muted">
                    <i class="bi bi-lightning-charge"></i>
                    Powered by <code>Redis::incr()</code> — Atomic Counter
                </small>
            </div>
        </div>

        {{-- CASE 5: Atomic Stock Decrement (Purchase) --}}
        <div class="card">
            <div class="card-body p-4">
                <h5 class="card-title"><i class="bi bi-cart-check"></i> Purchase</h5>
                <p class="text-muted small">
                    Stock managed by Lua Script — atomic check + decrement prevents overselling.
                </p>

                @if (session('success'))
                    <div class="alert alert-success small">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger small">
                        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif

                @if ($product->stock > 0)
                    <form action="{{ route('products.purchase', $product) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-bag-plus"></i> Buy Now ({{ $product->stock }} left)
                        </button>
                    </form>
                @else
                    <button class="btn btn-secondary w-100" disabled>
                        <i class="bi bi-bag-x"></i> Out of Stock
                    </button>
                @endif

                <hr>
                <small class="text-muted">
                    <i class="bi bi-code-slash"></i>
                    Powered by <code>Redis EVAL (Lua)</code> — Atomic Script
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
