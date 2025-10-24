import DataTable from 'datatables.net-bs5'
import 'datatables.net-buttons-bs5'
import 'datatables.net-buttons/js/buttons.html5.mjs'
import 'datatables.net-buttons/js/buttons.print.mjs'
import 'datatables.net-responsive-bs5'
import 'datatables.net-select-bs5'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'
import { Toast } from '../app'

const formatCoordinate = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    const numeric = Number(value)
    return Number.isFinite(numeric) ? numeric.toFixed(6) : String(value)
}

const formCustomer = document.querySelector('#formCustomer')
const modalEl = document.getElementById('modalCustomer')
const modalCustomer = new Modal(modalEl)
const btnCreateCustomer = document.getElementById('btnCreateCustomer')
const title = document.getElementById('customerModalTitle')
const btnSave = document.getElementById('btnSaveCustomer')
const btnUpdate = document.getElementById('btnUpdateCustomer')
const spinnerSave = document.getElementById('spinnerSaveCustomer')
const inputName = document.getElementById('name')
const inputPhone = document.getElementById('phone')
const inputEmail = document.getElementById('email')
const inputAddress = document.getElementById('address')
const inputLatitude = document.getElementById('latitude')
const inputLongitude = document.getElementById('longitude')

btnUpdate.style.display = 'none'
btnUpdate.disabled = true
spinnerSave.style.display = 'none'

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

const dt = new DataTable('#customerTable', {
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
        { title: 'Teléfono', data: 'phone', defaultContent: '' },
        { title: 'Correo electrónico', data: 'email', defaultContent: '' },
        { title: 'Dirección', data: 'address', defaultContent: '' },
        {
            title: 'Latitud',
            data: 'latitude',
            className: 'text-end',
            render: (value) => formatCoordinate(value)
        },
        {
            title: 'Longitud',
            data: 'longitude',
            className: 'text-end',
            render: (value) => formatCoordinate(value)
        },
        {
            title: 'Opciones',
            data: 'id',
            orderable: false,
            className: 'text-center',
            render: (id, type, row) => `
        <div class="btn-group" role="group">
          <button class="btn btn-sm btn-warning btn-edit" data-id="${id}" data-name="${encodeURIComponent(row.name ?? '')}" data-phone="${encodeURIComponent(row.phone ?? '')}" data-email="${encodeURIComponent(row.email ?? '')}" data-address="${encodeURIComponent(row.address ?? '')}" data-latitude="${encodeURIComponent(row.latitude ?? '')}" data-longitude="${encodeURIComponent(row.longitude ?? '')}" data-bs-toggle="modal" data-bs-target="#modalCustomer">
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
    formCustomer.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'))
    formCustomer.querySelectorAll('[id$="Feedback"]').forEach((el) => (el.innerHTML = ''))
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

async function getCustomers() {
    try {
        const resp = await fetch('/customers', {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'include'
        })
        const data = await resp.json()
        const { customers } = data

        const pageInfo = dt.page.info()
        const page = pageInfo.page
        const scrollY = window.scrollY

        dt.clear()
        if (Array.isArray(customers) && customers.length) {
            dt.rows.add(customers).draw()
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

async function createCustomer(e) {
    e.preventDefault()
    spinnerSave.style.display = ''
    btnSave.disabled = true

    const body = new FormData(formCustomer)

    try {
        const resp = await fetch('/customers', {
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
            Toast.fire({ icon: 'success', title: 'Cliente creado correctamente' })
            formCustomer.reset()
            modalCustomer.hide()
            await getCustomers()
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
    title.textContent = 'Crear cliente'
    btnSave.style.display = ''
    btnSave.disabled = false
    btnUpdate.style.display = 'none'
    btnUpdate.disabled = true
    spinnerSave.style.display = 'none'
    clearValidation()
    formCustomer.reset()
    if (inputLatitude) inputLatitude.value = ''
    if (inputLongitude) inputLongitude.value = ''
}

function editCustomer(e) {
    const btn = e.target.closest('button.btn-edit')
    if (!btn) return

    currentUpdateId = btn.dataset.id
    title.textContent = 'Editar cliente'
    btnSave.style.display = 'none'
    btnSave.disabled = true
    btnUpdate.style.display = ''
    btnUpdate.disabled = false
    clearValidation()

    inputName.value = decodeURIComponent(btn.dataset.name || '')
    inputPhone.value = decodeURIComponent(btn.dataset.phone || '')
    inputEmail.value = decodeURIComponent(btn.dataset.email || '')
    inputAddress.value = decodeURIComponent(btn.dataset.address || '')
    inputLatitude.value = decodeURIComponent(btn.dataset.latitude || '')
    inputLongitude.value = decodeURIComponent(btn.dataset.longitude || '')
}

async function updateCustomer(e) {
    e.preventDefault()
    if (!currentUpdateId) return

    btnUpdate.disabled = true
    const body = new FormData(formCustomer)
    body.append('_method', 'PUT')

    try {
        const resp = await fetch(`/customers/${currentUpdateId}`, {
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
            Toast.fire({ icon: 'success', title: 'Cliente actualizado correctamente' })
            modalCustomer.hide()
            await getCustomers()
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

async function deleteCustomer(e) {
    const btn = e.target.closest('button.btn-delete')
    if (!btn) return
    const id = btn.dataset.id

    const result = await Swal.fire({
        icon: 'warning',
        text: '¿Está seguro que desea eliminar este cliente?',
        title: 'Confirmación',
        showCancelButton: true,
        confirmButtonColor: '#E5533D',
        confirmButtonText: 'Sí',
        cancelButtonText: 'Cancelar'
    })
    if (!result.isConfirmed) return

    try {
        const resp = await fetch(`/customers/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            credentials: 'include'
        })
        if (resp.ok) {
            Toast.fire({ icon: 'success', title: 'Cliente eliminado' })
            await getCustomers()
        } else {
            Toast.fire({ icon: 'error', title: 'Contacte al administrador' })
        }
    } catch (e) {
        console.error(e)
    }
}

formCustomer.addEventListener('submit', createCustomer)
btnUpdate.addEventListener('click', updateCustomer)
btnCreateCustomer.addEventListener('click', setCreateState)
modalEl.addEventListener('hidden.bs.modal', setCreateState)

document.querySelector('#customerTable').addEventListener('click', (e) => {
    if (e.target.closest('.btn-edit')) editCustomer(e)
    if (e.target.closest('.btn-delete')) deleteCustomer(e)
})

getCustomers()
