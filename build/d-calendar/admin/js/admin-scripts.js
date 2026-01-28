jQuery(document).ready(function ($) {
    'use strict';

    /**
     * General Helper Functions
     */
    function showLoader(element) {
        $(element).find('.spinner').addClass('is-active');
    }

    function hideLoader(element) {
        $(element).find('.spinner').removeClass('is-active');
    }

    /**
     * Dashboard Logic
     */
    if ($('#rt-stats-today').length) {
        // Mock loading stats
        setTimeout(function () {
            $('#rt-stats-today').text('12');
            $('#rt-stats-week').text('45');
            $('#rt-stats-month').text('180');
            $('#rt-stats-cancellation').text('4%');

            // Populate bookings tables
            $('#rt-upcoming-bookings tbody').html('<tr><td>2024-05-20 10:00</td><td>Juan Pérez</td><td>Terapia Individual</td><td><span class="status-badge status-connected">Confirmada</span></td></tr>');
            $('#rt-recent-bookings tbody').html('<tr><td>Hace 2 horas</td><td>Ana García</td><td>Consulta Online</td><td><span class="status-badge status-disconnected">Pendiente</span></td></tr>');
        }, 800);
    }

    /**
     * Services Logic
     */
    // Open Modal
    $('#rt-btn-add-service').on('click', function (e) {
        e.preventDefault();
        $('#rt-service-form')[0].reset();
        $('#rt-modal-title').text('Añadir Nuevo Servicio');
        $('#service_id').val('');
        $('#rt-service-modal').fadeIn();
    });

    // Close Modal
    $('.rt-close-modal, .rt-close-modal-btn').on('click', function () {
        $('.rt-modal').fadeOut();
    });

    // Save Service
    $('#rt-service-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        showLoader(form.parent());

        var data = {
            action: 'rt_save_service',
            nonce: rt_admin_ajax.nonce,
            form_data: form.serialize()
        };

        // Simulate AJAX
        setTimeout(function () {
            hideLoader(form.parent());
            $('.rt-modal').fadeOut();
            alert('Servicio guardado correctamente (Simulación AJAX)');
            loadServices();
        }, 1000);

        /* 
        // Real AJAX implementation would be:
        $.post(rt_admin_ajax.ajax_url, data, function(response) {
            hideLoader(form.parent());
            if (response.success) {
                $('.rt-modal').fadeOut();
                loadServices();
            } else {
                alert(response.data.message);
            }
        });
        */
    });

    function loadServices() {
        // Simulate loading list
        $('#rt-services-table-body').html('<tr><td colspan="5">Cargando...</td></tr>');
        setTimeout(function () {
            var html = '<tr>' +
                '<td><strong>Terapia Individual</strong></td>' +
                '<td>60 min</td>' +
                '<td>50.00€</td>' +
                '<td>Activo</td>' +
                '<td><button class="button rt-edit-service">Editar</button> <button class="button rt-delete-service">Borrar</button></td>' +
                '</tr>';
            $('#rt-services-table-body').html(html);
        }, 600);
    }

    // Initial load for services page
    if ($('#rt-services-list-container').length) {
        loadServices();
    }

    /**
     * Schedules Logic
     */
    $('.rt-day-toggle').on('change', function () {
        var row = $(this).closest('tr');
        var inputs = row.find('input[type="time"], select');
        if ($(this).is(':checked')) {
            row.removeClass('disabled');
            inputs.prop('disabled', false);
        } else {
            row.addClass('disabled');
            inputs.prop('disabled', true);
        }
    });

    // Init state for schedules
    $('.rt-day-toggle').each(function () {
        if (!$(this).is(':checked')) {
            $(this).closest('tr').addClass('disabled');
        }
    });

    $('#rt-schedule-form').on('submit', function (e) {
        e.preventDefault();
        alert('Horarios guardados (Simulación)');
    });

    /**
     * Bookings Logic
     */
    $('#rt-btn-add-booking').on('click', function (e) {
        e.preventDefault();
        $('#rt-booking-form')[0].reset();
        $('#rt-booking-modal-title').text('Nueva Reserva');
        $('#booking_id').val('');
        $('#rt-booking-modal').fadeIn();
    });

    // Initial load for bookings page
    if ($('#rt-bookings-table-body').length) {
        setTimeout(function () {
            var html = '<tr><td><input type="checkbox"></td><td>#1023</td><td>Carlos Ruiz</td><td>carlos@email.com</td><td>Terapia Pareja</td><td>2024-05-21 15:00</td><td>Confirmada</td><td><button class="button">Ver</button></td></tr>';
            $('#rt-bookings-table-body').html(html);
        }, 600);
    }

    /**
     * Alerts Logic
     */
    $('.rt-btn-expand-alert').on('click', function () {
        var body = $(this).closest('.rt-alert-item').find('.rt-alert-body');
        body.slideToggle();
        $(this).text(body.is(':visible') ? 'Ocultar Configuración' : 'Editar Configuración');
    });

    $('.rt-preview-alert').on('click', function () {
        alert('Generando vista previa del correo/mensaje...');
    });

    /**
     * Integrations
     */
    $('#rt-save-zoom, #rt-save-twilio').on('click', function () {
        alert('Credenciales guardadas correctamente (Simulación)');
    });

});
