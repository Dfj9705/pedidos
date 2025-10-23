@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Almacenes</h4>
    <button id="btnCreateWarehouse" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalWarehouse">
      <i class="bi bi-plus-circle me-1"></i> Nuevo
    </button>
  </div>

  <table id="warehouseTable" class="table table-striped table-bordered w-100"></table>

  <div class="modal fade" id="modalWarehouse" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form id="formWarehouse" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="warehouseModalTitle">Crear almacén</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">Nombre</label>
            <input id="name" name="name" class="form-control" autocomplete="off">
            <div id="nameFeedback" class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label for="code" class="form-label">Código</label>
            <input id="code" name="code" class="form-control" autocomplete="off">
            <div id="codeFeedback" class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label for="latitude" class="form-label">Latitud</label>
            <input id="latitude" name="latitude" type="number" step="0.000001" class="form-control" autocomplete="off">
            <div id="latitudeFeedback" class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label for="longitude" class="form-label">Longitud</label>
            <input id="longitude" name="longitude" type="number" step="0.000001" class="form-control" autocomplete="off">
            <div id="longitudeFeedback" class="invalid-feedback"></div>
          </div>
          <div class="form-check">
            <input id="is_route" name="is_route" class="form-check-input" type="checkbox" value="1">
            <label class="form-check-label" for="is_route">¿Es almacén en ruta?</label>
            <div id="is_routeFeedback" class="invalid-feedback d-block"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button id="btnSaveWarehouse" class="btn btn-success" type="submit">
            <span id="spinnerSaveWarehouse" class="spinner-border spinner-border-sm me-2" style="display:none"></span>
            Guardar
          </button>
          <button id="btnUpdateWarehouse" class="btn btn-warning" type="button" style="display:none">Modificar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/warehouses/index.js')
@endpush
