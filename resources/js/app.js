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
