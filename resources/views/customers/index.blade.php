@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Clientes</h4>
    <button id="btnCreateCustomer" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCustomer">
      <i class="bi bi-plus-circle me-1"></i> Nuevo
    </button>
  </div>

  <table id="customerTable" class="table table-striped table-bordered w-100"></table>

  <div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form id="formCustomer" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="customerModalTitle">Crear cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="name" class="form-label">Nombre</label>
              <input id="name" name="name" class="form-control" autocomplete="off">
              <div id="nameFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label">Teléfono</label>
              <input id="phone" name="phone" class="form-control" autocomplete="off">
              <div id="phoneFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">Correo electrónico</label>
              <input id="email" name="email" type="email" class="form-control" autocomplete="off">
              <div id="emailFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-12">
              <label for="address" class="form-label">Dirección</label>
              <textarea id="address" name="address" class="form-control" rows="3"></textarea>
              <div id="addressFeedback" class="invalid-feedback"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button id="btnSaveCustomer" class="btn btn-success" type="submit">
            <span id="spinnerSaveCustomer" class="spinner-border spinner-border-sm me-2" style="display:none"></span>
            Guardar
          </button>
          <button id="btnUpdateCustomer" class="btn btn-warning" type="button" style="display:none">Modificar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/customers/index.js')
@endpush
