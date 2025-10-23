@extends('layouts.app')

@section('main-class', 'py-5 bg-light')

@section('content')
@php
    $hasOrders = Route::has('orders.index');
    $hasProducts = Route::has('products.index');
    $statusLabels = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'shipped' => 'Despachado',
        'delivered' => 'Entregado',
    ];
@endphp
<div class="container">
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="dashboard-hero card border-0 shadow-sm mb-5 overflow-hidden">
        <div class="card-body p-4 p-lg-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold mb-2 letter-spacing">Resumen de operaciones</p>
                    <h1 class="display-6 fw-bold mb-3">Hola, {{ Auth::user()->name }} 👋</h1>
                    <p class="mb-4 lead">Hoy tienes {{ number_format($metrics['orders_pending']) }} pedidos pendientes y {{ number_format($metrics['active_routes']) }} rutas activas listas para entregar.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($hasOrders)
                            <a href="{{ route('orders.index') }}" class="btn btn-light btn-lg shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-bag-check"></i> Administrar pedidos
                            </a>
                        @endif
                        @if ($hasProducts)
                            <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-lg d-flex align-items-center gap-2">
                                <i class="bi bi-box-seam"></i> Inventario
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="hero-icon-wrapper mx-auto mx-lg-0">
                        <i class="bi bi-truck"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5 dashboard-metrics">
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="stats-icon bg-primary-subtle text-primary">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Pedidos totales</p>
                    <h3 class="fw-bold mb-1">{{ number_format($metrics['orders_total']) }}</h3>
                    <span class="text-muted small">Histórico de pedidos registrados</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="stats-icon bg-warning-subtle text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Pendientes de entrega</p>
                    <h3 class="fw-bold mb-1">{{ number_format($metrics['orders_pending']) }}</h3>
                    <span class="text-muted small">{{ number_format($metrics['paid_pending']) }} pagados listos para ruta</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="stats-icon bg-success-subtle text-success">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Ventas del mes</p>
                    <h3 class="fw-bold mb-1">${{ number_format($metrics['monthly_sales'], 2) }}</h3>
                    <span class="text-muted small">Ingresos con pedidos pagados</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="stats-icon bg-info-subtle text-info">
                        <i class="bi bi-signpost-split"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Rutas activas</p>
                    <h3 class="fw-bold mb-1">{{ number_format($metrics['active_routes']) }}</h3>
                    <span class="text-muted small">{{ number_format($metrics['deliveries_today']) }} entregas realizadas hoy</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Próximas rutas de entrega</h5>
                            <p class="text-muted small mb-0">Planifica tu logística y asegura las entregas a tiempo.</p>
                        </div>
                        <span class="badge text-bg-light">{{ number_format($metrics['active_routes']) }} activas</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ruta</th>
                                    <th>Programada</th>
                                    <th class="text-center">Pedidos</th>
                                    <th>Almacén</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($upcomingRoutes as $route)
                                    <tr>
                                        <td class="fw-semibold">{{ $route->code }}</td>
                                        <td>{{ optional($route->scheduled_at)->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                        <td class="text-center">{{ number_format($route->orders_count ?? 0) }}</td>
                                        <td>{{ $route->warehouse?->code ? $route->warehouse->code . ' - ' . $route->warehouse->name : ($route->warehouse->name ?? 'Sin asignar') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center">No hay rutas activas por el momento. Genera una desde el módulo de entregas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Pedidos recientes</h5>
                    <ul class="list-unstyled mb-0 dashboard-order-list">
                        @forelse ($recentOrders as $order)
                            <li class="dashboard-order-item">
                                <div>
                                    <p class="fw-semibold mb-0">{{ $order->code }}</p>
                                    <span class="text-muted small">{{ $order->customer?->name ?? 'Cliente sin nombre' }}</span>
                                </div>
                                <span class="badge {{ $order->status === 'delivered' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $statusLabels[$order->status] ?? ucfirst($order->status) }}</span>
                            </li>
                        @empty
                            <li class="text-muted small">Aún no hay pedidos registrados.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Resumen de catálogos</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="catalog-summary-box">
                                <span class="catalog-label text-muted">Categorías</span>
                                <p class="catalog-value mb-0">{{ number_format($totals['categories']) }}</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="catalog-summary-box">
                                <span class="catalog-label text-muted">Marcas</span>
                                <p class="catalog-value mb-0">{{ number_format($totals['brands']) }}</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="catalog-summary-box">
                                <span class="catalog-label text-muted">Productos</span>
                                <p class="catalog-value mb-0">{{ number_format($totals['products']) }}</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="catalog-summary-box">
                                <span class="catalog-label text-muted">Clientes</span>
                                <p class="catalog-value mb-0">{{ number_format($totals['customers']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
