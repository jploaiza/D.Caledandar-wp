<?php
/**
 * Provide a public-facing view for the booking management page
 *
 * @package ReservasTerapia
 * @subpackage ReservasTerapia/public/partials
 */

// Basic security check if loaded directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// $code should be available from Booking_Management::handle_template_redirect logic
// But we need to fetch the booking details here.
// Assuming we can use a helper function or direct DB query.
// For now, let's mock it or fetch it if possible.
// Better practice: Booking_Management passes data, but 'include' shares scope.
// So let's assume $booking_data is passed or we fetch it.

global $wpdb;
$table_reservas = $wpdb->prefix . 'rt_reservas';
$table_servicios = $wpdb->prefix . 'rt_servicios';

// Securely fetch booking
if (empty($code)) {
    $code = get_query_var('codigo');
}

$booking = $wpdb->get_row($wpdb->prepare(
    "SELECT r.*, s.nombre as service_name, s.duracion_minutos 
     FROM $table_reservas r 
     JOIN $table_servicios s ON r.servicio_id = s.id 
     WHERE r.codigo_unico = %s",
    $code
));

// Handle invalid booking
if (!$booking) {
    echo '<div class="rt-container"><div class="rt-error-message">Reserva no encontrada o código inválido.</div></div>';
    get_footer();
    exit;
}

// Helpers
$start_date = new DateTime($booking->fecha_hora_inicio);
$end_date = new DateTime($booking->fecha_hora_fin);
$status_label = ucfirst($booking->estado);
$status_class = 'rt-status-' . $booking->estado;
$is_upcoming = $start_date > new DateTime();
$can_modify = ($booking->estado === 'confirmada' && $is_upcoming);

?>

<div class="rt-manage-container">
    <div class="rt-card">
        <div class="rt-header">
            <span class="rt-badge <?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($status_label); ?>
            </span>
            <h1 class="rt-booking-code">
                <?php echo esc_html($code); ?>
            </h1>
            <p class="rt-subtitle">Código de Reserva</p>
        </div>

        <div class="rt-details">
            <div class="rt-detail-item">
                <span class="rt-label">Servicio</span>
                <span class="rt-value">
                    <?php echo esc_html($booking->service_name); ?>
                </span>
            </div>
            <div class="rt-detail-item">
                <span class="rt-label">Fecha</span>
                <span class="rt-value">
                    <?php echo esc_html($start_date->format('d/m/Y')); ?>
                </span>
            </div>
            <div class="rt-detail-item">
                <span class="rt-label">Hora</span>
                <span class="rt-value">
                    <?php echo esc_html($start_date->format('H:i')); ?> -
                    <?php echo esc_html($end_date->format('H:i')); ?>
                </span>
            </div>
            <div class="rt-detail-item">
                <span class="rt-label">Cliente</span>
                <span class="rt-value">
                    <?php echo esc_html($booking->cliente_nombre); ?>
                </span>
            </div>

            <?php if (!empty($booking->zoom_join_url) && $booking->estado === 'confirmada'): ?>
                <div class="rt-zoom-section">
                    <a href="<?php echo esc_url($booking->zoom_join_url); ?>" target="_blank" class="rt-btn rt-btn-zoom">
                        Unirse a Zoom
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($can_modify): ?>
            <div class="rt-actions">
                <button type="button" class="rt-btn rt-btn-outline" id="btn-cancel-modal">Cancelar Reserva</button>
                <button type="button" class="rt-btn rt-btn-primary" id="btn-reschedule-modal">Reagendar</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Modal -->
<div id="rt-cancel-modal" class="rt-modal">
    <div class="rt-modal-content">
        <span class="rt-close">&times;</span>
        <h2>Cancelar Reserva</h2>
        <p>¿Estás seguro de que deseas cancelar esta reserva? Esta acción no se puede deshacer.</p>
        <textarea id="cancel-reason" placeholder="Motivo de la cancelación (opcional)" rows="3"></textarea>
        <div class="rt-modal-actions">
            <button class="rt-btn rt-btn-text" id="btn-close-cancel">No, mantener</button>
            <button class="rt-btn rt-btn-danger" id="btn-confirm-cancel" data-code="<?php echo esc_attr($code); ?>">Sí,
                cancelar</button>
        </div>
    </div>
</div>

<!-- Reschedule Modal placeholder (can be expanded later with full calendar) -->
<div id="rt-reschedule-modal" class="rt-modal">
    <div class="rt-modal-content">
        <span class="rt-close-reschedule">&times;</span>
        <h2>Reagendar Reserva</h2>
        <p>Selecciona una nueva fecha (Funcionalidad simplificada por ahora).</p>
        <input type="datetime-local" id="id_new_date" min="<?php echo date('Y-m-d\TH:i'); ?>">
        <div class="rt-modal-actions">
            <button class="rt-btn rt-btn-primary" id="btn-confirm-reschedule"
                data-code="<?php echo esc_attr($code); ?>">Confirmar Reagendamiento</button>
        </div>
    </div>
</div>

<script>
    // Simple pass of variables to JS
    var rt_booking_config = {
        api_url: '<?php echo esc_url(rest_url('rt/v1/bookings/')); ?>',
        nonce: '<?php echo wp_create_nonce('wp_rest'); ?>'
    };
</script>

<?php
get_footer();
