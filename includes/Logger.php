<?php
/**
 * Logger Class
 *
 * Handles logging for the plugin using WordPress error_log with structured JSON data.
 *
 * @package    ReservasTerapia
 * @subpackage ReservasTerapia/includes
 */

namespace ReservasTerapia;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Logger
 */
class Logger
{
    /**
     * Log informational messages.
     *
     * @param string $message The message to log.
     * @param string $context context description (e.g. 'system', 'booking', 'error').
     * @param array  $data    Optional. Additional data to log.
     */
    public static function info($message, $context = 'system', $data = [])
    {
        error_log(json_encode([
            'msg' => $message,
            'context' => $context,
            'data' => $data,
            'level' => 'INFO'
        ]));
    }

    /**
     * Log error messages.
     *
     * @param string $message The error message to log.
     * @param string $context context description.
     * @param array  $data    Optional. Additional data to log.
     */
    public static function error($message, $context = 'system', $data = [])
    {
        error_log(json_encode([
            'msg' => $message,
            'context' => $context,
            'data' => $data,
            'level' => 'ERROR'
        ]));
    }
}
