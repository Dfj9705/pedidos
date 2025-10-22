import { Modal } from 'bootstrap'
import { Toast } from '../app'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const formatDecimal = (value) => {
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) return '0.0000'
    return numeric.toFixed(4)
}

const parseNumber = (value, fallback = 0) => {
    const numeric = Number(value)
    return Number.isFinite(numeric) ? numeric : fallback
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-order-form]')
    if (!form) return

    const modalElement = document.getElementById('modalOrder')
    const modal = modalElement ? new Modal(modalElement) : null
    const customerSelect = document.getElementById('orderCustomer')
    const addItemButton = document.getElementById('btnAddOrderItem')
    const itemsContainer = form.querySelector('[data-order-items]')
    const spinner = document.getElementById('spinnerSaveOrder')
    const submitButton = form.querySelector('button[type="submit"]')
    const codeInput = document.getElementById('orderCode')
    const summarySubtotal = form.querySelector('[data-order-subtotal]')
    const summaryDiscount = form.querySelector('[data-order-discount]')
    const summaryGrand = form.querySelector('[data-order-grand]')

    const emptyRowHtml = `<tr data-empty-row class="text-muted"><td colspan="6" class="text-center py-4">Agrega productos para calcular los totales del pedido.</td></tr>`

    const catalogs = {
        customers: [],
        products: [],
    }

    let rowCounter = 0

    const getItemRows = () => Array.from(itemsContainer?.querySelectorAll('tr[data-item-row]') || [])

    const ensurePlaceholder = () => {
        if (!itemsContainer) return
        if (!getItemRows().length) {
            if (!itemsContainer.querySelector('[data-empty-row]')) {
                itemsContainer.innerHTML = emptyRowHtml
            }
        } else {
            const placeholder = itemsContainer.querySelector('[data-empty-row]')
            if (placeholder) placeholder.remove()
        }
    }

    const renderCustomerOptions = () => {
        if (!customerSelect) return
        const previous = customerSelect.value
        const options = ['<option value="">Selecciona un cliente</option>']
        catalogs.customers.forEach((customer) => {
            options.push(`<option value="${customer.id}">${customer.name}</option>`)
        })
        customerSelect.innerHTML = options.join('')
        if (previous && catalogs.customers.some((c) => String(c.id) === previous)) {
            customerSelect.value = previous
        }
        customerSelect.disabled = catalogs.customers.length === 0
    }

    const getActiveProducts = () => catalogs.products.filter((product) => product.is_active !== false)

    const renderProductOptions = () => {
        const rows = getItemRows()
        rows.forEach((row) => {
            const select = row.querySelector('select[data-field="product_id"]')
            if (!select) return
            const previous = select.value
            const options = ['<option value="">Selecciona un producto</option>']
            getActiveProducts().forEach((product) => {
                const selected = String(product.id) === previous ? 'selected' : ''
                options.push(`<option value="${product.id}" ${selected}>${product.label || product.name}</option>`)
            })
            select.innerHTML = options.join('')
            select.disabled = getActiveProducts().length === 0
        })

        if (addItemButton) {
            addItemButton.disabled = getActiveProducts().length === 0
        }
    }

    const createItemRow = () => {
        if (!itemsContainer) return
        const products = getActiveProducts()
        const rowKey = String(++rowCounter)
        const row = document.createElement('tr')
        row.dataset.itemRow = 'true'
        row.dataset.key = rowKey

        const productOptions = ['<option value="">Selecciona un producto</option>']
        products.forEach((product) => {
            productOptions.push(`<option value="${product.id}">${product.label || product.name}</option>`)
        })

        row.innerHTML = `
      <td>
        <select class="form-select form-select-sm" name="items[${rowKey}][product_id]" data-field="product_id" required ${products.length ? '' : 'disabled'}>
          ${productOptions.join('')}
        </select>
        <div class="invalid-feedback" data-feedback="product_id"></div>
      </td>
      <td>
        <input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm text-end" name="items[${rowKey}][qty]" value="1" data-field="qty">
        <div class="invalid-feedback" data-feedback="qty"></div>
      </td>
      <td>
        <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end" name="items[${rowKey}][price]" value="0.0000" data-field="price">
        <div class="invalid-feedback" data-feedback="price"></div>
      </td>
      <td>
        <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end" name="items[${rowKey}][discount]" value="0.0000" data-field="discount">
        <div class="invalid-feedback" data-feedback="discount"></div>
      </td>
      <td class="text-end"><span data-line-total>0.0000</span></td>
      <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-item>
          <i class="bi bi-x-lg"></i>
        </button>
      </td>
    `

        removePlaceholder()
        itemsContainer.appendChild(row)

        const select = row.querySelector('select[data-field="product_id"]')
        if (products.length && select) {
            select.value = String(products[0].id)
            setProductFromSelect(select)
        } else {
            updateLineTotal(row)
            recalculateSummary()
        }
    }

    const removePlaceholder = () => {
        if (!itemsContainer) return
        const placeholder = itemsContainer.querySelector('[data-empty-row]')
        if (placeholder) placeholder.remove()
    }

    const updateLineTotal = (row) => {
        if (!row) return
        const qtyInput = row.querySelector('[data-field="qty"]')
        const priceInput = row.querySelector('[data-field="price"]')
        const discountInput = row.querySelector('[data-field="discount"]')
        const lineTotalElement = row.querySelector('[data-line-total]')
        if (!lineTotalElement) return

        const qty = parseNumber(qtyInput?.value, 0)
        const price = parseNumber(priceInput?.value, 0)
        const discount = parseNumber(discountInput?.value, 0)
        const total = Math.max(0, qty * (price - discount))
        lineTotalElement.textContent = formatDecimal(total)
    }

    const recalculateSummary = () => {
        let subtotal = 0
        let discountTotal = 0

        getItemRows().forEach((row) => {
            const qty = parseNumber(row.querySelector('[data-field="qty"]')?.value, 0)
            const price = parseNumber(row.querySelector('[data-field="price"]')?.value, 0)
            const discount = parseNumber(row.querySelector('[data-field="discount"]')?.value, 0)
            subtotal += qty * price
            discountTotal += qty * discount
        })

        const grandTotal = Math.max(0, subtotal - discountTotal)

        if (summarySubtotal) summarySubtotal.textContent = formatDecimal(subtotal)
        if (summaryDiscount) summaryDiscount.textContent = formatDecimal(discountTotal)
        if (summaryGrand) summaryGrand.textContent = formatDecimal(grandTotal)
    }

    const setProductFromSelect = (select) => {
        const row = select.closest('tr[data-item-row]')
        const product = getActiveProducts().find((item) => String(item.id) === select.value)
        const priceInput = row?.querySelector('[data-field="price"]')
        if (product && priceInput) {
            priceInput.value = formatDecimal(product.price)
        }
        updateLineTotal(row)
        recalculateSummary()
    }

    const resetForm = () => {
        form.reset()
        clearValidation()
        itemsContainer.innerHTML = emptyRowHtml
        rowCounter = 0
        if (customerSelect) {
            customerSelect.value = ''
        }
        recalculateSummary()
        ensurePlaceholder()
    }

    const clearValidation = () => {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'))
        form.querySelectorAll('.invalid-feedback').forEach((el) => (el.innerHTML = ''))
    }

    const showErrors = (errors = {}) => {
        Object.entries(errors).forEach(([key, messages]) => {
            const feedbackMessage = Array.isArray(messages) ? messages.join('<br>') : messages
            if (key.startsWith('items.')) {
                const [, rowKey, field] = key.split('.')
                const row = itemsContainer.querySelector(`tr[data-key="${rowKey}"]`)
                if (!row) return
                const control = row.querySelector(`[name="items[${rowKey}][${field}]"]`)
                if (control) control.classList.add('is-invalid')
                const feedback = row.querySelector(`[data-feedback="${field}"]`)
                if (feedback) feedback.innerHTML = feedbackMessage
                return
            }

            const control = form.querySelector(`[name="${key}"]`)
            if (control) {
                control.classList.add('is-invalid')
                const feedback = document.getElementById(`${control.id}Feedback`)
                if (feedback) feedback.innerHTML = feedbackMessage
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
            const response = await fetch(form.dataset.action || form.getAttribute('action') || '/orders', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
                credentials: 'include',
            })

            const payload = await response.json().catch(() => ({}))
            clearValidation()

            if (response.status === 422) {
                showErrors(payload.errors || {})
                submitButton.disabled = false
                spinner?.classList.add('d-none')
                return
            }

            if (!response.ok) {
                Toast.fire({ icon: 'error', title: 'No se pudo guardar el pedido' })
                submitButton.disabled = false
                spinner?.classList.add('d-none')
                return
            }

            Toast.fire({ icon: 'success', title: 'Pedido guardado correctamente' })
            modal?.hide()
            resetForm()
            document.dispatchEvent(new Event('orders:request-refresh'))
        } catch (error) {
            console.error('No se pudo enviar el formulario de pedidos', error)
            submitButton.disabled = false
        }

        spinner?.classList.add('d-none')
        submitButton.disabled = false
    }

    const handleItemEvents = (event) => {
        const select = event.target.closest('select[data-field="product_id"]')
        if (select) {
            setProductFromSelect(select)
            return
        }

        const input = event.target.closest('input[data-field]')
        if (input) {
            const row = input.closest('tr[data-item-row]')
            updateLineTotal(row)
            recalculateSummary()
        }
    }

    const handleRemoveItem = (event) => {
        const button = event.target.closest('[data-remove-item]')
        if (!button) return
        const row = button.closest('tr[data-item-row]')
        if (!row) return
        row.remove()
        ensurePlaceholder()
        recalculateSummary()
    }

    const handleModalShow = () => {
        if (!getItemRows().length) {
            ensurePlaceholder()
        }
        setTimeout(() => {
            codeInput?.focus()
        }, 100)
    }

    const handleModalHidden = () => {
        resetForm()
    }

    form.addEventListener('submit', handleSubmit)
    addItemButton?.addEventListener('click', (event) => {
        event.preventDefault()
        createItemRow()
    })
    itemsContainer?.addEventListener('input', handleItemEvents)
    itemsContainer?.addEventListener('change', handleItemEvents)
    itemsContainer?.addEventListener('click', handleRemoveItem)

    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', handleModalShow)
        modalElement.addEventListener('hidden.bs.modal', handleModalHidden)
    }

    document.addEventListener('orders:data-loaded', (event) => {
        const detail = event.detail || {}
        catalogs.customers = Array.isArray(detail.customers) ? detail.customers : []
        catalogs.products = Array.isArray(detail.products) ? detail.products : []
        renderCustomerOptions()
        renderProductOptions()
        ensurePlaceholder()
    })

    renderCustomerOptions()
    ensurePlaceholder()
    if (addItemButton) addItemButton.disabled = true
    if (customerSelect) customerSelect.disabled = true
    spinner?.classList.add('d-none')
})
