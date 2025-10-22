@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">Stock por almacén</h4>
      <p class="text-muted mb-0">Consulta la disponibilidad por producto y almacén.</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Producto</th>
          <th>Almacén</th>
          <th class="text-end">Cantidad</th>
        </tr>
      </thead>
      <tbody>
        @forelse($stocks as $stock)
          <tr>
            <td class="text-nowrap">{{ $stock->product?->sku }}</td>
            <td>{{ $stock->product?->name }}</td>
            <td>{{ $stock->warehouse?->name }}</td>
            <td class="text-end">{{ number_format((float) $stock->qty, 4, '.', ',') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center text-muted py-4">No hay registros de stock disponibles.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
