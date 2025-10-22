@extends('layouts.app')

@section('main-class', 'py-4 bg-light')

@section('content')
<div class="container py-4 product-page">
  <div class="card border-0 shadow-sm mb-4 product-hero">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
      <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
          <span class="text-warning"><i class="bi bi-box-seam"></i></span>
          Gestión de productos
        </h4>
        <p class="text-muted mb-0">Controla las fichas de producto, costos, precios y su disponibilidad.</p>
      </div>
      <button id="btnCreateProduct" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduct">
        <i class="bi bi-plus-circle me-1"></i> Nuevo producto
      </button>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <table id="productTable" class="table table-striped table-bordered w-100"></table>
    </div>
  </div>

  <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form id="formProduct" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productModalTitle">Crear producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="productBrand" class="form-label">Marca</label>
              <select id="productBrand" name="brand_id" class="form-select">
                <option value="">Selecciona una marca</option>
              </select>
              <div id="productBrandFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label for="productCategory" class="form-label">Categoría</label>
              <select id="productCategory" name="category_id" class="form-select">
                <option value="">Selecciona una categoría</option>
              </select>
              <div id="productCategoryFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label for="productSku" class="form-label">SKU</label>
              <input id="productSku" name="sku" class="form-control" autocomplete="off">
              <div id="productSkuFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label for="productName" class="form-label">Nombre</label>
              <input id="productName" name="name" class="form-control" autocomplete="off">
              <div id="productNameFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-12">
              <label for="productDescription" class="form-label">Descripción</label>
              <textarea id="productDescription" name="description" class="form-control" rows="3" placeholder="Información adicional sobre el producto"></textarea>
              <div id="productDescriptionFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-4">
              <label for="productCost" class="form-label">Costo</label>
              <input id="productCost" name="cost" type="number" step="0.0001" min="0" class="form-control text-end">
              <div id="productCostFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-4">
              <label for="productPrice" class="form-label">Precio</label>
              <input id="productPrice" name="price" type="number" step="0.0001" min="0" class="form-control text-end">
              <div id="productPriceFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-md-4">
              <label for="productMinStock" class="form-label">Stock mínimo</label>
              <input id="productMinStock" name="min_stock" type="number" step="0.0001" min="0" class="form-control text-end">
              <div id="productMinStockFeedback" class="invalid-feedback"></div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="productIsActive" name="is_active" checked>
                <label class="form-check-label" for="productIsActive">Producto activo</label>
              </div>
              <div id="productIsActiveFeedback" class="invalid-feedback d-block"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button id="btnSaveProduct" class="btn btn-success" type="submit">
            <span id="spinnerSaveProduct" class="spinner-border spinner-border-sm me-2" style="display:none"></span>
            Guardar
          </button>
          <button id="btnUpdateProduct" class="btn btn-warning" type="button" style="display:none">Modificar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/products/index.js')
@endpush
