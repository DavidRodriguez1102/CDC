// js/main.js

// Confirmación de eliminación
document.addEventListener('DOMContentLoaded', function() {
    // Mensajes automáticos desaparecen después de 5 segundos
    const mensajes = document.querySelectorAll('.mensaje');
    mensajes.forEach(function(mensaje) {
        setTimeout(function() {
            mensaje.style.display = 'none';
        }, 5000);
    });
    
    // Búsqueda en tiempo real (opcional)
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Podrías implementar búsqueda AJAX aquí
        });
    }
    
    // Validación de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            inputs.forEach(function(input) {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#e53e3e';
                } else {
                    input.style.borderColor = '#e2e8f0';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos obligatorios.');
            }
        });
    });
});

// Función para confirmar eliminación
function confirmarEliminacion(id, tipo) {
    return confirm(`¿Estás seguro de que deseas eliminar este ${tipo}? Esta acción no se puede deshacer.`);
}