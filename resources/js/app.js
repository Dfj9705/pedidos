import Swal from 'sweetalert2';
import './bootstrap';

export const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
})

// Para que tus módulos puedan usar meta csrf sin llorar
document.head.insertAdjacentHTML(
    'beforeend',
    '<meta name="csrf-token" content="' + document.querySelector('meta[name="csrf-token"]')?.content + '">'
)

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/service-worker.js')
            .catch((error) => console.error('No se pudo registrar el service worker', error))
    })
}

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body

    const setSidebarState = (isOpen) => {
        body.classList[isOpen ? 'add' : 'remove']('sidebar-open')
    }

    document.querySelectorAll('[data-sidebar-toggle]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            body.classList.toggle('sidebar-open')
        })
    })

    document.querySelectorAll('[data-sidebar-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => setSidebarState(false))
    })

    document.querySelectorAll('.app-sidebar a.nav-link, .app-sidebar-logout').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                setSidebarState(false)
            }
        })
    })

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            setSidebarState(false)
        }
    })
})
