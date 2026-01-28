<?php
/**
 * Main Plugin class.
 *
 * Bootstraps all plugin components and coordinates initialization.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\PostTypes\CollectionPostType;
use WPResourceHub\PostTypes\AccordionPostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;
use WPResourceHub\Admin\ResourceAdminUI;
use WPResourceHub\Admin\MetaBoxes;
use WPResourceHub\Admin\CollectionMetaBoxes;
use WPResourceHub\Admin\AccordionMetaBoxes;
use WPResourceHub\Admin\SettingsPage;
use WPResourceHub\Admin\ListTableEnhancements;
use WPResourceHub\Admin\ImportExportPage;
use WPResourceHub\Admin\BulkActions;
use WPResourceHub\Frontend\TemplateLoader;
use WPResourceHub\Frontend\SingleRenderer;
use WPResourceHub\Hooks\Filters;
use WPResourceHub\Hooks\Actions;
use WPResourceHub\Stats\StatsManager;
use WPResourceHub\Stats\DownloadTracker;
use WPResourceHub\ImportExport\Importer;
use WPResourceHub\ImportExport\Exporter;
use WPResourceHub\AccessControl\AccessManager;
use WPResourceHub\Shortcodes\ResourcesShortcode;
use WPResourceHub\Shortcodes\ResourceShortcode;
use WPResourceHub\Shortcodes\CollectionShortcode;
use WPResourceHub\Shortcodes\AccordionShortcode;
use WPResourceHub\Blocks\BlocksManager;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin class.
 *
 * @since 1.0.0
 */
final class Plugin {

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Resource post type instance.
     *
     * @var ResourcePostType
     */
    public $post_type;

    /**
     * Resource type taxonomy instance.
     *
     * @var ResourceTypeTax
     */
    public $resource_type_tax;

    /**
     * Resource topic taxonomy instance.
     *
     * @var ResourceTopicTax
     */
    public $resource_topic_tax;

    /**
     * Resource audience taxonomy instance.
     *
     * @var ResourceAudienceTax
     */
    public $resource_audience_tax;

    /**
     * Admin UI instance.
     *
     * @var ResourceAdminUI
     */
    public $admin_ui;

    /**
     * Meta boxes instance.
     *
     * @var MetaBoxes
     */
    public $meta_boxes;

    /**
     * Settings page instance.
     *
     * @var SettingsPage
     */
    public $settings_page;

    /**
     * List table enhancements instance.
     *
     * @var ListTableEnhancements
     */
    public $list_table;

    /**
     * Template loader instance.
     *
     * @var TemplateLoader
     */
    public $template_loader;

    /**
     * Single renderer instance.
     *
     * @var SingleRenderer
     */
    public $single_renderer;

    /**
     * Filters instance.
     *
     * @var Filters
     */
    public $filters;

    /**
     * Actions instance.
     *
     * @var Actions
     */
    public $actions;

    /**
     * Collection post type instance.
     *
     * @var CollectionPostType
     */
    public $collection_post_type;

    /**
     * Accordion post type instance.
     *
     * @var AccordionPostType
     */
    public $accordion_post_type;

    /**
     * Stats manager instance.
     *
     * @var StatsManager
     */
    public $stats_manager;

    /**
     * Download tracker instance.
     *
     * @var DownloadTracker
     */
    public $download_tracker;

