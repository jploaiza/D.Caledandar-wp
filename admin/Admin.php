<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    DCalendar
 * @subpackage DCalendar/admin
 */

namespace ReservasTerapia\Admin;

use ReservasTerapia\Database;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 * Handles all admin pages and AJAX operations.
 *
 * @package    DCalendar
 * @subpackage DCalendar/admin
 * @author     JPL <email@example.com>
 */
class Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string $plugin_name       The name of this plugin.
     * @param    string $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register AJAX handlers
        $this->register_ajax_handlers();
    }

    /**
     * Register all AJAX action handlers.
     *
     * @since    1.0.0
     */
    private function register_ajax_handlers()
    {
        // Dashboard
        add_action('wp_ajax_rt_get_dashboard_stats', [$this, 'ajax_get_dashboard_stats']);
        add_action('wp_ajax_rt_get_upcoming_bookings', [$this, 'ajax_get_upcoming_bookings']);
        add_action('wp_ajax_rt_get_recent_bookings', [$this, 'ajax_get_recent_bookings']);

        // Services CRUD
        add_action('wp_ajax_rt_get_services', [$this, 'ajax_get_services']);
        add_action('wp_ajax_rt_save_service', [$this, 'ajax_save_service']);
        add_action('wp_ajax_rt_get_service', [$this, 'ajax_get_service']);
        add_action('wp_ajax_rt_delete_service', [$this, 'ajax_delete_service']);

        // Schedules
        add_action('wp_ajax_rt_get_schedules', [$this, 'ajax_get_schedules']);
        add_action('wp_ajax_rt_save_schedules', [$this, 'ajax_save_schedules']);
        add_action('wp_ajax_rt_preview_slots', [$this, 'ajax_preview_slots']);

        // Bookings CRUD
        add_action('wp_ajax_rt_get_bookings', [$this, 'ajax_get_bookings']);
        add_action('wp_ajax_rt_get_booking', [$this, 'ajax_get_booking']);
        add_action('wp_ajax_rt_save_booking', [$this, 'ajax_save_booking']);
        add_action('wp_ajax_rt_delete_booking', [$this, 'ajax_delete_booking']);
        add_action('wp_ajax_rt_update_booking_status', [$this, 'ajax_update_booking_status']);

        // Alerts
        add_action('wp_ajax_rt_get_alerts_config', [$this, 'ajax_get_alerts_config']);
        add_action('wp_ajax_rt_save_alerts_config', [$this, 'ajax_save_alerts_config']);
        add_action('wp_ajax_rt_preview_alert', [$this, 'ajax_preview_alert']);

        // Integrations
        add_action('wp_ajax_rt_test_twilio', [$this, 'ajax_test_twilio']);
        add_action('wp_ajax_rt_test_zoom', [$this, 'ajax_test_zoom']);
        add_action('wp_ajax_rt_save_integration', [$this, 'ajax_save_integration']);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        $screen = get_current_screen();

        // Only load on our plugin pages
        if (
            strpos($screen->id, $this->plugin_name) === false &&
            strpos($screen->id, 'rt-') === false
        ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            RT_URL . 'assets/css/admin.css',
            [],
            $this->version,
            'all'
        );

        // WordPress color picker
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        $screen = get_current_screen();

        // Only load on our plugin pages
        if (
            strpos($screen->id, $this->plugin_name) === false &&
            strpos($screen->id, 'rt-') === false
        ) {
            return;
        }

        // Chart.js for dashboard
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // WordPress color picker
        wp_enqueue_script('wp-color-picker');

        // Main admin script
        wp_enqueue_script(
            $this->plugin_name,
            RT_URL . 'assets/js/admin.js',
            ['jquery', 'wp-color-picker', 'chartjs'],
            $this->version,
            true
        );

        // Localize script with AJAX data
        wp_localize_script($this->plugin_name, 'rtAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('reservas-terapia/v1/'),
            'nonce' => wp_create_nonce('rt_admin_nonce'),
            'strings' => [
                'confirmDelete' => __('¿Estás seguro de que deseas eliminar este elemento?', 'reservas-terapia'),
                'saving' => __('Guardando...', 'reservas-terapia'),
                'saved' => __('Guardado exitosamente', 'reservas-terapia'),
                'error' => __('Ocurrió un error', 'reservas-terapia'),
                'loading' => __('Cargando...', 'reservas-terapia'),
                'noResults' => __('No se encontraron resultados', 'reservas-terapia'),
            ],
        ]);
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu()
    {
        // Main menu page - Dashboard
        add_menu_page(
            'D.Calendar',
            'D.Calendar',
            'manage_options',
            $this->plugin_name,
            [$this, 'display_dashboard_page'],
            'dashicons-calendar-alt',
            30
        );

        // Dashboard (replaces default)
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'reservas-terapia'),
            __('Dashboard', 'reservas-terapia'),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_dashboard_page']
        );

        // Services
        add_submenu_page(
            $this->plugin_name,
            __('Servicios', 'reservas-terapia'),
            __('Servicios', 'reservas-terapia'),
            'manage_options',
            'rt-services',
            [$this, 'display_services_page']
        );

        // Schedules
        add_submenu_page(
            $this->plugin_name,
            __('Horarios', 'reservas-terapia'),
            __('Horarios', 'reservas-terapia'),
            'manage_options',
            'rt-schedules',
            [$this, 'display_schedules_page']
        );

        // Bookings
        add_submenu_page(
            $this->plugin_name,
            __('Reservas', 'reservas-terapia'),
            __('Reservas', 'reservas-terapia'),
            'manage_options',
            'rt-bookings',
            [$this, 'display_bookings_page']
        );

        // Alerts
        add_submenu_page(
            $this->plugin_name,
            __('Alertas', 'reservas-terapia'),
            __('Alertas', 'reservas-terapia'),
            'manage_options',
            'rt-alerts',
            [$this, 'display_alerts_page']
        );

        // Integrations
        add_submenu_page(
            $this->plugin_name,
            __('Integraciones', 'reservas-terapia'),
            __('Integraciones', 'reservas-terapia'),
            'manage_options',
            'rt-integrations',
            [$this, 'display_integrations_page']
        );

        // Settings
        add_submenu_page(
            $this->plugin_name,
            __('Configuración', 'reservas-terapia'),
            __('Configuración', 'reservas-terapia'),
            'manage_options',
            'rt-settings',
            [$this, 'display_plugin_setup_page']
        );

        // WhatsApp Logs
        add_submenu_page(
            $this->plugin_name,
            __('WhatsApp Logs', 'reservas-terapia'),
            __('WhatsApp Logs', 'reservas-terapia'),
            'manage_options',
            'rt-whatsapp-logs',
            [$this, 'display_twilio_logs_page']
        );
    }

    // =========================================================================
    // DISPLAY METHODS
    // =========================================================================

    /**
     * Display the Dashboard page.
     */
    public function display_dashboard_page()
    {
        include_once 'partials/dashboard.php';
    }

    /**
     * Display the Services page.
     */
    public function display_services_page()
    {
        include_once 'partials/services.php';
    }

    /**
     * Display the Schedules page.
     */
    public function display_schedules_page()
    {
        include_once 'partials/schedules.php';
    }

    /**
     * Display the Bookings page.
     */
    public function display_bookings_page()
    {
        include_once 'partials/bookings.php';
    }

    /**
     * Display the Alerts page.
     */
    public function display_alerts_page()
    {
        include_once 'partials/alerts.php';
    }

    /**
     * Display the Integrations page.
     */
    public function display_integrations_page()
    {
        $plugin_name = $this->plugin_name;
        include_once 'partials/integrations.php';
    }

    /**
     * Display the Twilio Logs page.
     */
    public function display_twilio_logs_page()
    {
        include_once 'partials/twilio-logs-display.php';
    }

    /**
     * Prepare configuration page by setting the variables.
     *
     * @since    1.0.0
     */
    public function display_plugin_setup_page()
    {
        include_once 'partials/reservas-terapia-admin-display.php';
    }

    /**
     * Register the settings for this plugin into the WordPress Dashboard.
     *
     * @since    1.0.0
     */
    public function register_settings()
    {
        // Google Calendar Settings
        register_setting($this->plugin_name, 'rt_google_client_id');
        register_setting($this->plugin_name, 'rt_google_client_secret');
        register_setting($this->plugin_name, 'rt_google_redirect_uri');
        register_setting($this->plugin_name, 'rt_google_calendar_id');

        // Zoom Settings
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_client_id');
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_client_secret');
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_webhook_secret');
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_record_meetings');

        // Twilio Settings
        register_setting($this->plugin_name, 'rt_twilio_account_sid');
        register_setting($this->plugin_name, 'rt_twilio_auth_token');
        register_setting($this->plugin_name, 'rt_twilio_whatsapp_number');

        // Business Settings
        register_setting($this->plugin_name, 'rt_business_name');
        register_setting($this->plugin_name, 'rt_business_email');
        register_setting($this->plugin_name, 'rt_business_phone');
        register_setting($this->plugin_name, 'rt_timezone');

        // Alerts Configuration
        register_setting($this->plugin_name, 'rt_alerts_config');
        register_setting($this->plugin_name, 'rt_schedules_config');
    }

    // =========================================================================
    // AJAX HANDLERS - Dashboard
    // =========================================================================

    /**
     * Get dashboard statistics.
     */
    public function ajax_get_dashboard_stats()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_reservas';

        $today = current_time('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');

        // Today's bookings
        $today_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE fecha = %s AND estado != 'cancelled'",
            $today
        ));

        // Week bookings
        $week_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE fecha BETWEEN %s AND %s AND estado != 'cancelled'",
            $week_start,
            $week_end
        ));

        // Month bookings
        $month_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE fecha BETWEEN %s AND %s AND estado != 'cancelled'",
            $month_start,
            $month_end
        ));

        // Cancellation rate (last 30 days)
        $total_30_days = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE fecha >= %s",
            date('Y-m-d', strtotime('-30 days'))
        ));

        $cancelled_30_days = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE fecha >= %s AND estado = 'cancelled'",
            date('Y-m-d', strtotime('-30 days'))
        ));

        $cancellation_rate = $total_30_days > 0
            ? round(($cancelled_30_days / $total_30_days) * 100, 1)
            : 0;

        // Chart data - last 30 days
        $chart_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha) as date, COUNT(*) as count 
             FROM {$table} 
             WHERE fecha >= %s AND estado != 'cancelled'
             GROUP BY DATE(fecha) 
             ORDER BY fecha ASC",
            date('Y-m-d', strtotime('-30 days'))
        ));

        wp_send_json_success([
            'today' => (int) $today_count,
            'week' => (int) $week_count,
            'month' => (int) $month_count,
            'cancellation_rate' => $cancellation_rate,
            'chart_data' => $chart_data,
        ]);
    }

    /**
     * Get upcoming bookings for dashboard.
     */
    public function ajax_get_upcoming_bookings()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'rt_reservas';
        $services_table = $wpdb->prefix . 'rt_servicios';

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, s.nombre as servicio_nombre 
             FROM {$bookings_table} r
             LEFT JOIN {$services_table} s ON r.servicio_id = s.id
             WHERE r.fecha >= %s AND r.estado IN ('confirmed', 'pending')
             ORDER BY r.fecha ASC, r.hora ASC
             LIMIT 10",
            current_time('Y-m-d')
        ));

        wp_send_json_success($bookings);
    }

    /**
     * Get recent bookings for dashboard.
     */
    public function ajax_get_recent_bookings()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'rt_reservas';
        $services_table = $wpdb->prefix . 'rt_servicios';

        $bookings = $wpdb->get_results(
            "SELECT r.*, s.nombre as servicio_nombre 
             FROM {$bookings_table} r
             LEFT JOIN {$services_table} s ON r.servicio_id = s.id
             ORDER BY r.created_at DESC
             LIMIT 10"
        );

        wp_send_json_success($bookings);
    }

    // =========================================================================
    // AJAX HANDLERS - Services
    // =========================================================================

    /**
     * Get all services.
     */
    public function ajax_get_services()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_servicios';
        $services = $wpdb->get_results("SELECT * FROM {$table} ORDER BY nombre ASC");

        wp_send_json_success($services);
    }

    /**
     * Get a single service.
     */
    public function ajax_get_service()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'ID inválido']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_servicios';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));

        if (!$service) {
            wp_send_json_error(['message' => 'Servicio no encontrado']);
        }

        wp_send_json_success($service);
    }

    /**
     * Save (create or update) a service.
     */
    public function ajax_save_service()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

        $data = [
            'nombre' => sanitize_text_field($_POST['service_name'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['service_description'] ?? ''),
            'duracion' => intval($_POST['service_duration'] ?? 60),
            'precio' => floatval($_POST['service_price'] ?? 0),
            'color' => sanitize_hex_color($_POST['service_color'] ?? '#3b82f6'),
            'buffer_antes' => intval($_POST['service_buffer_before'] ?? 0),
            'buffer_despues' => intval($_POST['service_buffer_after'] ?? 0),
            'activo' => intval($_POST['service_active'] ?? 1),
        ];

        // Validate required fields
        if (empty($data['nombre'])) {
            wp_send_json_error(['message' => 'El nombre es requerido']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_servicios';

        if ($id > 0) {
            // Update
            $result = $wpdb->update($table, $data, ['id' => $id]);
            $message = 'Servicio actualizado';
        } else {
            // Insert
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
            $message = 'Servicio creado';
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al guardar: ' . $wpdb->last_error]);
        }

        wp_send_json_success([
            'message' => $message,
            'id' => $id,
        ]);
    }

    /**
     * Delete a service.
     */
    public function ajax_delete_service()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'ID inválido']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_servicios';
        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al eliminar']);
        }

        wp_send_json_success(['message' => 'Servicio eliminado']);
    }

    // =========================================================================
    // AJAX HANDLERS - Schedules
    // =========================================================================

    /**
     * Get schedule configuration.
     */
    public function ajax_get_schedules()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $schedules = get_option('rt_schedules_config', []);

        // Default schedule structure
        $default = [
            'mon' => ['active' => false, 'start' => '09:00', 'end' => '18:00', 'slot_duration' => 30],
            'tue' => ['active' => false, 'start' => '09:00', 'end' => '18:00', 'slot_duration' => 30],
            'wed' => ['active' => false, 'start' => '09:00', 'end' => '18:00', 'slot_duration' => 30],
            'thu' => ['active' => false, 'start' => '09:00', 'end' => '18:00', 'slot_duration' => 30],
            'fri' => ['active' => false, 'start' => '09:00', 'end' => '18:00', 'slot_duration' => 30],
            'sat' => ['active' => false, 'start' => '09:00', 'end' => '14:00', 'slot_duration' => 30],
            'sun' => ['active' => false, 'start' => '09:00', 'end' => '14:00', 'slot_duration' => 30],
        ];

        $schedules = wp_parse_args($schedules, $default);

        wp_send_json_success($schedules);
    }

    /**
     * Save schedule configuration.
     */
    public function ajax_save_schedules()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $schedule_data = isset($_POST['schedule']) ? $_POST['schedule'] : [];

        $clean_data = [];
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        foreach ($days as $day) {
            if (isset($schedule_data[$day])) {
                $clean_data[$day] = [
                    'active' => !empty($schedule_data[$day]['active']),
                    'start' => sanitize_text_field($schedule_data[$day]['start'] ?? '09:00'),
                    'end' => sanitize_text_field($schedule_data[$day]['end'] ?? '18:00'),
                    'slot_duration' => intval($schedule_data[$day]['slot_duration'] ?? 30),
                ];
            } else {
                $clean_data[$day] = [
                    'active' => false,
                    'start' => '09:00',
                    'end' => '18:00',
                    'slot_duration' => 30,
                ];
            }
        }

        update_option('rt_schedules_config', $clean_data);

        wp_send_json_success(['message' => 'Horarios guardados']);
    }

    /**
     * Preview slots for a given day configuration.
     */
    public function ajax_preview_slots()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $start = sanitize_text_field($_POST['start'] ?? '09:00');
        $end = sanitize_text_field($_POST['end'] ?? '18:00');
        $duration = intval($_POST['slot_duration'] ?? 30);

        $slots = [];
        $current = strtotime($start);
        $end_time = strtotime($end);

        while ($current < $end_time) {
            $slot_end = $current + ($duration * 60);
            if ($slot_end <= $end_time) {
                $slots[] = date('H:i', $current) . ' - ' . date('H:i', $slot_end);
            }
            $current += $duration * 60;
        }

        wp_send_json_success($slots);
    }

    // =========================================================================
    // AJAX HANDLERS - Bookings
    // =========================================================================

    /**
     * Get bookings with filters.
     */
    public function ajax_get_bookings()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'rt_reservas';
        $services_table = $wpdb->prefix . 'rt_servicios';

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $offset = ($page - 1) * $per_page;

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $where = ['1=1'];
        $prepare_args = [];

        if ($service_id > 0) {
            $where[] = 'r.servicio_id = %d';
            $prepare_args[] = $service_id;
        }

        if (!empty($status)) {
            $where[] = 'r.estado = %s';
            $prepare_args[] = $status;
        }

        if (!empty($date)) {
            $where[] = 'r.fecha = %s';
            $prepare_args[] = $date;
        }

        if (!empty($search)) {
            $where[] = '(r.cliente_nombre LIKE %s OR r.cliente_email LIKE %s OR r.codigo LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $prepare_args[] = $search_term;
            $prepare_args[] = $search_term;
            $prepare_args[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        // Count total
        $count_query = "SELECT COUNT(*) FROM {$bookings_table} r WHERE {$where_clause}";
        if (!empty($prepare_args)) {
            $count_query = $wpdb->prepare($count_query, $prepare_args);
        }
        $total = $wpdb->get_var($count_query);

        // Get bookings
        $query = "SELECT r.*, s.nombre as servicio_nombre 
                  FROM {$bookings_table} r
                  LEFT JOIN {$services_table} s ON r.servicio_id = s.id
                  WHERE {$where_clause}
                  ORDER BY r.fecha DESC, r.hora DESC
                  LIMIT %d OFFSET %d";

        $prepare_args[] = $per_page;
        $prepare_args[] = $offset;

        $bookings = $wpdb->get_results($wpdb->prepare($query, $prepare_args));

        wp_send_json_success([
            'bookings' => $bookings,
            'total' => (int) $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }

    /**
     * Get a single booking.
     */
    public function ajax_get_booking()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'ID inválido']);
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'rt_reservas';
        $services_table = $wpdb->prefix . 'rt_servicios';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, s.nombre as servicio_nombre 
             FROM {$bookings_table} r
             LEFT JOIN {$services_table} s ON r.servicio_id = s.id
             WHERE r.id = %d",
            $id
        ));

        if (!$booking) {
            wp_send_json_error(['message' => 'Reserva no encontrada']);
        }

        wp_send_json_success($booking);
    }

    /**
     * Save (create or update) a booking.
     */
    public function ajax_save_booking()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        $data = [
            'cliente_nombre' => sanitize_text_field($_POST['client_name'] ?? ''),
            'cliente_email' => sanitize_email($_POST['client_email'] ?? ''),
            'cliente_telefono' => sanitize_text_field($_POST['client_phone'] ?? ''),
            'servicio_id' => intval($_POST['service_id'] ?? 0),
            'fecha' => sanitize_text_field($_POST['booking_date'] ?? ''),
            'hora' => sanitize_text_field($_POST['booking_time'] ?? ''),
            'estado' => sanitize_text_field($_POST['booking_status'] ?? 'pending'),
            'notas' => sanitize_textarea_field($_POST['booking_notes'] ?? ''),
        ];

        // Validate required fields
        if (empty($data['cliente_nombre']) || empty($data['cliente_email'])) {
            wp_send_json_error(['message' => 'Nombre y email son requeridos']);
        }

        if (empty($data['fecha']) || empty($data['hora'])) {
            wp_send_json_error(['message' => 'Fecha y hora son requeridos']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_reservas';

        if ($id > 0) {
            // Update
            $result = $wpdb->update($table, $data, ['id' => $id]);
            $message = 'Reserva actualizada';
        } else {
            // Insert - generate code
            $db = new Database();
            $data['codigo'] = $db->generate_unique_code();
            $data['created_at'] = current_time('mysql');

            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
            $message = 'Reserva creada';
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al guardar: ' . $wpdb->last_error]);
        }

        wp_send_json_success([
            'message' => $message,
            'id' => $id,
        ]);
    }

    /**
     * Delete a booking.
     */
    public function ajax_delete_booking()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'ID inválido']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_reservas';
        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al eliminar']);
        }

        wp_send_json_success(['message' => 'Reserva eliminada']);
    }

    /**
     * Update booking status.
     */
    public function ajax_update_booking_status()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$id || !in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rt_reservas';
        $result = $wpdb->update($table, ['estado' => $status], ['id' => $id]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al actualizar']);
        }

        wp_send_json_success(['message' => 'Estado actualizado']);
    }

    // =========================================================================
    // AJAX HANDLERS - Alerts
    // =========================================================================

    /**
     * Get alerts configuration.
     */
    public function ajax_get_alerts_config()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $config = get_option('rt_alerts_config', []);

        // Default configuration
        $defaults = [
            'confirmation' => [
                'active' => true,
                'channels' => ['email'],
                'subject' => 'Confirmación de tu reserva',
                'message' => "Hola {nombre},\n\nTu reserva ha sido confirmada:\n\nServicio: {servicio}\nFecha: {fecha}\nHora: {hora}\n\n¡Te esperamos!",
            ],
            'reminder' => [
                'active' => false,
                'channels' => ['email', 'whatsapp'],
                'offset' => 24,
                'subject' => 'Recordatorio de tu cita',
                'message' => "Hola {nombre},\n\nTe recordamos tu cita mañana:\n\nServicio: {servicio}\nFecha: {fecha}\nHora: {hora}",
            ],
            'thanks' => [
                'active' => false,
                'channels' => ['email'],
                'offset' => 2,
                'subject' => '¡Gracias por tu visita!',
                'message' => "Hola {nombre},\n\n¡Gracias por visitarnos! Esperamos verte pronto.",
            ],
            'cancellation' => [
                'active' => true,
                'channels' => ['email'],
                'subject' => 'Reserva cancelada',
                'message' => "Hola {nombre},\n\nTu reserva ha sido cancelada:\n\nServicio: {servicio}\nFecha: {fecha}\nHora: {hora}",
            ],
            'rescheduled' => [
                'active' => true,
                'channels' => ['email'],
                'subject' => 'Reserva modificada',
                'message' => "Hola {nombre},\n\nTu reserva ha sido modificada:\n\nServicio: {servicio}\nNueva fecha: {fecha}\nNueva hora: {hora}",
            ],
        ];

        $config = wp_parse_args($config, $defaults);

        wp_send_json_success($config);
    }

    /**
     * Save alerts configuration.
     */
    public function ajax_save_alerts_config()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $alerts_data = isset($_POST['alerts']) ? $_POST['alerts'] : [];

        $clean_data = [];
        $alert_types = ['confirmation', 'reminder', 'thanks', 'cancellation', 'rescheduled'];

        foreach ($alert_types as $type) {
            if (isset($alerts_data[$type])) {
                $clean_data[$type] = [
                    'active' => !empty($alerts_data[$type]['active']),
                    'channels' => isset($alerts_data[$type]['channels'])
                        ? array_map('sanitize_text_field', (array) $alerts_data[$type]['channels'])
                        : [],
                    'subject' => sanitize_text_field($alerts_data[$type]['subject'] ?? ''),
                    'message' => sanitize_textarea_field($alerts_data[$type]['message'] ?? ''),
                ];

                // Add offset for reminder and thanks
                if (in_array($type, ['reminder', 'thanks'])) {
                    $clean_data[$type]['offset'] = intval($alerts_data[$type]['offset'] ?? 24);
                }
            }
        }

        update_option('rt_alerts_config', $clean_data);

        wp_send_json_success(['message' => 'Configuración de alertas guardada']);
    }

    /**
     * Preview an alert template.
     */
    public function ajax_preview_alert()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');

        // Sample data for preview
        $sample = [
            '{nombre}' => 'Juan Pérez',
            '{email}' => 'juan@ejemplo.com',
            '{servicio}' => 'Consulta General',
            '{fecha}' => date_i18n('l j \d\e F, Y'),
            '{hora}' => '10:00',
            '{link_cancelar}' => home_url('/cancelar-reserva/?codigo=ABC123&token=xyz'),
            '{link_modificar}' => home_url('/modificar-reserva/?codigo=ABC123&token=xyz'),
            '{zoom_url}' => 'https://zoom.us/j/123456789',
        ];

        $preview_subject = str_replace(array_keys($sample), array_values($sample), $subject);
        $preview_message = str_replace(array_keys($sample), array_values($sample), $message);

        wp_send_json_success([
            'subject' => $preview_subject,
            'message' => nl2br(esc_html($preview_message)),
        ]);
    }

    // =========================================================================
    // AJAX HANDLERS - Integrations
    // =========================================================================

    /**
     * Test Twilio connection.
     */
    public function ajax_test_twilio()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $phone = sanitize_text_field($_POST['phone'] ?? '');

        if (empty($phone)) {
            wp_send_json_error(['message' => 'Ingresa un número de teléfono']);
        }

        if (class_exists('ReservasTerapia\\Twilio_Whatsapp')) {
            try {
                $twilio = \ReservasTerapia\Twilio_Whatsapp::get_instance();
                $result = $twilio->send_message($phone, 'Mensaje de prueba desde D.Calendar');

                if ($result) {
                    wp_send_json_success(['message' => 'Mensaje enviado correctamente']);
                } else {
                    wp_send_json_error(['message' => 'Error al enviar mensaje']);
                }
            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        } else {
            wp_send_json_error(['message' => 'Clase Twilio no disponible']);
        }
    }

    /**
     * Test Zoom connection.
     */
    public function ajax_test_zoom()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        if (class_exists('ReservasTerapia\\Zoom')) {
            try {
                $zoom = \ReservasTerapia\Zoom::get_instance();
                $user = $zoom->get_user_info();

                if ($user) {
                    wp_send_json_success([
                        'message' => 'Conexión exitosa',
                        'user' => $user,
                    ]);
                } else {
                    wp_send_json_error(['message' => 'No se pudo conectar']);
                }
            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        } else {
            wp_send_json_error(['message' => 'Clase Zoom no disponible']);
        }
    }

    /**
     * Save integration settings.
     */
    public function ajax_save_integration()
    {
        check_ajax_referer('rt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permiso denegado']);
        }

        $type = sanitize_text_field($_POST['type'] ?? '');
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];

        switch ($type) {
            case 'zoom':
                update_option('rt_zoom_client_id', sanitize_text_field($settings['client_id'] ?? ''));
                update_option('rt_zoom_client_secret', sanitize_text_field($settings['client_secret'] ?? ''));
                break;

            case 'google':
                update_option('rt_google_client_id', sanitize_text_field($settings['client_id'] ?? ''));
                update_option('rt_google_client_secret', sanitize_text_field($settings['client_secret'] ?? ''));
                update_option('rt_google_calendar_id', sanitize_text_field($settings['calendar_id'] ?? ''));
                break;

            default:
                wp_send_json_error(['message' => 'Tipo de integración no válido']);
        }

        wp_send_json_success(['message' => 'Configuración guardada']);
    }
}
