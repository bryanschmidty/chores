<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Chores') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        :root {
            --app-indigo: #4f46e5;
            --app-purple: #7c3aed;
            --app-cyan: #06b6d4;
            --app-bg: #f5f7ff;
            --app-card-bg: #ffffff;
        }

        body.app-theme {
            background: linear-gradient(180deg, #eef2ff 0%, var(--app-bg) 45%, #f8fafc 100%);
            min-height: 100vh;
        }

        .app-nav {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.92), rgba(124, 58, 237, 0.92));
            border-bottom: 0;
            box-shadow: 0 8px 20px rgba(30, 41, 59, 0.18);
        }

        .app-nav .navbar-brand,
        .app-nav .nav-link,
        .app-nav .text-secondary,
        .app-nav .small {
            color: #fff !important;
        }

        .app-nav .btn-outline-secondary {
            border-color: rgba(255, 255, 255, 0.6);
            color: #fff;
        }

        .app-nav .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: #fff;
            color: #fff;
        }

        .app-surface {
            background: var(--app-card-bg);
            border: 1px solid #e0e7ff;
            box-shadow: 0 8px 18px rgba(79, 70, 229, 0.08);
        }

        .app-stat-card {
            border: 0;
            border-radius: 0.9rem;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(6, 182, 212, 0.1));
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.12);
        }

        .app-stat-card .stat-number {
            color: var(--app-indigo);
        }

        .btn-primary {
            background-color: var(--app-indigo);
            border-color: var(--app-indigo);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #4338ca;
            border-color: #4338ca;
        }

        .list-group-item.active {
            background-color: var(--app-indigo);
            border-color: var(--app-indigo);
        }
    </style>
</head>
<body class="app-theme">
    @include('partials.nav')

    <main class="container py-3 py-md-4">
        @include('partials.flash')
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
