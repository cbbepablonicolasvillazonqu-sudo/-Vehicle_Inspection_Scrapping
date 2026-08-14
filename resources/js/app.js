import './bootstrap';

// Alpine.js NO se importa aquí: Livewire 3 ya incluye y arranca Alpine
// automáticamente (@livewireScripts). Importarlo dos veces rompe los
// componentes interactivos.

/**
 * Aviso cuando la red falla.
 *
 * Livewire, si el fetch se rechaza porque no hay conexión, reactiva el botón y
 * termina sin mostrar nada: queda igual que un guardado exitoso. Acá se engancha
 * ese fallo al mismo toast que ya usa el resto de la app, en versión roja.
 */
function avisarError(mensaje) {
    window.dispatchEvent(new CustomEvent('notificar', {
        detail: { mensaje, tipo: 'error' },
    }));
}

document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status }) => {
            // 503 es el código que Livewire usa cuando el fetch ni siquiera salió.
            // Un 500 real conserva su comportamiento (modal de error en desarrollo).
            if (status === 503) {
                avisarError(window.avisos?.sinConexion ?? 'Sin conexión');
            }
        });
    });
});

// Cubre todos los formularios con archivos, no solo el del vehículo.
window.addEventListener('livewire-upload-error', () => {
    avisarError(window.avisos?.subidaFallida ?? 'No se pudo subir el archivo');
});
