<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Pedidos') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700&display=swap" rel="stylesheet" />
    <style>
      :root {
        color-scheme: light dark;
      }
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        font-family: 'Nunito', sans-serif;
        display: flex;
        min-height: 100vh;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #dee2e6 100%);
        color: #1f2933;
      }
      .welcome-wrapper {
        max-width: 720px;
        width: 100%;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(6px);
        border-radius: 18px;
        padding: 48px 40px;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.12);
        text-align: center;
      }
      .welcome-title {
        font-size: 2.25rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #0b7285;
      }
      .welcome-subtitle {
        font-size: 1rem;
        margin: 0 auto 32px;
        max-width: 520px;
        line-height: 1.6;
        color: #495057;
      }
      .welcome-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: center;
      }
      .welcome-actions a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        border-radius: 999px;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .btn-primary {
        background: #0b7285;
        color: #fff;
        box-shadow: 0 12px 24px rgba(11, 114, 133, 0.25);
      }
      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(11, 114, 133, 0.28);
      }
      .btn-secondary {
        background: #fff;
        color: #0b7285;
        border: 2px solid rgba(11, 114, 133, 0.2);
      }
      .btn-secondary:hover {
        transform: translateY(-2px);
        border-color: rgba(11, 114, 133, 0.35);
      }
      @media (max-width: 576px) {
        .welcome-wrapper {
          padding: 32px 24px;
        }
        .welcome-title {
          font-size: 1.85rem;
        }
      }
    </style>
  </head>
  <body>
    <div class="welcome-wrapper">
      <h1 class="welcome-title">Bienvenido al sistema de pedidos</h1>
      <p class="welcome-subtitle">
        Administra catálogos, controla inventarios y coordina tus rutas de entrega desde una sola plataforma pensada para tu equipo.
      </p>
      <div class="welcome-actions">
        @auth
          <a href="{{ route('home') }}" class="btn-primary">Ir al panel</a>
        @else
          <a href="{{ route('login') }}" class="btn-primary">Iniciar sesión</a>
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn-secondary">Crear cuenta</a>
          @endif
        @endauth
      </div>
    </div>
  </body>
</html>
