<?php
/**
 * The file that defines the core plugin class
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    ReservasTerapia
 * @subpackage ReservasTerapia/includes
 */

namespace ReservasTerapia;

use ReservasTerapia\Admin\Admin;
use ReservasTerapia\Frontend\Public_Area;
use ReservasTerapia\Twilio_Whatsapp;
use ReservasTerapia\Booking_Management;
use ReservasTerapia\Logger;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    ReservasTerapia
 * @subpackage ReservasTerapia/includes
 * @author     JPL <email@example.com>
 */
class Plugin
{

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The single instance of the class.
	 *
	 * @since    1.0.0
	 * @var      Plugin
	 */
	protected static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since    1.0.0
	 * @return   Plugin
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct()
	{
		$this->plugin_name = 'd-calendar';
		$this->version = RT_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_booking_hooks();
		$this->register_cron_jobs();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Loader. Orchestrates the hooks of the plugin.
	 * - i18n. Defines internationalization functionality.
	 * - Admin. Defines all hooks for the admin area.
	 * - Public_Area. Defines all hooks for the public side of the site.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
		$plugin_i18n = new i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Public_Area($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// Zoom Webhooks
		$this->loader->add_action('rest_api_init', Zoom::get_instance(), 'register_routes');

		// Twilio Webhooks & Cron
		$twilio = Twilio_Whatsapp::get_instance();
		$this->loader->add_action('rest_api_init', $twilio, 'register_routes');
		$this->loader->add_action('rest_api_init', $twilio, 'register_routes');
		$this->loader->add_action('rt_process_scheduled_messages', $twilio, 'process_scheduled_queue');

		// Cleaning up is handled in register_cron_jobs
	}

	/**
	 * Register all of the hooks related to booking management.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_booking_hooks()
	{
		$plugin_booking = new Booking_Management();

		$this->loader->add_action('init', $plugin_booking, 'register_rewrite_rules');
		$this->loader->add_filter('query_vars', $plugin_booking, 'register_query_vars');
		$this->loader->add_action('template_redirect', $plugin_booking, 'handle_template_redirect');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_booking, 'enqueue_assets');
		$this->loader->add_action('rest_api_init', $plugin_booking, 'register_rest_routes');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Code to run on activation.
	 *
	 * @since 1.0.0
	 */
	public static function activate()
	{
		// Activation logic here (e.g., create DB tables)
		// Classes are autoloaded via Composer PSR-4
		$database = new Database();
		$database->create_tables();

		$booking = new Booking_Management();
		$booking->register_rewrite_rules();
		flush_rewrite_rules();

		if (!wp_next_scheduled('rt_cleanup_expired_tokens')) {
			wp_schedule_event(time(), 'daily', 'rt_cleanup_expired_tokens');
		}
	}

	/**
	 * Code to run on deactivation.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate()
	{
		$timestamp = wp_next_scheduled('rt_cleanup_expired_tokens');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'rt_cleanup_expired_tokens');

			if (class_exists('ReservasTerapia\Logger')) {
				Logger::info(
					'Cron job de limpieza de tokens desregistrado',
					'sistema'
				);
			}
		}
	}

	/**
	 * Registra tareas programadas (WP Cron)
	 */
	private function register_cron_jobs()
	{

		// Registrar evento si no existe
		if (!wp_next_scheduled('rt_cleanup_expired_tokens')) {
			wp_schedule_event(
				time(),
				'daily', // Una vez al día
				'rt_cleanup_expired_tokens'
			);

			Logger::info(
				'Cron job de limpieza de tokens registrado',
				'sistema'
			);
		}

		// Registrar callback
		add_action('rt_cleanup_expired_tokens', function () {
			$database = new Database();
			$deleted = $database->cleanup_expired_tokens();

			Logger::info(
				"Limpieza automática ejecutada: {$deleted} tokens eliminados",
				'sistema'
			);
		});
	}


}
