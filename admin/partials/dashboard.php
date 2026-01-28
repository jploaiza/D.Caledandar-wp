<?php
/**
 * Dashboard page partial
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Dashboard - D.Calendar</h1>

    <?php wp_nonce_field('rt_admin_nonce', 'rt_dashboard_nonce'); ?>

    <div class="rt-dashboard-widgets">
        <!-- Stats Cards -->
        <div class="rt-card rt-stats-card">
            <h3>
                <?php esc_html_e('Reservas Hoy', 'reservas-terapia'); ?>
            </h3>
            <div class="rt-stat-number" id="rt-stats-today">
                <span class="rt-loading"></span>
            </div>
        </div>
        <div class="rt-card rt-stats-card">
            <h3>
                <?php esc_html_e('Reservas Semana', 'reservas-terapia'); ?>
            </h3>
            <div class="rt-stat-number" id="rt-stats-week">
                <span class="rt-loading"></span>
            </div>
        </div>
        <div class="rt-card rt-stats-card">
            <h3>
                <?php esc_html_e('Reservas Mes', 'reservas-terapia'); ?>
            </h3>
            <div class="rt-stat-number" id="rt-stats-month">
                <span class="rt-loading"></span>
            </div>
        </div>
        <div class="rt-card rt-stats-card">
            <h3>
                <?php esc_html_e('Tasa Cancelación', 'reservas-terapia'); ?>
            </h3>
            <div class="rt-stat-number" id="rt-stats-cancellation">
                <span class="rt-loading"></span>
            </div>
        </div>
    </div>

    <div class="rt-dashboard-charts">
        <div class="rt-card">
            <h3>
                <?php esc_html_e('Actividad (Últimos 30 días)', 'reservas-terapia'); ?>
            </h3>
            <div style="height: 300px;">
                <canvas id="rt-bookings-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="rt-dashboard-lists">
        <div class="rt-card">
            <h3>
                <?php esc_html_e('Próximas Reservas', 'reservas-terapia'); ?>
            </h3>
            <table class="wp-list-table widefat fixed striped" id="rt-upcoming-bookings">
                <thead>
                    <tr>
                        <th>
                            <?php esc_html_e('Fecha/Hora', 'reservas-terapia'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Cliente', 'reservas-terapia'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Servicio', 'reservas-terapia'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Estado', 'reservas-terapia'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="rt-loading">
                            <?php esc_html_e('Cargando...', 'reservas-terapia'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="rt-card">
            <h3>
                <?php esc_html_e('Últimas Reservas Creadas', 'reservas-terapia'); ?>
            </h3>
            <table class="wp-list-table widefat fixed striped" id="rt-recent-bookings">
                <thead>
                    <tr>
                        <th>
                            <?php esc_html_e('Creado', 'reservas-terapia'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Cliente', 'reservas-terapia'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Servicio', 'reservas-terapia'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Estado', 'reservas-terapia'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="rt-loading">
                            <?php esc_html_e('Cargando...', 'reservas-terapia'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rt-quick-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=rt-bookings')); ?>" class="button button-primary">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e('Ver Todas las Reservas', 'reservas-terapia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=rt-services')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e('Gestionar Servicios', 'reservas-terapia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=rt-schedules')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Configurar Horarios', 'reservas-terapia'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=rt-integrations')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-admin-plugins"></span>
            <?php esc_html_e('Integraciones', 'reservas-terapia'); ?>
        </a>
    </div>
</div>