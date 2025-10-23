@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
      <h4 class="mb-0">Movimientos de inventario</h4>
      <p class="text-muted mb-0">Registra entradas, salidas y traslados de productos fuera del flujo de pedidos.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInventoryMovement">
      <i class="bi bi-plus-circle me-1"></i> Nuevo movimiento
    </button>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <table id="inventoryMovementsTable" class="table table-striped table-bordered w-100"></table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalInventoryMovement" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form data-inventory-movement-form class="modal-content" method="POST" action="{{ route('inventory-movements.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Registrar movimiento de inventario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label for="movementCode" class="form-label">Código</label>
            <input id="movementCode" name="code" class="form-control" autocomplete="off">
            <div id="movementCodeFeedback" class="invalid-feedback"></div>
          </div>
          <div class="col-md-4">
            <label for="movementType" class="form-label">Tipo</label>
            <select id="movementType" name="type" class="form-select">
              <option value="in">Entrada</option>
              <option value="out">Salida</option>
              <option value="transfer">Transferencia</option>
            </select>
            <div id="movementTypeFeedback" class="invalid-feedback"></div>
          </div>
          <div class="col-md-4">
            <label for="movementDate" class="form-label">Fecha de movimiento</label>
            <input id="movementDate" type="datetime-local" name="moved_at" class="form-control">
            <div id="movementDateFeedback" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6">
            <label for="movementOrigin" class="form-label">Almacén origen</label>
            <select id="movementOrigin" name="origin_warehouse_id" class="form-select">
              <option value="">Selecciona un almacén</option>
            </select>
            <div id="movementOriginFeedback" class="invalid-feedback"></div>
          </div>
          <div class="col-md-6">
            <label for="movementTarget" class="form-label">Almacén destino</label>
            <select id="movementTarget" name="target_warehouse_id" class="form-select">
              <option value="">Selecciona un almacén</option>
            </select>
            <div id="movementTargetFeedback" class="invalid-feedback"></div>
          </div>
          <div class="col-12">
            <label for="movementNotes" class="form-label">Notas</label>
            <textarea id="movementNotes" name="notes" class="form-control" rows="3" placeholder="Comentarios adicionales"></textarea>
            <div id="movementNotesFeedback" class="invalid-feedback"></div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Detalle de productos</h6>
          <button type="button" class="btn btn-outline-primary btn-sm" data-add-item>
            <i class="bi bi-plus-circle me-1"></i> Añadir producto
          </button>
        </div>
        <p class="text-muted small mb-3">Define los productos y cantidades que deseas mover entre tus almacenes.</p>

        <div class="table-responsive border rounded">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 45%">Producto</th>
                <th style="width: 20%" class="text-end">Cantidad</th>
                <th style="width: 20%" class="text-end">Costo unitario</th>
                <th style="width: 15%"></th>
              </tr>
            </thead>
            <tbody data-inventory-items>
              <tr data-empty-row class="text-muted">
                <td colspan="4" class="text-center py-4">Agrega productos para registrar el movimiento.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div data-items-feedback class="text-danger small mt-2 d-none"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">
          <span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>
          Guardar movimiento
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/inventory_movements/index.js')
  @vite('resources/js/inventory_movements/form.js')
@endpush
