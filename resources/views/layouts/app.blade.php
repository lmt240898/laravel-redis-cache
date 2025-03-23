<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel Redis Cache')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .nav-link {
            font-weight: 500;
            opacity: 0.85;
            transition: opacity 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            opacity: 1;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .badge-category {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .badge-electronics { background: #dbeafe; color: #1d4ed8; }
        .badge-clothing    { background: #fce7f3; color: #be185d; }
        .badge-books       { background: #fef3c7; color: #92400e; }
        .badge-sports      { background: #d1fae5; color: #065f46; }
        .badge-home        { background: #ede9fe; color: #5b21b6; }

        .stock-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .rating-stars {
            color: var(--warning);
            font-size: 0.85rem;
        }

        .page-header {
            padding: 2rem 0 1rem;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 1.75rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .table th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .price-tag {
            font-weight: 700;
            color: var(--primary);
        }

        .product-card {
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .product-card .card-body {
            padding: 1.25rem;
        }

        .product-card .product-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .filter-sidebar {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            position: sticky;
            top: 100px;
        }

        .filter-sidebar h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
        }

        .filter-sidebar .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .container {
            max-width: 1200px;
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('products.index') }}">
                <i class="bi bi-database"></i> Redis Cache Lab
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}"
                           href="{{ route('products.index') }}">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.filter') ? 'active' : '' }}"
                           href="{{ route('products.filter') }}">
                            <i class="bi bi-funnel"></i> Filter
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.create') ? 'active' : '' }}"
                           href="{{ route('products.create') }}">
                            <i class="bi bi-plus-lg"></i> Create
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
