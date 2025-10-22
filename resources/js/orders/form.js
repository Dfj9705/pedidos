const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-order-form]')
    if (!form) return

    const defaultAction = form.getAttribute('action') || '/orders'

    const handleSubmit = async (event) => {
        event.preventDefault()

        const formData = new FormData(form)
        const targetAction = form.dataset.action || defaultAction
        const methodAttr = (form.dataset.method || form.getAttribute('method') || 'POST').toUpperCase()
        if (methodAttr === 'PUT') {
            formData.append('_method', 'PUT')
        }

        try {
            const response = await fetch(targetAction, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
                credentials: 'include',
            })

            let payload = {}
            try {
                payload = await response.json()
            } catch (error) {
                payload = {}
            }

            console.log('Respuesta del formulario de pedidos', response.status, payload)
        } catch (error) {
            console.error('No se pudo enviar el formulario de pedidos', error)
        }
    }

    form.addEventListener('submit', handleSubmit)
})
