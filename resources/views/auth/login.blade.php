@extends('layouts.app')

@section('body-class', 'auth-body')
@section('main-class', 'py-0')

@section('content')
<div class="auth-wrapper d-flex align-items-center justify-content-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card auth-card border-0 shadow-lg overflow-hidden">
                    <div class="row g-0">
                        <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between auth-illustration text-white p-5">
                            <div>
                                <h3 class="fw-bold mb-3">Gestiona tus pedidos</h3>
                                <p class="mb-4">Mantén un control total de tus productos, marcas y categorías desde un panel moderno y fácil de usar.</p>
                            </div>
                            <ul class="list-unstyled mb-0 small">
                                <li class="d-flex align-items-center mb-2"><i class="bi bi-check-circle-fill me-2"></i> Reportes claros y rápidos</li>
                                <li class="d-flex align-items-center mb-2"><i class="bi bi-check-circle-fill me-2"></i> Seguridad de nivel empresarial</li>
                                <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i> Gestiona pedidos en minutos</li>
                            </ul>
                        </div>
                        <div class="col-12 col-lg-6 p-4 p-lg-5">
                            <div class="text-center mb-4">
                                <div class="auth-icon mx-auto mb-3">
                                    <i class="bi bi-person-bounding-box"></i>
                                </div>
                                <h2 class="fw-bold mb-1">¡Bienvenido de nuevo!</h2>
                                <p class="text-muted mb-0">Inicia sesión para continuar administrando tus pedidos.</p>
                            </div>

                            <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                        @error('email')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                        @error('password')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="remember">
                                            Recuérdame
                                        </label>
                                    </div>

                                    @if (Route::has('password.request'))
                                        <a class="link-secondary small" href="{{ route('password.request') }}">
                                            ¿Olvidaste tu contraseña?
                                        </a>
                                    @endif
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                                    </button>
                                </div>
                            </form>

                            @if (Route::has('register'))
                                <div class="text-center mt-4">
                                    <span class="text-muted">¿Aún no tienes una cuenta?</span>
                                    <a href="{{ route('register') }}" class="fw-semibold">Regístrate</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
