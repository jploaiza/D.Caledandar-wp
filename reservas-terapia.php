<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * administrative area. This file also includes all of the plugin dependencies.
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           ReservasTerapia
 *
 * @wordpress-plugin
 * Plugin Name:       Reservas Terapia
 * Plugin URI:        https://example.com/plugin-name
 * Description:       A comprehensive therapy reservation system for WordPress.
 * Version:           1.0.0
 * Author:            JPL
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       reservas-terapia
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Current plugin version.
 */
define('RT_VERSION', '1.0.0');

/**
 * Plugin root path.
 */
define('RT_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin root URL.
 */
define('RT_URL', plugin_dir_url(__FILE__));

/**
 * Autoloader for dependencies and plugin classes.
 */
if (file_exists(RT_PATH . 'vendor/autoload.php')) {
	require RT_PATH . 'vendor/autoload.php';
}



/**
 * The code that runs during plugin activation.
 */
function activate_reservas_terapia()
{
	ReservasTerapia\Plugin::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_reservas_terapia()
{
	ReservasTerapia\Plugin::deactivate();
}

register_activation_hook(__FILE__, 'activate_reservas_terapia');
register_deactivation_hook(__FILE__, 'deactivate_reservas_terapia');

/**
 * Begins execution of the plugin.
 */
function run_reservas_terapia()
{
	$plugin = ReservasTerapia\Plugin::get_instance();
	$plugin->run();
}

run_reservas_terapia();
