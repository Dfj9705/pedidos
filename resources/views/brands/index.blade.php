@extends('layouts.app')

@section('main-class', 'py-4 bg-light')

@section('content')
<div class="container py-4 brand-page">
  <div class="card border-0 shadow-sm mb-4 brand-hero">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
      <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
          <span class="brand-icon"><i class="bi bi-award"></i></span>
          Gestión de marcas
        </h4>
        <p class="text-muted mb-0">Crea, edita y organiza las marcas para tus productos con facilidad.</p>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateBrand">
        <i class="bi bi-plus-circle me-1"></i> Nueva marca
      </button>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <table id="brandTable" class="table table-striped table-bordered w-100"></table>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="modalCreateBrand" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form id="formBrand" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createBrandTitle">Crear marca</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="name" class="form-label">Nombre</label>
            <input id="name" name="name" class="form-control" autocomplete="off">
            <div id="nameFeedback" class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Descripción</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Información adicional sobre la marca"></textarea>
            <div id="descriptionFeedback" class="invalid-feedback"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button id="btnGuardar" class="btn btn-success" type="submit">
            <span id="spinnerGuardar" class="spinner-border spinner-border-sm me-2" style="display:none"></span>
            Guardar
          </button>
          <button id="btnModificar" class="btn btn-warning" type="button" style="display:none">Modificar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/brands/index.js')
@endpush
