import { Modal } from 'bootstrap'
import { Toast } from '../app'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const formatDecimal = (value) => {
  const numeric = Number(value)
  if (!Number.isFinite(numeric)) {
    return '0.0000'
  }
  return numeric.toFixed(4)
}

const formatDateTimeLocal = (date) => {
  const pad = (value) => String(value).padStart(2, '0')
  const year = date.getFullYear()
  const month = pad(date.getMonth() + 1)
  const day = pad(date.getDate())
  const hours = pad(date.getHours())
  const minutes = pad(date.getMinutes())
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-inventory-movement-form]')
  if (!form) return

  const modalElement = document.getElementById('modalInventoryMovement')
  const modal = modalElement ? new Modal(modalElement) : null
  const typeSelect = form.querySelector('#movementType')
  const originSelect = form.querySelector('#movementOrigin')
  const targetSelect = form.querySelector('#movementTarget')
  const movedAtInput = form.querySelector('#movementDate')
  const itemsContainer = form.querySelector('[data-inventory-items]')
  const itemsFeedback = form.querySelector('[data-items-feedback]')
  const addItemButton = form.querySelector('[data-add-item]')
  const submitButton = form.querySelector('button[type="submit"]')
  const spinner = form.querySelector('[data-submit-spinner]')
  const codeInput = form.querySelector('#movementCode')

  const catalogs = {
    warehouses: [],
    products: []
  }

  let rowCounter = 0

  const emptyRowHtml = '<tr data-empty-row class="text-muted"><td colspan="4" class="text-center py-4">Agrega productos para registrar el movimiento.</td></tr>'

  const getItemRows = () => Array.from(itemsContainer?.querySelectorAll('tr[data-item-row]') || [])

  const ensurePlaceholder = () => {
    if (!itemsContainer) return
    const hasRows = getItemRows().length > 0
    const placeholder = itemsContainer.querySelector('[data-empty-row]')
    if (!hasRows && !placeholder) {
      itemsContainer.innerHTML = emptyRowHtml
    }
    if (hasRows && placeholder) {
      placeholder.remove()
    }
  }

  const clearItemsFeedback = () => {
    if (!itemsFeedback) return
    itemsFeedback.classList.add('d-none')
    itemsFeedback.innerHTML = ''
  }

  const showItemsFeedback = (message) => {
    if (!itemsFeedback) return
    itemsFeedback.classList.remove('d-none')
    itemsFeedback.innerHTML = message
  }

  const getAvailableProducts = () => catalogs.products

  const renderWarehouseOptions = () => {
    const buildOptions = (select) => {
      if (!select) return
      const previous = select.value
      const options = ['<option value="">Selecciona un almacén</option>']
      catalogs.warehouses.forEach((warehouse) => {
        const selected = String(warehouse.id) === previous ? 'selected' : ''
        const label = warehouse.label || warehouse.name
        options.push(`<option value="${warehouse.id}" ${selected}>${label}</option>`)
      })
      select.innerHTML = options.join('')
      if (previous && !catalogs.warehouses.some((warehouse) => String(warehouse.id) === previous)) {
        select.value = ''
      }
      select.disabled = catalogs.warehouses.length === 0
    }

    buildOptions(originSelect)
    buildOptions(targetSelect)
  }

  const renderProductOptions = () => {
    const products = getAvailableProducts()
    const rows = getItemRows()
    rows.forEach((row) => {
      const select = row.querySelector('select[data-field="product_id"]')
      if (!select) return
      const previous = select.value
      const options = ['<option value="">Selecciona un producto</option>']
      products.forEach((product) => {
        const selected = String(product.id) === previous ? 'selected' : ''
        options.push(`<option value="${product.id}" ${selected}>${product.label || product.name}</option>`)
      })
      select.innerHTML = options.join('')
      if (previous && !products.some((product) => String(product.id) === previous)) {
        select.value = ''
      }
      select.disabled = products.length === 0
    })

    if (addItemButton) {
      addItemButton.disabled = products.length === 0
    }
  }

  const updateWarehouseAvailability = () => {
    const type = typeSelect?.value || 'in'
    const requiresOrigin = ['out', 'transfer'].includes(type)
    const requiresTarget = ['in', 'transfer'].includes(type)

    if (originSelect) {
      originSelect.required = requiresOrigin
      originSelect.disabled = !requiresOrigin || catalogs.warehouses.length === 0
      if (!requiresOrigin) {
        originSelect.value = ''
      }
    }

    if (targetSelect) {
      targetSelect.required = requiresTarget
      targetSelect.disabled = !requiresTarget || catalogs.warehouses.length === 0
      if (!requiresTarget) {
        targetSelect.value = ''
      }
    }
  }

  const setProductFromSelect = (select) => {
    const productId = select.value
    const product = getAvailableProducts().find((item) => String(item.id) === productId)
    const row = select.closest('tr[data-item-row]')
    if (!row) return
    const costInput = row.querySelector('input[data-field="unit_cost"]')
    if (product && costInput && !costInput.dataset.touched) {
      costInput.value = formatDecimal(product.cost ?? 0)
    }
  }

  const createItemRow = () => {
    if (!itemsContainer) return
    const products = getAvailableProducts()
    if (!products.length) return
    const rowKey = String(++rowCounter)
    const row = document.createElement('tr')
    row.dataset.itemRow = 'true'
    row.dataset.key = rowKey
    row.innerHTML = `
      <td>
        <select class="form-select form-select-sm" name="items[${rowKey}][product_id]" data-field="product_id" required>
          <option value="">Selecciona un producto</option>
        </select>
        <div class="invalid-feedback" data-feedback="product_id"></div>
      </td>
      <td>
        <input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm text-end" name="items[${rowKey}][qty]" value="1" data-field="qty">
        <div class="invalid-feedback" data-feedback="qty"></div>
      </td>
      <td>
        <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end" name="items[${rowKey}][unit_cost]" value="0.0000" data-field="unit_cost">
        <div class="invalid-feedback" data-feedback="unit_cost"></div>
      </td>
      <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-item>
          <i class="bi bi-x-lg"></i>
        </button>
      </td>
    `

    if (itemsContainer.querySelector('[data-empty-row]')) {
      itemsContainer.innerHTML = ''
    }

    itemsContainer.appendChild(row)
    renderProductOptions()
    const select = row.querySelector('select[data-field="product_id"]')
    if (products.length && select) {
      select.value = String(products[0].id)
      setProductFromSelect(select)
    }
    ensurePlaceholder()
  }

  const resetForm = () => {
    form.reset()
    rowCounter = 0
    if (itemsContainer) {
      itemsContainer.innerHTML = emptyRowHtml
    }
    clearValidation()
    clearItemsFeedback()
    renderWarehouseOptions()
    renderProductOptions()
    updateWarehouseAvailability()
    ensurePlaceholder()
  }

  const clearValidation = () => {
    form.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'))
    form.querySelectorAll('.invalid-feedback').forEach((element) => {
      element.innerHTML = ''
    })
  }

  const showErrors = (errors = {}) => {
    clearItemsFeedback()
    Object.entries(errors).forEach(([key, messages]) => {
      const message = Array.isArray(messages) ? messages.join('<br>') : messages
      if (key.startsWith('items.')) {
        const [, rowKey, field] = key.split('.')
        const row = itemsContainer?.querySelector(`tr[data-key="${rowKey}"]`)
        if (!row) return
        const control = row.querySelector(`[name="items[${rowKey}][${field}]"]`)
        if (control) {
          control.classList.add('is-invalid')
        }
        const feedback = row.querySelector(`[data-feedback="${field}"]`)
        if (feedback) {
          feedback.innerHTML = message
        }
        return
      }

      if (key === 'items') {
        showItemsFeedback(message)
        return
      }

      const control = form.querySelector(`[name="${key}"]`)
      if (control) {
        control.classList.add('is-invalid')
        const feedback = document.getElementById(`${control.id}Feedback`)
        if (feedback) {
          feedback.innerHTML = message
        }
      }
    })
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (!submitButton) return

    const formData = new FormData(form)

    submitButton.disabled = true
    spinner?.classList.remove('d-none')

    try {
      const response = await fetch(form.getAttribute('action') || '/inventory-movements', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData,
        credentials: 'include'
      })

      const payload = await response.json().catch(() => ({}))
      clearValidation()

      if (response.status === 422) {
        showErrors(payload.errors || {})
        const message = payload.message || 'No se pudo registrar el movimiento'
        if (message) {
          Toast.fire({ icon: 'error', title: message })
        }
        submitButton.disabled = false
        spinner?.classList.add('d-none')
        return
      }

      if (!response.ok) {
        Toast.fire({ icon: 'error', title: 'No se pudo registrar el movimiento' })
        submitButton.disabled = false
        spinner?.classList.add('d-none')
        return
      }

      Toast.fire({ icon: 'success', title: 'Movimiento guardado correctamente' })
      modal?.hide()
      resetForm()
      document.dispatchEvent(new Event('inventory-movements:request-refresh'))
    } catch (error) {
      console.error('No se pudo registrar el movimiento de inventario', error)
      Toast.fire({ icon: 'error', title: 'Error al registrar el movimiento' })
    }

    spinner?.classList.add('d-none')
    submitButton.disabled = false
  }

  const handleRemoveItem = (event) => {
    const button = event.target.closest('[data-remove-item]')
    if (!button) return
    const row = button.closest('tr[data-item-row]')
    if (!row) return
    row.remove()
    ensurePlaceholder()
    renderProductOptions()
  }

  const handleChange = (event) => {
    const select = event.target.closest('select[data-field="product_id"]')
    if (select) {
      setProductFromSelect(select)
      return
    }

    const costInput = event.target.closest('input[data-field="unit_cost"]')
    if (costInput) {
      costInput.dataset.touched = 'true'
    }
  }

  const handleModalShow = () => {
    renderWarehouseOptions()
    renderProductOptions()
    updateWarehouseAvailability()
    ensurePlaceholder()
    clearItemsFeedback()
    if (codeInput && !codeInput.value) {
      const now = new Date()
      codeInput.value = `MOV-${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}-${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}`
    }
    if (movedAtInput) {
      movedAtInput.value = formatDateTimeLocal(new Date())
    }
    if (!getItemRows().length && getAvailableProducts().length) {
      createItemRow()
    }
    setTimeout(() => {
      codeInput?.focus()
    }, 100)
  }

  const handleModalHidden = () => {
    resetForm()
  }

  form.addEventListener('submit', handleSubmit)
  itemsContainer?.addEventListener('click', handleRemoveItem)
  itemsContainer?.addEventListener('change', handleChange)
  addItemButton?.addEventListener('click', (event) => {
    event.preventDefault()
    createItemRow()
  })
  typeSelect?.addEventListener('change', () => {
    updateWarehouseAvailability()
  })

  if (modalElement) {
    modalElement.addEventListener('shown.bs.modal', handleModalShow)
    modalElement.addEventListener('hidden.bs.modal', handleModalHidden)
  }

  document.addEventListener('inventory-movements:data-loaded', (event) => {
    const detail = event.detail || {}
    catalogs.warehouses = Array.isArray(detail.warehouses) ? detail.warehouses : []
    catalogs.products = Array.isArray(detail.products) ? detail.products : []
    renderWarehouseOptions()
    renderProductOptions()
    updateWarehouseAvailability()
    ensurePlaceholder()
    if (modalElement?.classList.contains('show') && !getItemRows().length && catalogs.products.length) {
      createItemRow()
    }
  })

  renderWarehouseOptions()
  renderProductOptions()
  updateWarehouseAvailability()
  ensurePlaceholder()
  spinner?.classList.add('d-none')
  if (addItemButton) addItemButton.disabled = true
})
