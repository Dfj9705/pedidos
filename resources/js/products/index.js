import DataTable from 'datatables.net-bs5'
import 'datatables.net-buttons-bs5'
import 'datatables.net-buttons/js/buttons.html5.mjs'
import 'datatables.net-buttons/js/buttons.print.mjs'
import 'datatables.net-responsive-bs5'
import 'datatables.net-select-bs5'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'
import { Toast } from '../app'

const formProduct = document.querySelector('#formProduct')
const tableElement = document.querySelector('#productTable')
const modalElement = document.getElementById('modalProduct')
const btnCreateProduct = document.getElementById('btnCreateProduct')
const btnSave = document.getElementById('btnSaveProduct')
const btnUpdate = document.getElementById('btnUpdateProduct')
const spinnerSave = document.getElementById('spinnerSaveProduct')
const title = document.getElementById('productModalTitle')
const selectBrand = document.getElementById('productBrand')
const selectCategory = document.getElementById('productCategory')
const inputSku = document.getElementById('productSku')
const inputName = document.getElementById('productName')
const inputDescription = document.getElementById('productDescription')
const inputCost = document.getElementById('productCost')
const inputPrice = document.getElementById('productPrice')
const inputMinStock = document.getElementById('productMinStock')
const inputIsActive = document.getElementById('productIsActive')

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

