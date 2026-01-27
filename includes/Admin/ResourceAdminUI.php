<?php
/**
 * Resource Admin UI class.
 *
 * Handles the admin user interface for resources including menus and dashboard.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\ResourcePostType;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resource Admin UI class.
 *
 * @since 1.0.0
 */
class ResourceAdminUI {

    /**
     * Singleton instance.
     *
     * @var ResourceAdminUI|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return ResourceAdminUI
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_submenus' ) );
        add_action( 'admin_head', array( $this, 'admin_styles' ) );
        add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
    }

    /**
     * Register admin submenus.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_submenus() {
        // Dashboard submenu.
        add_submenu_page(
            'edit.php?post_type=' . ResourcePostType::get_post_type(),
            __( 'Dashboard', 'wp-resource-hub' ),
            __( 'Dashboard', 'wp-resource-hub' ),
            'manage_options',
            'wprh-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        // Settings submenu (handled by SettingsPage class, but we need to position it).
        // The actual page is registered in SettingsPage class.

        // License submenu (placeholder for Phase 4).
        add_submenu_page(
            'edit.php?post_type=' . ResourcePostType::get_post_type(),
            __( 'License', 'wp-resource-hub' ),
            __( 'License', 'wp-resource-hub' ),
            'manage_options',
            'wprh-license',
            array( $this, 'render_license_page' )
        );
    }

    /**
     * Render the dashboard page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_dashboard_page() {
        $resources_count = wp_count_posts( ResourcePostType::get_post_type() );
        $published       = isset( $resources_count->publish ) ? $resources_count->publish : 0;
        $draft           = isset( $resources_count->draft ) ? $resources_count->draft : 0;
        ?>
        <div class="wrap wprh-dashboard">
            <h1><?php esc_html_e( 'Resources Hub Dashboard', 'wp-resource-hub' ); ?></h1>

            <div class="wprh-dashboard-welcome">
                <h2><?php esc_html_e( 'Welcome to WP Resource Hub', 'wp-resource-hub' ); ?></h2>
                <p><?php esc_html_e( 'Manage all your resources from one central location. Create videos, PDFs, downloads, external links, and internal content resources.', 'wp-resource-hub' ); ?></p>
            </div>

            <div class="wprh-dashboard-stats">
                <div class="wprh-stat-box">
                    <span class="dashicons dashicons-media-document"></span>
                    <div class="wprh-stat-content">
                        <span class="wprh-stat-number"><?php echo esc_html( $published ); ?></span>
                        <span class="wprh-stat-label"><?php esc_html_e( 'Published Resources', 'wp-resource-hub' ); ?></span>
                    </div>
                </div>
                <div class="wprh-stat-box">
                    <span class="dashicons dashicons-edit"></span>
                    <div class="wprh-stat-content">
                        <span class="wprh-stat-number"><?php echo esc_html( $draft ); ?></span>
                        <span class="wprh-stat-label"><?php esc_html_e( 'Draft Resources', 'wp-resource-hub' ); ?></span>
                    </div>
                </div>
            </div>

            <div class="wprh-dashboard-actions">
                <h3><?php esc_html_e( 'Quick Actions', 'wp-resource-hub' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=resource' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Add New Resource', 'wp-resource-hub' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=resource' ) ); ?>" class="button">
                    <?php esc_html_e( 'View All Resources', 'wp-resource-hub' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=resource&page=wprh-settings' ) ); ?>" class="button">
                    <?php esc_html_e( 'Settings', 'wp-resource-hub' ); ?>
                </a>
            </div>

            <div class="wprh-dashboard-info">
                <h3><?php esc_html_e( 'Getting Started', 'wp-resource-hub' ); ?></h3>
                <ol>
                    <li><?php esc_html_e( 'Create your first resource by clicking "Add New Resource"', 'wp-resource-hub' ); ?></li>
                    <li><?php esc_html_e( 'Choose the resource type (Video, PDF, Download, External Link, or Internal Content)', 'wp-resource-hub' ); ?></li>
                    <li><?php esc_html_e( 'Fill in the required fields for your chosen resource type', 'wp-resource-hub' ); ?></li>
                    <li><?php esc_html_e( 'Assign topics and audience levels to help organize your content', 'wp-resource-hub' ); ?></li>
                    <li><?php esc_html_e( 'Publish and share your resource with your audience', 'wp-resource-hub' ); ?></li>
                </ol>
            </div>

            <?php
            /**
             * Fires at the end of the dashboard page content.
             *
             * @since 1.0.0
             */
            do_action( 'wprh_dashboard_after' );
            ?>
        </div>
        <?php
    }

    /**
     * Render the license page (placeholder).
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_license_page() {
        ?>
        <div class="wrap wprh-license">
            <h1><?php esc_html_e( 'License', 'wp-resource-hub' ); ?></h1>

            <div class="wprh-license-placeholder">
                <span class="dashicons dashicons-lock"></span>
                <h2><?php esc_html_e( 'License Management', 'wp-resource-hub' ); ?></h2>
                <p><?php esc_html_e( 'License management and automatic updates will be available in a future version.', 'wp-resource-hub' ); ?></p>
            </div>

            <?php
            /**
             * Fires on the license page.
             *
             * Use this hook to add custom license management functionality.
             *
             * @since 1.0.0
             */
            do_action( 'wprh_license_page' );
            ?>
        </div>
        <?php
    }

    /**
     * Add inline admin styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function admin_styles() {
        global $post_type;

        if ( ResourcePostType::get_post_type() !== $post_type ) {
            return;
        }
        ?>
        <style>
            .wprh-dashboard-welcome {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                margin: 20px 0;
            }
            .wprh-dashboard-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }
            .wprh-stat-box {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                display: flex;
                align-items: center;
                gap: 15px;
                min-width: 200px;
            }
            .wprh-stat-box .dashicons {
                font-size: 40px;
                width: 40px;
                height: 40px;
                color: #2271b1;
            }
            .wprh-stat-number {
                display: block;
                font-size: 28px;
                font-weight: 600;
                line-height: 1.2;
            }
            .wprh-stat-label {
                color: #646970;
            }
            .wprh-dashboard-actions,
            .wprh-dashboard-info {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                margin: 20px 0;
            }
            .wprh-dashboard-actions .button {
                margin-right: 10px;
            }
            .wprh-license-placeholder {
                background: #fff;
                padding: 40px;
                border: 1px solid #ccd0d4;
                text-align: center;
                margin: 20px 0;
            }
            .wprh-license-placeholder .dashicons {
                font-size: 60px;
                width: 60px;
                height: 60px;
                color: #646970;
            }
        </style>
        <?php
    }

    /**
     * Add body class to resource admin pages.
     *
     * @since 1.0.0
     *
     * @param string $classes Current body classes.
     * @return string
     */
    public function add_body_class( $classes ) {
        global $post_type;

        if ( ResourcePostType::get_post_type() === $post_type ) {
            $classes .= ' wprh-admin';
        }

        return $classes;
    }
}
