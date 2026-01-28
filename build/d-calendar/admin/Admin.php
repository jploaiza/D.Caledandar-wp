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

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
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
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, RT_URL . 'assets/css/admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->plugin_name, RT_URL . 'assets/js/admin.js', array('jquery'), $this->version, false);
    }
    /**
     * Register the administration menu for this plugin into the WordPress Dashboard.
     *
     * @since    1.0.0
     */

    /**
     * Register the logs submenu.
     */
    public function add_plugin_admin_menu()
    {
        add_menu_page(
            'D.Calendar',
            'D.Calendar',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            $this->plugin_name,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page')
        );

        add_submenu_page(
            $this->plugin_name,
            'WhatsApp Logs',
            'WhatsApp Logs',
            'manage_options',
            'rt-whatsapp-logs',
            array($this, 'display_twilio_logs_page')
        );
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
        register_setting($this->plugin_name, 'rt_google_client_id');
        register_setting($this->plugin_name, 'rt_google_client_secret');
        register_setting($this->plugin_name, 'rt_google_redirect_uri');

        // Zoom Settings
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_client_id');
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_client_secret');
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_webhook_secret');
        register_setting($this->plugin_name . '_zoom', 'rt_zoom_record_meetings');

        // Twilio Settings
        register_setting($this->plugin_name, 'rt_twilio_account_sid');
        register_setting($this->plugin_name, 'rt_twilio_auth_token');
        register_setting($this->plugin_name, 'rt_twilio_whatsapp_number');
    }
}
