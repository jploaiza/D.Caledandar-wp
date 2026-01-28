<?php
/**
 * Define the internationalization functionality
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    ReservasTerapia
 * @subpackage ReservasTerapia/includes
 */

namespace ReservasTerapia;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    ReservasTerapia
 * @subpackage ReservasTerapia/includes
 * @author     JPL <email@example.com>
 */
class i18n
{

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'reservas-terapia',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
