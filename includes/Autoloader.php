<?php
/**
 * Autoloader class.
 *
 * Handles autoloading of plugin classes following PSR-4 conventions.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Autoloader class.
 *
 * @since 1.0.0
 */
class Autoloader {

    /**
     * Namespace prefix.
     *
     * @var string
     */
    private static $namespace_prefix = 'WPResourceHub\\';

    /**
     * Base directory for classes.
     *
     * @var string
     */
    private static $base_dir;

    /**
     * Register the autoloader.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function register() {
        self::$base_dir = WPRH_PLUGIN_DIR . 'includes/';
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Autoload callback.
     *
     * @since 1.0.0
     *
     * @param string $class The fully-qualified class name.
     * @return void
     */
    public static function autoload( $class ) {
        // Check if the class uses our namespace prefix.
        $len = strlen( self::$namespace_prefix );
        if ( strncmp( self::$namespace_prefix, $class, $len ) !== 0 ) {
            return;
        }

        // Get the relative class name.
        $relative_class = substr( $class, $len );

        // Replace namespace separators with directory separators.
        $file = self::$base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

        // If the file exists, require it.
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }

    /**
     * Get the base directory.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_base_dir() {
        return self::$base_dir;
    }
}
