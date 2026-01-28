<?php

/**
 * Plugin Name: WP Resource Hub
 * Plugin URI: https://example.com/wp-resource-hub
 * Description: A comprehensive resource management system for WordPress. Create, organize, and display videos, PDFs, downloads, external links, and internal content.
 * Version: 1.0.2
 * Author: Your Company Name
 * Author URI: https://example.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-resource-hub
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package WPResourceHub
 */

namespace WPResourceHub;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin version.
 */
define('WPRH_VERSION', '1.0.2');

/**
 * Plugin file path.
 */
define('WPRH_PLUGIN_FILE', __FILE__);

/**
 * Plugin directory path.
 */
define('WPRH_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('WPRH_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('WPRH_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Minimum PHP version required.
 */
define('WPRH_MIN_PHP_VERSION', '7.4');

/**
 * Minimum WordPress version required.
 */
define('WPRH_MIN_WP_VERSION', '5.8');

/**
 * Check PHP version requirement.
 *
 * @return bool
 */
function wprh_check_php_version()
{
    return version_compare(PHP_VERSION, WPRH_MIN_PHP_VERSION, '>=');
}

/**
 * Check WordPress version requirement.
 *
 * @return bool
 */
function wprh_check_wp_version()
{
    return version_compare(get_bloginfo('version'), WPRH_MIN_WP_VERSION, '>=');
}

/**
 * Display admin notice for version requirements.
 *
 * @return void
 */
function wprh_version_notice()
{
    $message = '';

    if (! wprh_check_php_version()) {
        $message = sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            esc_html__('WP Resource Hub requires PHP version %1$s or higher. You are running version %2$s.', 'wp-resource-hub'),
            WPRH_MIN_PHP_VERSION,
            PHP_VERSION
        );
    } elseif (! wprh_check_wp_version()) {
        $message = sprintf(
            /* translators: 1: Required WordPress version, 2: Current WordPress version */
            esc_html__('WP Resource Hub requires WordPress version %1$s or higher. You are running version %2$s.', 'wp-resource-hub'),
            WPRH_MIN_WP_VERSION,
            get_bloginfo('version')
        );
    }

    if ($message) {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($message)
        );
    }
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function wprh_init()
{
    // Check version requirements.
    if (! wprh_check_php_version() || ! wprh_check_wp_version()) {
        add_action('admin_notices', __NAMESPACE__ . '\\wprh_version_notice');
        return;
    }

    // Load autoloader.
    require_once WPRH_PLUGIN_DIR . 'includes/Autoloader.php';
    Autoloader::register();

    // Initialize the plugin.
    Plugin::get_instance();
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function wprh_activate()
{
    // Check version requirements.
    if (! wprh_check_php_version() || ! wprh_check_wp_version()) {
        deactivate_plugins(WPRH_PLUGIN_BASENAME);
        wp_die(
            esc_html__('WP Resource Hub cannot be activated due to version requirements. Please check your PHP and WordPress versions.', 'wp-resource-hub'),
            esc_html__('Plugin Activation Error', 'wp-resource-hub'),
            array('back_link' => true)
        );
    }

    // Load autoloader for activation.
    require_once WPRH_PLUGIN_DIR . 'includes/Autoloader.php';
    Autoloader::register();

    // Register CPT and taxonomies for flush.
    PostTypes\ResourcePostType::get_instance()->register();
    PostTypes\CollectionPostType::get_instance()->register();
    Taxonomies\ResourceTypeTax::get_instance()->register();
    Taxonomies\ResourceTopicTax::get_instance()->register();
    Taxonomies\ResourceAudienceTax::get_instance()->register();

    // Create stats table.
    Stats\StatsManager::create_table();

    // Flush rewrite rules.
    flush_rewrite_rules();

    // Set activation flag for welcome redirect.
    set_transient('wprh_activation_redirect', true, 30);

    // Store plugin version.
    update_option('wprh_version', WPRH_VERSION);

    /**
     * Fires after the plugin is activated.
     *
     * @since 1.0.0
     */
    do_action('wprh_activated');
}

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function wprh_deactivate()
{
    // Flush rewrite rules.
    flush_rewrite_rules();

    /**
     * Fires after the plugin is deactivated.
     *
     * @since 1.0.0
     */
    do_action('wprh_deactivated');
}

// Register activation and deactivation hooks.
register_activation_hook(__FILE__, __NAMESPACE__ . '\\wprh_activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\wprh_deactivate');

// Initialize the plugin.
add_action('plugins_loaded', __NAMESPACE__ . '\\wprh_init');