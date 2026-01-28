/**
 * Gestión de Reservas - Frontend
 */
(function () {
    'use strict';

    // Obtener token de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const codigo = urlParams.get('codigo');
    const token = urlParams.get('token');

    // Determinar acción según la página actual
    const currentPath = window.location.pathname;
    let action = '';

    if (currentPath.includes('cancelar-reserva')) {
        action = 'cancel';
    } else if (currentPath.includes('reagendar-reserva')) {
        action = 'reschedule';
    }

    // Validar que tenemos código y token
    if (!codigo || !token) {
        showError('Link incompleto o inválido. Verifica que hayas copiado el link completo del email.');
        // Deshabilitar botones si existen
        const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
        const confirmRescheduleBtn = document.getElementById('confirm-reschedule-btn');
        if (confirmCancelBtn) confirmCancelBtn.disabled = true;
        if (confirmRescheduleBtn) confirmRescheduleBtn.disabled = true;
        return;
    }

    /**
     * Cancela una reserva
     */
    function cancelBooking(motivo = '') {
        const confirmBtn = document.getElementById('confirm-cancel-btn');
        const spinner = document.getElementById('loading-spinner');

        // Mostrar loading
        if (confirmBtn) confirmBtn.disabled = true;
        if (spinner) spinner.style.display = 'block';

        fetch(reservasTerapia.apiUrl + '/bookings/' + codigo + '/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': reservasTerapia.nonce,
                'X-Action-Token': token
            },
            body: JSON.stringify({
                motivo: motivo
            })
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Error al cancelar');
                    });
                }
                return response.json();
            })
            .then(data => {
                // Éxito
                showSuccess('Reserva cancelada exitosamente');

                // Redirigir a página de confirmación
                setTimeout(() => {
                    window.location.href = '/reserva-cancelada/?codigo=' + codigo;
                }, 2000);
            })
            .catch(error => {
                handleApiError(error);
            })
            .finally(() => {
                if (confirmBtn) confirmBtn.disabled = false;
                if (spinner) spinner.style.display = 'none';
            });
    }

    /**
     * Reagenda una reserva
     */
    function rescheduleBooking(nuevaFecha, nuevaHora, timezone) {
        const confirmBtn = document.getElementById('confirm-reschedule-btn');
        const spinner = document.getElementById('loading-spinner');

        if (confirmBtn) confirmBtn.disabled = true;
        if (spinner) spinner.style.display = 'block';

        fetch(reservasTerapia.apiUrl + '/bookings/' + codigo + '/reschedule', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': reservasTerapia.nonce,
                'X-Action-Token': token
            },
            body: JSON.stringify({
                fecha: nuevaFecha,
                hora: nuevaHora,
                timezone: timezone
            })
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Error al reagendar');
                    });
                }
                return response.json();
            })
            .then(data => {
                showSuccess('Reserva reagendada exitosamente');

                // Mostrar comparación de fechas si existe la función, o recargar
                if (typeof displayRescheduleConfirmation === 'function') {
                    displayRescheduleConfirmation(data);
                } else {
                    // Fallback si no existe la función UI específica
                    window.location.reload();
                }
            })
            .catch(error => {
                handleApiError(error);
            })
            .finally(() => {
                if (confirmBtn) confirmBtn.disabled = false;
                if (spinner) spinner.style.display = 'none';
            });
    }

    /**
     * Maneja errores de API con mensajes específicos
     */
    function handleApiError(error) {
        const message = error.message || 'Error desconocido';
        console.error('API Error:', error);

        // Errores específicos de tokens
        if (message.includes('Token inválido') || message.includes('expirado') || message.includes('Invalid token')) {
            showError(
                'El link ha expirado o ya fue utilizado. ' +
                'Los links de gestión solo pueden usarse una vez. ' +
                'Si necesitas hacer cambios, contacta con nosotros.'
            );
        } else if (message.includes('Rate limit')) {
            showError(
                'Has realizado demasiados intentos. ' +
                'Por favor espera un minuto antes de reintentar.'
            );
        } else if (message.includes('Token de autorización requerido') || message.includes('Missing token')) {
            showError(
                'Link de seguridad no válido. ' +
                'Asegúrate de usar el link completo que recibiste por email.'
            );
        } else {
            showError('Error: ' + message);
        }
    }

    /**
     * Muestra mensaje de error
     */
    function showError(message) {
        const errorDiv = document.getElementById('error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.style.padding = '15px';
            errorDiv.style.background = '#f8d7da';
            errorDiv.style.border = '1px solid #f5c6cb';
            errorDiv.style.borderRadius = '4px';
            errorDiv.style.color = '#721c24';
            errorDiv.style.marginBottom = '20px';
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            alert(message);
        }
    }

    /**
     * Muestra mensaje de éxito
     */
    function showSuccess(message) {
        const successDiv = document.getElementById('success-message');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            successDiv.style.padding = '15px';
            successDiv.style.background = '#d4edda';
            successDiv.style.border = '1px solid #c3e6cb';
            successDiv.style.borderRadius = '4px';
            successDiv.style.color = '#155724';
            successDiv.style.marginBottom = '20px';
            // Scroll to success
            successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Exponer funciones globalmente
    window.reservasManagement = {
        cancelBooking,
        rescheduleBooking
    };

})();
