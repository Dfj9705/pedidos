@extends('layouts.app')

@section('main-class', 'py-5 bg-light')

@section('content')
@php
    $hasOrders = Route::has('orders.index');
    $hasProducts = Route::has('products.index');
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
                    <p class="text-uppercase small fw-semibold mb-2 letter-spacing">Panel principal</p>
                    <h1 class="display-6 fw-bold mb-3">Hola, {{ Auth::user()->name }} 👋</h1>
                    <p class="mb-4 lead">Administra tus pedidos, marcas y categorías desde un único lugar. Accede rápidamente a tus módulos más importantes y mantén todo bajo control.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($hasOrders)
                            <a href="{{ route('orders.index') }}" class="btn btn-light btn-lg shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-bag-check"></i> Ver pedidos
                            </a>
                        @endif
                        @if ($hasProducts)
                            <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-lg d-flex align-items-center gap-2">
                                <i class="bi bi-box-seam"></i> Productos
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="hero-icon-wrapper mx-auto mx-lg-0">
                        <i class="bi bi-graph-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="stats-icon bg-primary-subtle text-primary">
                        <i class="bi bi-tags"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Categorías</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totals['categories']) }}</h3>
                    <span class="text-muted small">Organiza tus catálogos</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="stats-icon bg-success-subtle text-success">
                        <i class="bi bi-award"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Marcas</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totals['brands']) }}</h3>
                    <span class="text-muted small">Construye confianza</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="stats-icon bg-warning-subtle text-warning">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Productos</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totals['products']) }}</h3>
                    <span class="text-muted small">Inventario disponible</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="stats-icon bg-info-subtle text-info">
                        <i class="bi bi-people"></i>
                    </div>
                    <p class="text-uppercase text-muted small mb-1">Clientes</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totals['customers']) }}</h3>
                    <span class="text-muted small">Relaciones activas</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Acciones rápidas</h5>
                    <p class="text-muted small mb-4">Accede rápidamente a las secciones más utilizadas del sistema.</p>
                    <div class="d-flex flex-column gap-3">
                        <a href="{{ route('categories.index') }}" class="quick-link">
                            <span class="quick-link-icon bg-primary-subtle text-primary"><i class="bi bi-folder"></i></span>
                            <div>
                                <p class="fw-semibold mb-0">Gestionar categorías</p>
                                <span class="text-muted small">Crea agrupaciones para tus productos</span>
                            </div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        <a href="{{ route('brands.index') }}" class="quick-link">
                            <span class="quick-link-icon bg-success-subtle text-success"><i class="bi bi-star"></i></span>
                            <div>
                                <p class="fw-semibold mb-0">Administrar marcas</p>
                                <span class="text-muted small">Destaca tus proveedores preferidos</span>
                            </div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        @if ($hasProducts)
                            <a href="{{ route('products.index') }}" class="quick-link">
                                <span class="quick-link-icon bg-warning-subtle text-warning"><i class="bi bi-box"></i></span>
                                <div>
                                    <p class="fw-semibold mb-0">Control de productos</p>
                                    <span class="text-muted small">Actualiza precios y stock rápidamente</span>
                                </div>
                                <i class="bi bi-chevron-right ms-auto"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Últimas novedades</h5>
                    <p class="text-muted small mb-4">Mantente informado de los movimientos recientes en tu negocio.</p>
                    <ul class="list-unstyled timeline-list mb-0">
                        <li class="timeline-item">
                            <span class="timeline-dot bg-primary"></span>
                            <div>
                                <p class="fw-semibold mb-1">Revisa tus pedidos pendientes</p>
                                <span class="text-muted small">Haz seguimiento a los pedidos sin completar para mejorar tus tiempos de entrega.</span>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <span class="timeline-dot bg-success"></span>
                            <div>
                                <p class="fw-semibold mb-1">Actualiza nuevas marcas</p>
                                <span class="text-muted small">Asegúrate de mantener vigente la información de proveedores y marcas.</span>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <span class="timeline-dot bg-warning"></span>
                            <div>
                                <p class="fw-semibold mb-1">Comparte reportes con tu equipo</p>
                                <span class="text-muted small">Descarga y distribuye reportes desde los módulos principales.</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
