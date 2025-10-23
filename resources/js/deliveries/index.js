import DataTable from 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'
import { Toast } from '../app'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const statusLabels = {
  confirmed: 'Confirmado',
  shipped: 'Despachado',
  delivered: 'Entregado'
}

const formatDecimal = (value) => {
  const numeric = Number(value)
  if (!Number.isFinite(numeric)) return '0.0000'
  return numeric.toFixed(4)
}

document.addEventListener('DOMContentLoaded', () => {
  const tableElement = document.querySelector('#deliveriesTable')
  if (!tableElement) return

  const warehouseSelect = document.querySelector('[data-delivery-warehouse]')
  const dateInput = document.querySelector('[data-delivery-date]')

  const datatable = new DataTable(tableElement, {
    data: [],
    responsive: true,
    columns: [
      { title: 'Código', data: 'code', defaultContent: '-' },
      { title: 'Cliente', data: 'customer.name', defaultContent: '-' },
      {
        title: 'Estado',
        data: 'status',
        render: (value) => statusLabels[value] ?? value ?? '-',
        className: 'text-center'
      },
      {
        title: 'Total',
        data: 'grand_total',
        className: 'text-end',
        render: (value) => formatDecimal(value)
      },
      {
        title: 'Acciones',
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
        render: (data, type, row) => {
          if (row.status === 'delivered') {
            return '<span class="badge text-bg-success">Entregado</span>'
          }

          return `
            <button type="button" class="btn btn-sm btn-primary" data-deliver-order data-order-id="${row.id}">
              <span class="d-inline-flex align-items-center gap-1">
                <i class="bi bi-truck"></i>
                Entregar
              </span>
            </button>
          `
        }
      }
    ]
  })

  const renderWarehouses = (warehouses = []) => {
    if (!warehouseSelect) return
    const previous = warehouseSelect.value
    const options = ['<option value="">Seleccionar automáticamente</option>']

    warehouses.forEach((warehouse) => {
      const selected = previous && String(warehouse.id) === previous ? 'selected' : ''
      const label = warehouse.label || warehouse.name
      options.push(`<option value="${warehouse.id}" ${selected}>${label}</option>`)
    })

    warehouseSelect.innerHTML = options.join('')
    if (previous && !warehouses.some((warehouse) => String(warehouse.id) === previous)) {
      warehouseSelect.value = ''
    }
  }

  const loadDeliveries = async () => {
    try {
      const response = await fetch('/deliveries', {
        headers: { Accept: 'application/json' },
        credentials: 'include'
      })

      if (!response.ok) {
        Toast.fire({ icon: 'error', title: 'No se pudieron cargar los pedidos para entrega' })
        return
      }

      const payload = await response.json()
      const orders = Array.isArray(payload.orders) ? payload.orders : []
      const warehouses = Array.isArray(payload.warehouses) ? payload.warehouses : []

      datatable.clear()
      datatable.rows.add(orders)
      datatable.draw()

      renderWarehouses(warehouses)
    } catch (error) {
      console.error('Error cargando entregas', error)
    }
  }

  const deliverOrder = async (orderId, button) => {
    if (!orderId) return
    if (!window.confirm('¿Deseas marcar el pedido como entregado?')) return

    const originalHtml = button?.innerHTML
    if (button) {
      button.disabled = true
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Entregando...'
    }

    const formData = new FormData()
    if (warehouseSelect?.value) {
      formData.append('warehouse_id', warehouseSelect.value)
    }
    if (dateInput?.value) {
      formData.append('moved_at', dateInput.value)
    }

    try {
      const response = await fetch(`/orders/${orderId}/deliver`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json'
        },
        body: formData,
        credentials: 'include'
      })

      const payload = await response.json().catch(() => ({}))

      if (response.status === 422) {
        const message = Object.values(payload.errors || {})
          .flat()
          .find(Boolean)
        Toast.fire({ icon: 'error', title: message || 'No se pudo entregar el pedido' })
        return
      }

      if (!response.ok) {
        Toast.fire({ icon: 'error', title: 'No se pudo entregar el pedido' })
        return
      }

      Toast.fire({ icon: 'success', title: 'Pedido entregado correctamente' })
      await loadDeliveries()
      document.dispatchEvent(new Event('orders:request-refresh'))
    } catch (error) {
      console.error('Error entregando pedido', error)
      Toast.fire({ icon: 'error', title: 'Ocurrió un error al entregar el pedido' })
    } finally {
      if (button) {
        button.disabled = false
        button.innerHTML = originalHtml || 'Entregar'
      }
    }
  }

  tableElement.addEventListener('click', (event) => {
    const button = event.target.closest('[data-deliver-order]')
    if (!button) return
    const orderId = button.getAttribute('data-order-id')
    deliverOrder(orderId, button)
  })

  loadDeliveries()

  document.addEventListener('deliveries:request-refresh', () => {
    loadDeliveries()
  })
})
