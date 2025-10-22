import DataTable from 'datatables.net-bs5'
import 'datatables.net-buttons-bs5'
import 'datatables.net-buttons/js/buttons.html5.mjs'
import 'datatables.net-buttons/js/buttons.print.mjs'
import 'datatables.net-responsive-bs5'
import 'datatables.net-select-bs5'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'
import { Toast } from '../app'

const formBrand = document.querySelector('#formBrand')
const modalEl = document.getElementById('modalCreateBrand')
const modalBrand = new Modal(modalEl)
const title = document.getElementById('createBrandTitle')
const btnGuardar = document.getElementById('btnGuardar')
const btnModificar = document.getElementById('btnModificar')
const spinnerGuardar = document.getElementById('spinnerGuardar')

btnModificar.style.display = 'none'
btnModificar.disabled = true
spinnerGuardar.style.display = 'none'

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

const dt = new DataTable('#brandTable', {
    data: [],
    responsive: true,
    select: false,
    columns: [
        {
            title: 'No.',
            data: null,
            render: (data, type, row, meta) => meta.row + 1,
            className: 'text-center',
            width: '60px'
        },
        { title: 'Nombre', data: 'name' },
        { title: 'Descripción', data: 'description', defaultContent: '' },
        {
            title: 'Opciones',
            data: 'id',
            orderable: false,
            className: 'text-center',
            render: (id, type, row) => `
        <div class="btn-group" role="group">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${id}" data-name="${row.name}" data-description="${row.description ?? ''}" data-bs-toggle="modal" data-bs-target="#modalCreateBrand">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-danger btn-delete" data-id="${id}">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      `
        },
    ]
})

function clearValidation() {
    formBrand.querySelectorAll('input,textarea').forEach(i => i.classList.remove('is-invalid'))
    formBrand.querySelectorAll('[id$="Feedback"]').forEach(f => f.innerHTML = '')
}

function paintErrors(errors = {}) {
    for (const prop in errors) {
        const el = document.getElementById(prop)
        if (!el) continue
        el.classList.add('is-invalid')
        const fb = document.getElementById(prop + 'Feedback')
        if (fb) fb.innerHTML = errors[prop].join('<br>')
    }
}

async function getBrands() {
    try {
        const resp = await fetch('/brands', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'include'
        })
        const data = await resp.json()
        const { brands } = data

        const pageInfo = dt.page.info()
        const page = pageInfo.page
        const scrollY = window.scrollY

        dt.clear()
        if (Array.isArray(brands) && brands.length) {
            dt.rows.add(brands).draw()
            dt.page(page).draw('page')
            window.scrollTo(0, scrollY)
        } else {
            dt.draw()
            Toast.fire({ icon: 'info', title: 'No se encontraron marcas' })
        }
    } catch (e) { console.error(e) }
}

async function createBrand(e) {
    e.preventDefault()
    spinnerGuardar.style.display = ''
    btnGuardar.disabled = true

    const body = new FormData(formBrand)
    try {
        const resp = await fetch('/brands', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body,
            credentials: 'include'
        })
        const data = await resp.json()
        clearValidation()

        if (resp.status === 422) {
            paintErrors(data.errors)
        } else if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Marca creada correctamente' })
            formBrand.reset()
            modalBrand.hide()
            await getBrands()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
        }
    } catch (e) { console.error(e) }

    spinnerGuardar.style.display = 'none'
    btnGuardar.disabled = false
}

let currentUpdateId = null

function editBrand(e) {
    const btn = e.target.closest('button[data-id]')
    if (!btn) return
    document.getElementById('name').value = btn.dataset.name
    document.getElementById('description').value = btn.dataset.description || ''
    title.textContent = 'Editar marca'
    btnGuardar.style.display = 'none'
    btnGuardar.disabled = true
    btnModificar.style.display = ''
    btnModificar.disabled = false
    currentUpdateId = btn.dataset.id
}

function resetModal() {
    if (!currentUpdateId) {
        formBrand.reset()
        clearValidation()
        title.textContent = 'Crear marca'
        btnModificar.style.display = 'none'
        btnModificar.disabled = true
        btnGuardar.style.display = ''
        btnGuardar.disabled = false
    }
}

async function updateBrand(e) {
    e.preventDefault()
    if (!currentUpdateId) return
    const body = new FormData(formBrand)
    body.append('_method', 'PUT')
    try {
        const resp = await fetch(`/brands/${currentUpdateId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body,
            credentials: 'include'
        })
        const data = await resp.json()
        clearValidation()
        if (resp.status === 422) {
            paintErrors(data.errors)
        } else if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Marca actualizada correctamente' })
            formBrand.reset()
            modalBrand.hide()
            await getBrands()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
        }
    } catch (e) { console.error(e) }
}

async function deleteBrand(e) {
    const btn = e.target.closest('button.btn-delete')
    if (!btn) return
    const id = btn.dataset.id

    const result = await Swal.fire({
        icon: 'warning',
        text: '¿Está seguro que desea eliminar esta marca?',
        title: 'Confirmación',
        showCancelButton: true,
        confirmButtonColor: '#E5533D',
        confirmButtonText: 'Sí',
        cancelButtonText: 'Cancelar'
    })
    if (!result.isConfirmed) return

    try {
        const resp = await fetch(`/brands/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            credentials: 'include'
        })
        if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Marca eliminada' })
            await getBrands()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
        }
    } catch (e) { console.error(e) }
}

formBrand.addEventListener('submit', createBrand)
btnModificar.addEventListener('click', updateBrand)
modalEl.addEventListener('show.bs.modal', resetModal)
modalEl.addEventListener('hidden.bs.modal', () => {
    currentUpdateId = null
    resetModal()
})

document.querySelector('#brandTable').addEventListener('click', (e) => {
    if (e.target.closest('.btn-edit')) editBrand(e)
    if (e.target.closest('.btn-delete')) deleteBrand(e)
})

getBrands()
