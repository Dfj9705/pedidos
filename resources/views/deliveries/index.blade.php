@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
      <h4 class="mb-0">Rutas de entrega</h4>
      <p class="text-muted mb-0">Elige pedidos pagados, define el almacén de salida y márcalos como entregados.</p>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-md-5 col-lg-4">
          <label for="deliveryWarehouse" class="form-label">Almacén de salida</label>
          <select id="deliveryWarehouse" class="form-select" data-delivery-warehouse>
            <option value="">Seleccionar automáticamente</option>
          </select>
          <div class="form-text">Si no eliges un almacén se utilizará el configurado como ruta o principal.</div>
        </div>
        <div class="col-md-5 col-lg-4">
          <label for="deliveryDate" class="form-label">Fecha y hora de entrega</label>
          <input id="deliveryDate" type="datetime-local" class="form-control" data-delivery-date>
          <div class="form-text">Deja vacío para usar la fecha y hora actuales.</div>
        </div>
      </div>

      <table id="deliveriesTable" class="table table-striped table-bordered w-100"></table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/deliveries/index.js')
@endpush