if (formProduct && tableElement && modalElement) {
    const modal = new Modal(modalElement)

    const state = {
        brands: [],
        categories: [],
    }

    const dt = new DataTable(tableElement, {
        data: [],
        responsive: true,
        columns: [
            {
                title: 'No.',
                data: null,
                render: (data, type, row, meta) => meta.row + 1,
                className: 'text-center',
                width: '60px',
            },
            { title: 'SKU', data: 'sku' },
            { title: 'Nombre', data: 'name' },
            { title: 'Marca', data: 'brand.name', defaultContent: '' },
            { title: 'Categoría', data: 'category.name', defaultContent: '' },
            {
                title: 'Precio',
                data: 'price',
                className: 'text-end',
                render: (data) => formatDecimal(data),
            },
            {
                title: 'Stock mínimo',
                data: 'min_stock',
                className: 'text-end',
                render: (data) => formatDecimal(data),
            },
            {
                title: 'Estado',
                data: 'is_active',
                className: 'text-center',
                render: (value) =>
                    value
                        ? '<span class="badge text-bg-success">Activo</span>'
                        : '<span class="badge text-bg-secondary">Inactivo</span>',
            },
            {
                title: 'Opciones',
                data: 'id',
                orderable: false,
                className: 'text-center',
                render: (id, type, row) => {
                    const brandId = row.brand?.id ?? ''
                    const categoryId = row.category?.id ?? ''
                    const description = encodeURIComponent(row.description ?? '')
                    return `
          <div class="btn-group" role="group">
            <button
              class="btn btn-sm btn-warning btn-edit"
              data-id="${id}"
              data-brand-id="${brandId}"
              data-category-id="${categoryId}"
              data-sku="${encodeURIComponent(row.sku ?? '')}"
              data-name="${encodeURIComponent(row.name ?? '')}"
              data-description="${description}"
              data-cost="${row.cost ?? '0'}"
              data-price="${row.price ?? '0'}"
              data-min-stock="${row.min_stock ?? '0'}"
              data-is-active="${row.is_active ? '1' : '0'}"
              data-bs-toggle="modal"
              data-bs-target="#modalProduct"
            >
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-danger btn-delete" data-id="${id}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        `
                },
            },
        ],
    })

    let currentUpdateId = null

    const renderSelectOptions = () => {
        renderOptions(selectBrand, state.brands, 'Selecciona una marca')
        renderOptions(selectCategory, state.categories, 'Selecciona una categoría')
    }

    const clearValidation = () => {
        formProduct.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'))
        formProduct.querySelectorAll('.invalid-feedback').forEach((el) => (el.innerHTML = ''))
    }

    const paintErrors = (errors = {}) => {
        Object.entries(errors).forEach(([key, messages]) => {
            const feedbackMessage = Array.isArray(messages) ? messages.join('<br>') : messages
            const input = formProduct.querySelector(`[name="${key}"]`)
            if (input) {
                input.classList.add('is-invalid')
                const feedback = document.getElementById(`${input.id}Feedback`)
                if (feedback) feedback.innerHTML = feedbackMessage
            }
        })
    }

    const loadProducts = async () => {
        try {
            const response = await fetch('/products', {
                headers: { Accept: 'application/json' },
                credentials: 'include',
            })
            if (!response.ok) {
                Toast.fire({ icon: 'error', title: 'No se pudieron cargar los productos' })
                return
            }

            const payload = await response.json()
            const products = Array.isArray(payload.products) ? payload.products : []
            state.brands = Array.isArray(payload.brands) ? payload.brands : []
            state.categories = Array.isArray(payload.categories) ? payload.categories : []
            renderSelectOptions()

            dt.clear()
            if (products.length) {
                dt.rows.add(products).draw()
            } else {
                dt.draw()
            }
        } catch (error) {
            console.error('Error cargando productos', error)
        }
    }

    const setCreateState = () => {
        currentUpdateId = null
        title.textContent = 'Crear producto'
        btnSave.style.display = ''
        btnSave.disabled = false
        btnUpdate.style.display = 'none'
        btnUpdate.disabled = true
        spinnerSave.style.display = 'none'
        formProduct.reset()
        inputIsActive.checked = true
        clearValidation()
    }

    const populateForm = (data) => {
        renderSelectOptions()
        selectBrand.value = data.brandId || ''
        selectCategory.value = data.categoryId || ''
        inputSku.value = decodeURIComponent(data.sku || '')
        inputName.value = decodeURIComponent(data.name || '')
        inputDescription.value = decodeURIComponent(data.description || '')
        inputCost.value = formatIntegerValue(data.cost)
        inputPrice.value = formatIntegerValue(data.price)
        inputMinStock.value = formatIntegerValue(data.minStock)
        inputIsActive.checked = data.isActive === '1'
    }

    const editProduct = (e) => {
        const btn = e.target.closest('button.btn-edit')
        if (!btn) return

        currentUpdateId = btn.dataset.id
        title.textContent = 'Editar producto'
        btnSave.style.display = 'none'
        btnSave.disabled = true
        btnUpdate.style.display = ''
        btnUpdate.disabled = false
        spinnerSave.style.display = 'none'
        clearValidation()

        populateForm({
            brandId: btn.dataset.brandId,
            categoryId: btn.dataset.categoryId,
            sku: btn.dataset.sku,
            name: btn.dataset.name,
            description: btn.dataset.description,
            cost: btn.dataset.cost,
            price: btn.dataset.price,
            minStock: btn.dataset.minStock,
            isActive: btn.dataset.isActive,
        })
    }

    const sendForm = async ({ id = null } = {}) => {
        const formData = new FormData(formProduct)
        formData.set('is_active', inputIsActive.checked ? '1' : '0')

        const url = id ? `/products/${id}` : '/products'
        const method = id ? 'POST' : 'POST'
        if (id) {
            formData.append('_method', 'PUT')
        }

        spinnerSave.style.display = ''
        btnSave.disabled = true
        btnUpdate.disabled = true

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
                credentials: 'include',
            })

            const payload = await response.json().catch(() => ({}))
            clearValidation()

            if (response.status === 422) {
                paintErrors(payload.errors || {})
                btnSave.disabled = false
                btnUpdate.disabled = false
                spinnerSave.style.display = 'none'
                return
            }

            if (!response.ok) {
                Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
                btnSave.disabled = false
                btnUpdate.disabled = false
                spinnerSave.style.display = 'none'
                return
            }

            Toast.fire({ icon: 'success', title: id ? 'Producto actualizado' : 'Producto creado' })
            modal.hide()
            setCreateState()
            await loadProducts()
        } catch (error) {
            console.error('Error guardando producto', error)
            btnSave.disabled = false
            btnUpdate.disabled = false
        }

        spinnerSave.style.display = 'none'
    }

    const createProduct = (event) => {
        event.preventDefault()
        if (currentUpdateId) return
        sendForm({})
    }

    const updateProduct = (event) => {
        event.preventDefault()
        if (!currentUpdateId) return
        sendForm({ id: currentUpdateId })
    }

    const deleteProduct = async (e) => {
        const btn = e.target.closest('button.btn-delete')
        if (!btn) return
        const { id } = btn.dataset

        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Confirmación',
            text: '¿Desea eliminar este producto?',
            showCancelButton: true,
            confirmButtonColor: '#E5533D',
            confirmButtonText: 'Sí',
            cancelButtonText: 'Cancelar',
        })

        if (!confirmation.isConfirmed) return

        try {
            const response = await fetch(`/products/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                credentials: 'include',
            })

            if (response.ok) {
                Toast.fire({ icon: 'success', title: 'Producto eliminado' })
                await loadProducts()
            } else {
                Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
            }
        } catch (error) {
            console.error('Error eliminando producto', error)
        }
    }

    formProduct.addEventListener('submit', createProduct)
    btnUpdate.addEventListener('click', updateProduct)
    btnCreateProduct?.addEventListener('click', setCreateState)
    modalElement.addEventListener('hidden.bs.modal', setCreateState)

    tableElement.addEventListener('click', (e) => {
        if (e.target.closest('.btn-edit')) editProduct(e)
        if (e.target.closest('.btn-delete')) deleteProduct(e)
    })

    loadProducts()
}

function renderOptions(select, data, placeholder) {
    if (!select) return
    const previousValue = select.value
    const options = [`<option value="">${placeholder}</option>`]
    data.forEach((item) => {
        options.push(`<option value="${item.id}">${item.name}</option>`)
    })
    select.innerHTML = options.join('')
    if (previousValue) {
        select.value = previousValue
    }
}

function formatDecimal(value) {
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) return '0.0000'
    return numeric.toFixed(4)
}

function formatIntegerValue(value, min = 0) {
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) return String(min)
    return String(Math.max(min, Math.round(numeric)))
}
