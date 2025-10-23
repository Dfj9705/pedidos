<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="app-body @yield('body-class')">
    <div id="app" class="app-shell d-flex min-vh-100">
        <aside class="app-sidebar">
            <div class="app-sidebar-content d-flex flex-column h-100">
                <div class="app-sidebar-header d-flex align-items-center justify-content-between">
                    <a class="app-brand d-flex align-items-center text-decoration-none" href="{{ url('/') }}">
                        <i class="bi bi-bag-heart-fill me-2 fs-4"></i>
                        <span class="fw-semibold">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    <button class="btn btn-outline-light btn-sm d-md-none" type="button" data-sidebar-toggle aria-label="{{ __('Cerrar navegación') }}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                @auth
                    <div class="app-sidebar-menu mt-4">
                        <p class="app-sidebar-section-title mb-3">{{ __('Navegación') }}</p>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('home')]) href="{{ route('home') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>{{ __('Panel') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('categories.*')]) href="{{ route('categories.index') }}">
                                    <i class="bi bi-journals"></i>
                                    <span>{{ __('Categorías') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('customers.*')]) href="{{ route('customers.index') }}">
                                    <i class="bi bi-people"></i>
                                    <span>{{ __('Clientes') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('warehouses.*')]) href="{{ route('warehouses.index') }}">
                                    <i class="bi bi-box-seam"></i>
                                    <span>{{ __('Almacenes') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('brands.*')]) href="{{ route('brands.index') }}">
                                    <i class="bi bi-award"></i>
                                    <span>{{ __('Marcas') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('products.*')]) href="{{ route('products.index') }}">
                                    <i class="bi bi-box"></i>
                                    <span>{{ __('Productos') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('stocks.*')]) href="{{ route('stocks.index') }}">
                                    <i class="bi bi-stack"></i>
                                    <span>{{ __('Existencias') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('inventory-movements.*')]) href="{{ route('inventory-movements.index') }}">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>{{ __('Movimientos') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('deliveries.*')]) href="{{ route('deliveries.index') }}">
                                    <i class="bi bi-truck"></i>
                                    <span>{{ __('Entregas') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a @class(['nav-link', 'active' => request()->routeIs('orders.*')]) href="{{ route('orders.index') }}">
                                    <i class="bi bi-bag-check"></i>
                                    <span>{{ __('Pedidos') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                @endauth

                <div class="app-sidebar-footer mt-auto">
                    @guest
                        <p class="app-sidebar-section-title mb-3">{{ __('Cuenta') }}</p>
                        <ul class="nav flex-column">
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        <span>{{ __('Login') }}</span>
                                    </a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">
                                        <i class="bi bi-person-plus"></i>
                                        <span>{{ __('Register') }}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    @else
                        <div class="app-sidebar-user">
                            <div class="app-sidebar-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="user-name mb-0">{{ Auth::user()->name }}</p>
                                @if (Auth::user()->email)
                                    <p class="user-email mb-0">{{ Auth::user()->email }}</p>
                                @endif
                            </div>
                        </div>
                        <a class="app-sidebar-logout" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('Logout') }}</span>
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    @endguest
                </div>
            </div>
        </aside>

        <div class="app-sidebar-backdrop d-md-none" data-sidebar-close></div>

        <div class="app-content flex-grow-1 d-flex flex-column">
            <header class="app-topbar d-md-none">
                <div class="d-flex align-items-center justify-content-between">
                    <button class="btn btn-outline-primary" type="button" data-sidebar-toggle aria-label="{{ __('Abrir navegación') }}">
                        <i class="bi bi-list"></i>
                    </button>
                    <a class="app-topbar-brand d-flex align-items-center text-decoration-none" href="{{ url('/') }}">
                        <i class="bi bi-bag-heart-fill me-2 text-primary fs-5"></i>
                        <span class="fw-semibold">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    <span class="app-topbar-placeholder"></span>
                </div>
            </header>

            <main class="app-main flex-grow-1 @yield('main-class', 'py-4')">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
