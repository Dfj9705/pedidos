@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Movimientos de inventario</h4>
  </div>

  <table id="inventoryMovementsTable" class="table table-striped table-bordered w-100">
    <thead>
      <tr>
        <th>Código</th>
        <th>Tipo</th>
        <th>Fecha de movimiento</th>
        <th>Origen</th>
        <th>Destino</th>
        <th>Detalles</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
@endsection

@push('scripts')
  @vite('resources/js/inventory_movements/index.js')
@endpush
