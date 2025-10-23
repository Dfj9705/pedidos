import DataTable from 'datatables.net-bs5'
import 'datatables.net-buttons-bs5'
import 'datatables.net-buttons/js/buttons.html5.mjs'
import 'datatables.net-buttons/js/buttons.print.mjs'
import 'datatables.net-responsive-bs5'
import 'datatables.net-select-bs5'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'
import { Toast } from '../app'

const formWarehouse = document.querySelector('#formWarehouse')
const modalEl = document.getElementById('modalWarehouse')
const modalWarehouse = new Modal(modalEl)
const btnCreateWarehouse = document.getElementById('btnCreateWarehouse')
const title = document.getElementById('warehouseModalTitle')
const btnSave = document.getElementById('btnSaveWarehouse')
const btnUpdate = document.getElementById('btnUpdateWarehouse')
const spinnerSave = document.getElementById('spinnerSaveWarehouse')
const inputName = document.getElementById('name')
const inputCode = document.getElementById('code')
const inputIsRoute = document.getElementById('is_route')
const inputLatitude = document.getElementById('latitude')
const inputLongitude = document.getElementById('longitude')

btnUpdate.style.display = 'none'
btnUpdate.disabled = true
spinnerSave.style.display = 'none'

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

const dt = new DataTable('#warehouseTable', {
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
        { title: 'Código', data: 'code' },
        {
            title: 'En ruta',
            data: 'is_route',
            className: 'text-center',
            render: (value) => (value ? 'Sí' : 'No'),
            width: '90px'
        },
        {
            title: 'Opciones',
            data: 'id',
            orderable: false,
            className: 'text-center',
            render: (id, type, row) => `
        <div class="btn-group" role="group">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${id}" data-name="${encodeURIComponent(row.name ?? '')}" data-code="${encodeURIComponent(row.code ?? '')}" data-is-route="${row.is_route ? 1 : 0}" data-latitude="${encodeURIComponent(row.latitude ?? '')}" data-longitude="${encodeURIComponent(row.longitude ?? '')}" data-bs-toggle="modal" data-bs-target="#modalWarehouse">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-danger btn-delete" data-id="${id}">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      `
        }
    ]
})

function clearValidation() {
    formWarehouse.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'))
    formWarehouse.querySelectorAll('[id$="Feedback"]').forEach((el) => (el.innerHTML = ''))
}

function paintErrors(errors = {}) {
    for (const prop in errors) {
        const el = document.getElementById(prop)
        if (!el) continue
        el.classList.add('is-invalid')
        const fb = document.getElementById(`${prop}Feedback`)
        if (fb) fb.innerHTML = errors[prop].join('<br>')
    }
}

async function getWarehouses() {
    try {
        const resp = await fetch('/warehouses', {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'include'
        })
        const data = await resp.json()
        const { warehouses } = data

        const pageInfo = dt.page.info()
        const page = pageInfo.page
        const scrollY = window.scrollY

        dt.clear()
        if (Array.isArray(warehouses) && warehouses.length) {
            dt.rows.add(warehouses).draw()
            dt.page(page).draw('page')
            window.scrollTo(0, scrollY)
        } else {
            dt.draw()
            Toast.fire({ icon: 'info', title: 'No se encontraron registros' })
        }
    } catch (e) {
        console.error(e)
    }
}

async function createWarehouse(e) {
    e.preventDefault()
    spinnerSave.style.display = ''
    btnSave.disabled = true

    const body = new FormData(formWarehouse)
    body.set('is_route', inputIsRoute.checked ? '1' : '0')

    try {
        const resp = await fetch('/warehouses', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body,
            credentials: 'include'
        })
        const data = await resp.json()
        clearValidation()

        if (resp.status === 422) {
            paintErrors(data.errors)
        } else if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Almacén creado correctamente' })
            formWarehouse.reset()
            inputIsRoute.checked = false
            modalWarehouse.hide()
            await getWarehouses()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
        }
    } catch (e) {
        console.error(e)
    }

    spinnerSave.style.display = 'none'
    btnSave.disabled = false
}

let currentUpdateId = null

function setCreateState() {
    currentUpdateId = null
    title.textContent = 'Crear almacén'
    btnSave.style.display = ''
    btnSave.disabled = false
    btnUpdate.style.display = 'none'
    btnUpdate.disabled = true
    spinnerSave.style.display = 'none'
    clearValidation()
    formWarehouse.reset()
    inputIsRoute.checked = false
    if (inputLatitude) inputLatitude.value = ''
    if (inputLongitude) inputLongitude.value = ''
}

function editWarehouse(e) {
    const btn = e.target.closest('button.btn-edit')
    if (!btn) return

    currentUpdateId = btn.dataset.id
    title.textContent = 'Editar almacén'
    btnSave.style.display = 'none'
    btnSave.disabled = true
    btnUpdate.style.display = ''
    btnUpdate.disabled = false
    clearValidation()

    inputName.value = decodeURIComponent(btn.dataset.name || '')
    inputCode.value = decodeURIComponent(btn.dataset.code || '')
    inputIsRoute.checked = btn.dataset.isRoute === '1'
    inputLatitude.value = decodeURIComponent(btn.dataset.latitude || '')
    inputLongitude.value = decodeURIComponent(btn.dataset.longitude || '')
}

async function updateWarehouse(e) {
    e.preventDefault()
    if (!currentUpdateId) return

    btnUpdate.disabled = true
    const body = new FormData(formWarehouse)
    body.append('_method', 'PUT')
    body.set('is_route', inputIsRoute.checked ? '1' : '0')

    try {
        const resp = await fetch(`/warehouses/${currentUpdateId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body,
            credentials: 'include'
        })
        const data = await resp.json()
        clearValidation()

        if (resp.status === 422) {
            paintErrors(data.errors)
            btnUpdate.disabled = false
            return
        }

        if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Almacén actualizado correctamente' })
            modalWarehouse.hide()
            await getWarehouses()
            setCreateState()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
            btnUpdate.disabled = false
        }
    } catch (e) {
        console.error(e)
        btnUpdate.disabled = false
    }
}

async function deleteWarehouse(e) {
    const btn = e.target.closest('button.btn-delete')
    if (!btn) return
    const id = btn.dataset.id

    const result = await Swal.fire({
        icon: 'warning',
        text: '¿Está seguro que desea eliminar este almacén?',
        title: 'Confirmación',
        showCancelButton: true,
        confirmButtonColor: '#E5533D',
        confirmButtonText: 'Sí',
        cancelButtonText: 'Cancelar'
    })
    if (!result.isConfirmed) return

    try {
        const resp = await fetch(`/warehouses/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            credentials: 'include'
        })
        if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Almacén eliminado' })
            await getWarehouses()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
        }
    } catch (e) {
        console.error(e)
    }
}

formWarehouse.addEventListener('submit', createWarehouse)
btnUpdate.addEventListener('click', updateWarehouse)
btnCreateWarehouse.addEventListener('click', setCreateState)
modalEl.addEventListener('hidden.bs.modal', setCreateState)

document.querySelector('#warehouseTable').addEventListener('click', (e) => {
    if (e.target.closest('.btn-edit')) editWarehouse(e)
    if (e.target.closest('.btn-delete')) deleteWarehouse(e)
})

getWarehouses()
