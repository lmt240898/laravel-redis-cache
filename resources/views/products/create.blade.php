@extends('layouts.app')

@section('title', 'Create Product')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Create Product</h1>
    <p class="text-muted">Add a new product to the catalog</p>
</div>

<div class="card">
    <div class="card-body p-4">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="price" class="form-label">Price (VND)</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror"
                           id="price" name="price" value="{{ old('price') }}" min="0" required>
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select @error('category') is-invalid @enderror"
                            id="category" name="category" required>
                        <option value="">-- Select --</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="brand" class="form-label">Brand</label>
                    <select class="form-select @error('brand') is-invalid @enderror"
                            id="brand" name="brand" required>
                        <option value="">-- Select --</option>
                        @foreach ($brands as $key => $label)
                            <option value="{{ $key }}" {{ old('brand') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('brand')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" class="form-control @error('stock') is-invalid @enderror"
                           id="stock" name="stock" value="{{ old('stock', 0) }}" min="0" required>
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label for="rating" class="form-label">Rating</label>
                    <input type="number" class="form-control @error('rating') is-invalid @enderror"
                           id="rating" name="rating" value="{{ old('rating', 0) }}"
                           min="0" max="5" step="0.1" required>
                    @error('rating')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                               value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active (visible to customers)</label>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Create Product
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
