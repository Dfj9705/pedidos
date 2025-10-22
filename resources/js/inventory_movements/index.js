import DataTable from 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'

const table = new DataTable('#inventoryMovementsTable', {
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
        const labels = {
          in: 'Entrada',
          out: 'Salida',
          transfer: 'Transferencia',
          adjustment: 'Ajuste'
        }

        return labels[value] ?? value
      }
    },
    {
      title: 'Fecha de movimiento',
      data: 'moved_at',
      render: (value) => value ?? '-'
    },
    { title: 'Origen', data: 'origin', defaultContent: '-' },
    { title: 'Destino', data: 'target', defaultContent: '-' },
    {
      title: 'Detalles',
      data: 'details_count',
      className: 'text-center',
      render: (value) => value ?? 0
    }
  ]
})

async function loadMovements() {
  try {
    const response = await fetch('/inventory-movements', {
      headers: { Accept: 'application/json' },
      credentials: 'include'
    })

    if (!response.ok) {
      throw new Error('Failed to load inventory movements')
    }

    const payload = await response.json()
    const movements = Array.isArray(payload.data)
      ? payload.data
      : Array.isArray(payload.movements)
        ? payload.movements
        : []

    table.clear()
    table.rows.add(movements)
    table.draw()
  } catch (error) {
    console.error('Error fetching inventory movements', error)
  }
}

loadMovements()
