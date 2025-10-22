@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Categorías</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateCategory">
      <i class="bi bi-plus-circle me-1"></i> Nueva
    </button>
  </div>

  <table id="categoryTable" class="table table-striped table-bordered w-100"></table>

  <!-- Modal -->
  <div class="modal fade" id="modalCreateCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form id="formCategory" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createCategoryTitle">Crear categoría</h5>
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
            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
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
  @vite('resources/js/categories/index.js')
@endpush
