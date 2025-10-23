import DataTable from 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'

const typeLabels = {
  in: 'Entrada',
  out: 'Salida',
  transfer: 'Transferencia',
  adjustment: 'Ajuste'
}

const formatDateTime = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString()
}

document.addEventListener('DOMContentLoaded', () => {
  const tableElement = document.querySelector('#inventoryMovementsTable')
  if (!tableElement) return

  const datatable = new DataTable(tableElement, {
    data: [],
    responsive: true,
    searching: false,
    paging: false,
    info: false,
    columns: [
      { title: 'Código', data: 'code', defaultContent: '-' },
      {
        title: 'Tipo',
        data: 'type',
        render: (value) => {
          const label = typeLabels[value] ?? value ?? '-'
          return `<span class="badge bg-light text-dark fw-semibold">${label}</span>`
        },
        className: 'text-center'
      },
      {
        title: 'Fecha de movimiento',
        data: 'moved_at',
        render: formatDateTime
      },
      { title: 'Origen', data: 'origin', defaultContent: '-' },
      { title: 'Destino', data: 'target', defaultContent: '-' },
      {
        title: 'Productos',
        data: 'details_count',
        className: 'text-center',
        render: (value) => (typeof value === 'number' ? value : value ?? 0)
      }
    ]
  })

  const emitCatalogs = (detail) => {
    document.dispatchEvent(new CustomEvent('inventory-movements:data-loaded', { detail }))
  }

  const loadMovements = async () => {
    try {
      const response = await fetch('/inventory-movements', {
        headers: { Accept: 'application/json' },
        credentials: 'include'
      })

      if (!response.ok) {
        console.warn('No se pudieron obtener los movimientos de inventario', response.status)
        return
      }

      const payload = await response.json()
      const movements = Array.isArray(payload.data)
        ? payload.data
        : Array.isArray(payload.movements)
          ? payload.movements
          : []
      const warehouses = Array.isArray(payload.warehouses) ? payload.warehouses : []
      const products = Array.isArray(payload.products) ? payload.products : []

      datatable.clear()
      datatable.rows.add(movements)
      datatable.draw()

      emitCatalogs({ warehouses, products })
    } catch (error) {
      console.error('Error fetching inventory movements', error)
    }
  }

  loadMovements()

  document.addEventListener('inventory-movements:request-refresh', () => {
    loadMovements()
  })
})
