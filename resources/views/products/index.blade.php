@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> Products</h1>
        <p class="text-muted mb-0">{{ $products->total() }} products total</p>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Product
    </a>
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

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr>
                    <td class="text-muted">{{ $product->id }}</td>
                    <td>
                        <strong>{{ $product->name }}</strong>
                    </td>
                    <td>
                        <span class="badge-category badge-{{ $product->category }}">
                            {{ \App\Models\Product::CATEGORIES[$product->category] ?? $product->category }}
                        </span>
                    </td>
                    <td>{{ \App\Models\Product::BRANDS[$product->brand] ?? $product->brand }}</td>
                    <td class="price-tag">{{ number_format($product->price) }}₫</td>
                    <td>
                        @if ($product->stock > 0)
                            <span class="stock-badge bg-success bg-opacity-10 text-success">
                                {{ $product->stock }} in stock
                            </span>
                        @else
                            <span class="stock-badge bg-danger bg-opacity-10 text-danger">
                                Out of stock
                            </span>
                        @endif
                    </td>
                    <td>
                        <span class="rating-stars">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= round($product->rating))
                                    <i class="bi bi-star-fill"></i>
                                @else
                                    <i class="bi bi-star"></i>
                                @endif
                            @endfor
                        </span>
                        <small class="text-muted ms-1">{{ $product->rating }}</small>
                    </td>
                    <td>
                        @if ($product->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2">No products found. <a href="{{ route('products.create') }}">Create one?</a></p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $products->links() }}
</div>
@endsection
