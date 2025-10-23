import DataTable from 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'
import { Toast } from '../app'

document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('#ordersTable')
    if (!table) return

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

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
            {
                title: 'Acciones',
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: (data, type, row) => {
                    if (row?.payment_status === 'paid') {
                        return '<span class="badge text-bg-success">Pagado</span>'
                    }

                    return `
                        <button type="button" class="btn btn-sm btn-outline-success" data-action="mark-paid" data-order-id="${row?.id ?? ''}">
                            <i class="bi bi-cash-coin me-1"></i>
                            Marcar pagado
                        </button>
                    `
                }
            }
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

    table.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="mark-paid"]')
        if (!button) return

        const orderId = button.getAttribute('data-order-id')
        if (!orderId) return

        const markPaid = async () => {
            if (!window.confirm('¿Deseas marcar este pedido como pagado?')) {
                return
            }

            const originalHtml = button.innerHTML
            button.disabled = true
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Actualizando'

            try {
                const response = await fetch(`/orders/${orderId}/payment-status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ payment_status: 'paid' }),
                    credentials: 'include',
                })

                const payload = await response.json().catch(() => ({}))

                if (response.status === 422) {
                    const message = Object.values(payload.errors || {})
                        .flat()
                        .find(Boolean)
                    Toast.fire({ icon: 'error', title: message || 'No se pudo actualizar el pago' })
                    return
                }

                if (!response.ok) {
                    Toast.fire({ icon: 'error', title: 'No se pudo actualizar el pago' })
                    return
                }

                Toast.fire({ icon: 'success', title: 'Pedido marcado como pagado' })
                loadOrders()
                document.dispatchEvent(new Event('deliveries:request-refresh'))
            } catch (error) {
                console.error('Error actualizando el pago del pedido', error)
                Toast.fire({ icon: 'error', title: 'Ocurrió un error al actualizar el pago' })
            } finally {
                button.disabled = false
                button.innerHTML = originalHtml
            }
        }

        markPaid()
    })
})
