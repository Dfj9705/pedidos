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
    <div id="app" class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm app-navbar">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <i class="bi bi-bag-heart-fill me-2 fs-4"></i>
                    <span class="fw-semibold">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('home') }}">
                                    <i class="bi bi-speedometer2 me-1"></i> Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('categories.index') }}">
                                    <i class="bi bi-tags me-1"></i> Categorías
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('brands.index') }}">
                                    <i class="bi bi-award me-1"></i> Marcas
                                </a>
                            </li>
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto align-items-md-center">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center" href="{{ route('login') }}">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Login') }}
                                    </a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center" href="{{ route('register') }}">
                                        <i class="bi bi-person-plus me-1"></i> {{ __('Register') }}
                                    </a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right text-primary"></i> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="app-main flex-grow-1 @yield('main-class', 'py-4')">
            @yield('content')
        </main>
    </div>
</body>
</html>
