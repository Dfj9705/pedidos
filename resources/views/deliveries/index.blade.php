@extends('layouts.app')

@section('content')
<div class="container py-3 delivery-routes-page">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
      <h4 class="mb-1">Rutas de entrega</h4>
      <p class="text-muted mb-0">Selecciona pedidos pagados para planificar rutas, visualizar el mapa y completar cada entrega.</p>
    </div>
    <div class="text-lg-end">
      <div class="small text-muted" data-selected-count>0 pedidos seleccionados</div>
      <button class="btn btn-primary mt-2 mt-lg-1" type="button" data-create-route>
        <i class="bi bi-signpost-split me-1"></i>
        Generar ruta
      </button>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="row g-3 align-items-end mb-3">
        <div class="col-md-4">
          <label for="deliveryWarehouse" class="form-label">Almacén de salida</label>
          <select id="deliveryWarehouse" class="form-select" data-delivery-warehouse>
            <option value="">Seleccionar automáticamente</option>
          </select>
          <div class="form-text">Si no eliges un almacén se utilizará el configurado como ruta o principal.</div>
        </div>
        <div class="col-md-4">
          <label for="deliveryDate" class="form-label">Fecha y hora programada</label>
          <input id="deliveryDate" type="datetime-local" class="form-control" data-delivery-date>
          <div class="form-text">Se usará la fecha y hora actual si lo dejas vacío.</div>
        </div>
        <div class="col-md-4">
          <label for="deliveryNotes" class="form-label">Notas de ruta (opcional)</label>
          <input id="deliveryNotes" type="text" class="form-control" placeholder="Instrucciones para el repartidor" data-delivery-notes>
        </div>
      </div>

      <div class="table-responsive">
        <table id="deliveriesTable" class="table table-striped table-bordered align-middle w-100"></table>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5 col-xl-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h5 class="mb-0">Rutas activas</h5>
              <p class="text-muted small mb-0">Selecciona una ruta para ver detalles y completar entregas.</p>
            </div>
            <span class="badge text-bg-secondary" data-routes-count>0</span>
          </div>
          <div class="delivery-route-list" data-route-list>
            <div class="text-muted small">No hay rutas planificadas aún. Selecciona pedidos y genera la primera ruta.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-7 col-xl-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div>
              <h5 class="mb-0" data-map-title>Mapa de la ruta</h5>
              <p class="text-muted small mb-0" data-map-subtitle>Selecciona una ruta para visualizarla y ver sus paradas.</p>
            </div>
            <span class="badge text-bg-light" data-map-status>Sin selección</span>
          </div>
          <div class="delivery-route-map-wrapper flex-grow-1">
            <div id="deliveryRouteMap" class="delivery-route-map"></div>
            <div class="delivery-route-map-empty text-center" data-map-empty>
              <i class="bi bi-map text-muted d-block fs-3 mb-2"></i>
              <p class="text-muted mb-0">Selecciona una ruta de la lista para mostrarla en el mapa.</p>
            </div>
          </div>
          <div class="delivery-route-stops mt-3" data-route-stops></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/deliveries/index.js')
@endpush