    /**
     * Access manager instance.
     *
     * @var AccessManager
     */
    public $access_manager;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return Plugin
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
        add_action( 'init', array( $this, 'load_textdomain' ), 0 );
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Prevent cloning.
     *
     * @since 1.0.0
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     *
     * @since 1.0.0
     *
     * @throws \Exception When attempting to unserialize.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    /**
     * Load plugin text domain.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-resource-hub',
            false,
            dirname( WPRH_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Initialize plugin components.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function init_components() {
        // Initialize hooks first.
        $this->filters = Filters::get_instance();
        $this->actions = Actions::get_instance();

        // Initialize post type.
        $this->post_type = ResourcePostType::get_instance();

        // Initialize taxonomies.
        $this->resource_type_tax     = ResourceTypeTax::get_instance();
        $this->resource_topic_tax    = ResourceTopicTax::get_instance();
        $this->resource_audience_tax = ResourceAudienceTax::get_instance();

        // Initialize admin components.
        if ( is_admin() ) {
            $this->admin_ui      = ResourceAdminUI::get_instance();
            $this->meta_boxes    = MetaBoxes::get_instance();
            $this->settings_page = SettingsPage::get_instance();
            $this->list_table    = ListTableEnhancements::get_instance();

            // Phase 2 admin components.
            CollectionMetaBoxes::get_instance();
            if ( self::is_accordions_enabled() ) {
                AccordionMetaBoxes::get_instance();
            }
            ImportExportPage::get_instance();
            BulkActions::get_instance();
        }

        // Initialize frontend components.
        $this->template_loader  = TemplateLoader::get_instance();
        $this->single_renderer  = SingleRenderer::get_instance();

        // Phase 2 components (both admin and frontend).
        $this->collection_post_type = CollectionPostType::get_instance();
        if ( self::is_accordions_enabled() ) {
            $this->accordion_post_type = AccordionPostType::get_instance();
        }
        $this->stats_manager        = StatsManager::get_instance();
        $this->download_tracker     = DownloadTracker::get_instance();
        $this->access_manager       = AccessManager::get_instance();

        // Initialize importers/exporters.
        Importer::get_instance();
        Exporter::get_instance();

        // Phase 3: Shortcodes.
        ResourcesShortcode::get_instance();
        ResourceShortcode::get_instance();
        CollectionShortcode::get_instance();
        if ( self::is_accordions_enabled() ) {
            AccordionShortcode::get_instance();
        }

        // Phase 3: Gutenberg Blocks.
        BlocksManager::get_instance();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function init_hooks() {
        // Register post type and taxonomies.
        add_action( 'init', array( $this, 'register_post_types' ), 5 );
        add_action( 'init', array( $this, 'register_taxonomies' ), 5 );

        // Handle activation redirect.
        add_action( 'admin_init', array( $this, 'maybe_redirect_after_activation' ) );

        /**
         * Fires after the plugin has been fully initialized.
         *
         * @since 1.0.0
         *
         * @param Plugin $plugin The plugin instance.
         */
        do_action( 'wprh_loaded', $this );
    }

    /**
     * Register custom post types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_post_types() {
        $this->post_type->register();
    }

    /**
     * Register custom taxonomies.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_taxonomies() {
        $this->resource_type_tax->register();
        $this->resource_topic_tax->register();
        $this->resource_audience_tax->register();

        /**
         * Fires after all default taxonomies are registered.
         *
         * Use this hook to register additional custom taxonomies for resources.
         *
         * @since 1.0.0
         */
        do_action( 'wprh_register_taxonomies' );
    }

    /**
     * Redirect to settings page after activation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function maybe_redirect_after_activation() {
        if ( ! get_transient( 'wprh_activation_redirect' ) ) {
            return;
        }

        delete_transient( 'wprh_activation_redirect' );

        // Don't redirect if activating multiple plugins.
        if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        wp_safe_redirect( admin_url( 'edit.php?post_type=resource&page=wprh-settings' ) );
        exit;
    }

    /**
     * Get the plugin version.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_version() {
        return WPRH_VERSION;
    }

    /**
     * Get the plugin directory path.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_plugin_dir() {
        return WPRH_PLUGIN_DIR;
    }

    /**
     * Get the plugin directory URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_plugin_url() {
        return WPRH_PLUGIN_URL;
    }

    /**
     * Get the plugin file path.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_plugin_file() {
        return WPRH_PLUGIN_FILE;
    }

    /**
     * Check if the accordion feature is enabled.
     *
     * @since 1.3.0
     *
     * @return bool
     */
    public static function is_accordions_enabled() {
        $settings = get_option( 'wprh_general_settings', array() );
        return ! empty( $settings['enable_accordions'] );
    }
}
