import DataTable from 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'

document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('#ordersTable')
    if (!table) return

    const datatable = new DataTable(table, {
        data: [],
        responsive: true,
        columns: [
            { title: 'Código', data: 'code' },
            { title: 'Cliente', data: 'customer.name', defaultContent: '' },
            { title: 'Estado', data: 'status' },
            { title: 'Pago', data: 'payment_status' },
            {
                title: 'Total',
                data: 'grand_total',
                className: 'text-end',
                render: (data) => {
                    const value = data ?? 0
                    const numeric = Number(value || 0)
                    return Number.isFinite(numeric) ? numeric.toFixed(4) : '0.0000'
                }
            },
            {
                title: 'Entregado',
                data: 'delivered_at',
                defaultContent: '',
                render: (data) => data ? new Date(data).toLocaleString() : ''
            },
        ],
    })

    const emitCatalogs = (detail) => {
        document.dispatchEvent(new CustomEvent('orders:data-loaded', { detail }))
    }

    const loadOrders = async () => {
        try {
            const response = await fetch('/orders', {
                headers: { Accept: 'application/json' },
                credentials: 'include',
            })

            if (!response.ok) {
                console.warn('No se pudieron obtener los pedidos', response.status)
                return
            }

            const payload = await response.json()
            const orders = Array.isArray(payload.orders) ? payload.orders : []
            const customers = Array.isArray(payload.customers) ? payload.customers : []
            const products = Array.isArray(payload.products) ? payload.products : []

            datatable.clear()
            datatable.rows.add(orders)
            datatable.draw()

            emitCatalogs({ customers, products })
        } catch (error) {
            console.error('Error cargando pedidos', error)
        }
    }

    loadOrders()

    document.addEventListener('orders:request-refresh', () => {
        loadOrders()
    })
})
