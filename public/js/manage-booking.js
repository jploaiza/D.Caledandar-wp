document.addEventListener('DOMContentLoaded', function () {
    // Modal Logic
    const cancelModal = document.getElementById('rt-cancel-modal');
    const rescheduleModal = document.getElementById('rt-reschedule-modal');

    const btnCancel = document.getElementById('btn-cancel-modal');
    const btnReschedule = document.getElementById('btn-reschedule-modal');

    const btnCloseCancel = document.querySelector('.rt-close');
    const btnCloseReschedule = document.querySelector('.rt-close-reschedule');
    const btnKeep = document.getElementById('btn-close-cancel');

    if (btnCancel) {
        btnCancel.onclick = function () { cancelModal.classList.add('active'); };
    }

    if (btnReschedule) {
        btnReschedule.onclick = function () { rescheduleModal.classList.add('active'); };
    }

    // Close Modals
    function closeModal() {
        if (cancelModal) cancelModal.classList.remove('active');
        if (rescheduleModal) rescheduleModal.classList.remove('active');
    }

    if (btnCloseCancel) btnCloseCancel.onclick = closeModal;
    if (btnCloseReschedule) btnCloseReschedule.onclick = closeModal;
    if (btnKeep) btnKeep.onclick = closeModal;

    window.onclick = function (event) {
        if (event.target == cancelModal || event.target == rescheduleModal) {
            closeModal();
        }
    }

    // API Handling
    const btnConfirmCancel = document.getElementById('btn-confirm-cancel');
    const btnConfirmReschedule = document.getElementById('btn-confirm-reschedule');

    if (btnConfirmCancel) {
        btnConfirmCancel.onclick = function () {
            const code = this.getAttribute('data-code');
            const reason = document.getElementById('cancel-reason').value;

            btnConfirmCancel.disabled = true;
            btnConfirmCancel.innerText = 'Procesando...';

            fetch(rt_booking_config.api_url + code + '/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': rt_booking_config.nonce
                },
                body: JSON.stringify({ motivo: reason })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reserva cancelada exitosamente.');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo cancelar.'));
                        btnConfirmCancel.disabled = false;
                        btnConfirmCancel.innerText = 'Sí, cancelar';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión.');
                    btnConfirmCancel.disabled = false;
                    btnConfirmCancel.innerText = 'Sí, cancelar';
                });
        };
    }

    // Simple Reschedule Logic (can be expanded)
    if (btnConfirmReschedule) {
        btnConfirmReschedule.onclick = function () {
            const code = this.getAttribute('data-code');
            const newDate = document.getElementById('id_new_date').value;

            if (!newDate) {
                alert('Por favor selecciona una fecha.');
                return;
            }

            btnConfirmReschedule.disabled = true;
            btnConfirmReschedule.innerText = 'Procesando...';

            fetch(rt_booking_config.api_url + code + '/reschedule', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': rt_booking_config.nonce
                },
                body: JSON.stringify({
                    nueva_fecha_hora: newDate,
                    nueva_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reserva reagendada exitosamente.');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo reagendar.'));
                        btnConfirmReschedule.disabled = false;
                        btnConfirmReschedule.innerText = 'Confirmar Reagendamiento';
                    }
                });
        };
    }
});
