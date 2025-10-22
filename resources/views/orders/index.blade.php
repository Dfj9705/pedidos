@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
      <h4 class="mb-0">Pedidos</h4>
      <p class="text-muted mb-0">Administra los pedidos, sus totales y estados de pago.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrder">
      <i class="bi bi-plus-circle me-1"></i> Nuevo pedido
    </button>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <table id="ordersTable" class="table table-striped table-bordered w-100"></table>
    </div>
  </div>

  <div class="modal fade" id="modalOrder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <form data-order-form class="modal-content" method="POST" action="{{ route('orders.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Crear pedido</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="orderCode" class="form-label">Código</label>
              <input id="orderCode" name="code" class="form-control" autocomplete="off">
              <div id="orderCodeFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label for="orderCustomer" class="form-label">Cliente</label>
              <select id="orderCustomer" name="customer_id" class="form-select">
                <option value="">Selecciona un cliente</option>
              </select>
              <div id="orderCustomerFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-12">
              <label for="orderNotes" class="form-label">Notas</label>
              <textarea id="orderNotes" name="notes" class="form-control" rows="3" placeholder="Comentarios adicionales"></textarea>
              <div id="orderNotesFeedback" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Detalle de productos</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddOrderItem">
              <i class="bi bi-plus-circle me-1"></i> Añadir producto
            </button>
          </div>
          <p class="text-muted small">Utiliza esta sección para definir los productos, cantidades, precios y descuentos del pedido.</p>

          <div class="table-responsive border rounded">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 30%">Producto</th>
                  <th style="width: 12%" class="text-end">Cantidad</th>
                  <th style="width: 15%" class="text-end">Precio</th>
                  <th style="width: 15%" class="text-end">Descuento</th>
                  <th style="width: 15%" class="text-end">Total</th>
                  <th style="width: 13%"></th>
                </tr>
              </thead>
              <tbody data-order-items>
                <tr data-empty-row class="text-muted">
                  <td colspan="6" class="text-center py-4">Agrega productos para calcular los totales del pedido.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer justify-content-between">
          <div class="text-end">
            <div class="fw-semibold">Resumen</div>
            <div class="small text-muted">Subtotal: <span data-order-subtotal>0.0000</span></div>
            <div class="small text-muted">Descuento: <span data-order-discount>0.0000</span></div>
            <div class="fw-semibold">Total: <span data-order-grand>0.0000</span></div>
          </div>
          <button type="submit" class="btn btn-success">
            <span id="spinnerSaveOrder" class="spinner-border spinner-border-sm me-2 d-none"></span>
            Guardar pedido
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/orders/index.js')
  @vite('resources/js/orders/form.js')
@endpush
