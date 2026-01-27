<?php
/**
 * Settings Page class.
 *
 * Handles the plugin settings page using WordPress Settings API.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings Page class.
 *
 * @since 1.0.0
 */
class SettingsPage {

    /**
     * Singleton instance.
     *
     * @var SettingsPage|null
     */
    private static $instance = null;

    /**
     * Option group name.
     *
     * @var string
     */
    const OPTION_GROUP = 'wprh_settings';

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return SettingsPage
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
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register the settings menu.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=' . ResourcePostType::get_post_type(),
            __( 'Settings', 'wp-resource-hub' ),
            __( 'Settings', 'wp-resource-hub' ),
            'manage_options',
            'wprh-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_settings() {
        // Register settings.
        register_setting(
            self::OPTION_GROUP,
            'wprh_general_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_general_settings' ),
                'default'           => $this->get_general_defaults(),
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'wprh_frontend_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_frontend_settings' ),
                'default'           => $this->get_frontend_defaults(),
            )
        );

        // General settings section.
        add_settings_section(
            'wprh_general_section',
            __( 'General Settings', 'wp-resource-hub' ),
            array( $this, 'render_general_section' ),
            'wprh-settings-general'
        );

        // General settings fields.
        add_settings_field(
            'default_resource_type',
            __( 'Default Resource Type', 'wp-resource-hub' ),
            array( $this, 'render_default_type_field' ),
            'wprh-settings-general',
            'wprh_general_section'
        );

        add_settings_field(
            'default_ordering',
            __( 'Default Ordering', 'wp-resource-hub' ),
            array( $this, 'render_default_ordering_field' ),
            'wprh-settings-general',
            'wprh_general_section'
        );

        // Frontend settings section.
        add_settings_section(
            'wprh_frontend_section',
            __( 'Frontend Defaults', 'wp-resource-hub' ),
            array( $this, 'render_frontend_section' ),
            'wprh-settings-frontend'
        );

        // Frontend settings fields.
        add_settings_field(
            'items_per_page',
            __( 'Items Per Page', 'wp-resource-hub' ),
            array( $this, 'render_items_per_page_field' ),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'default_layout',
            __( 'Default Layout', 'wp-resource-hub' ),
            array( $this, 'render_default_layout_field' ),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'enable_filters',
            __( 'Enable Filters', 'wp-resource-hub' ),
            array( $this, 'render_enable_filters_field' ),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'internal_content_defaults',
            __( 'Internal Content Defaults', 'wp-resource-hub' ),
            array( $this, 'render_internal_content_defaults_field' ),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );
    }

    /**
     * Get default general settings.
     *
     * @since 1.0.0
     *
     * @return array
     */
    private function get_general_defaults() {
        return array(
            'default_resource_type' => '',
            'default_ordering'      => 'date',
        );
    }

    /**
     * Get default frontend settings.
     *
     * @since 1.0.0
     *
     * @return array
     */
    private function get_frontend_defaults() {
        return array(
            'items_per_page'          => 12,
            'default_layout'          => 'grid',
            'enable_type_filter'      => true,
            'enable_topic_filter'     => true,
            'enable_audience_filter'  => true,
            'default_show_toc'        => false,
            'default_show_reading_time' => true,
            'default_show_related'    => true,
        );
    }

    /**
     * Sanitize general settings.
     *
     * @since 1.0.0
     *
     * @param array $input Input values.
     * @return array
     */
    public function sanitize_general_settings( $input ) {
        $sanitized = array();

        $sanitized['default_resource_type'] = isset( $input['default_resource_type'] )
            ? sanitize_text_field( $input['default_resource_type'] )
            : '';

        $valid_orderings = array( 'date', 'title', 'manual', 'modified', 'menu_order' );
        $sanitized['default_ordering'] = isset( $input['default_ordering'] ) && in_array( $input['default_ordering'], $valid_orderings, true )
            ? $input['default_ordering']
            : 'date';

        /**
         * Filter the sanitized general settings.
         *
         * @since 1.0.0
         *
         * @param array $sanitized Sanitized settings.
         * @param array $input     Raw input.
         */
        return apply_filters( 'wprh_sanitize_general_settings', $sanitized, $input );
    }

    /**
     * Sanitize frontend settings.
     *
     * @since 1.0.0
     *
     * @param array $input Input values.
     * @return array
     */
    public function sanitize_frontend_settings( $input ) {
        $sanitized = array();

        $sanitized['items_per_page'] = isset( $input['items_per_page'] )
            ? absint( $input['items_per_page'] )
            : 12;

        $valid_layouts = array( 'grid', 'list' );
        $sanitized['default_layout'] = isset( $input['default_layout'] ) && in_array( $input['default_layout'], $valid_layouts, true )
            ? $input['default_layout']
            : 'grid';

        // Boolean fields.
        $sanitized['enable_type_filter']      = ! empty( $input['enable_type_filter'] );
        $sanitized['enable_topic_filter']     = ! empty( $input['enable_topic_filter'] );
        $sanitized['enable_audience_filter']  = ! empty( $input['enable_audience_filter'] );
        $sanitized['default_show_toc']        = ! empty( $input['default_show_toc'] );
        $sanitized['default_show_reading_time'] = ! empty( $input['default_show_reading_time'] );
        $sanitized['default_show_related']    = ! empty( $input['default_show_related'] );

        /**
         * Filter the sanitized frontend settings.
         *
         * @since 1.0.0
         *
         * @param array $sanitized Sanitized settings.
         * @param array $input     Raw input.
         */
        return apply_filters( 'wprh_sanitize_frontend_settings', $sanitized, $input );
    }

    /**
     * Render the settings page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $tabs = array(
            'general'  => __( 'General', 'wp-resource-hub' ),
            'frontend' => __( 'Frontend', 'wp-resource-hub' ),
        );

        /**
         * Filter the settings page tabs.
         *
         * @since 1.0.0
         *
         * @param array $tabs Settings tabs.
         */
        $tabs = apply_filters( 'wprh_settings_tabs', $tabs );
        ?>
        <div class="wrap wprh-settings">
            <h1><?php esc_html_e( 'Resource Hub Settings', 'wp-resource-hub' ); ?></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $tabs as $tab_id => $tab_name ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_name ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );

                switch ( $active_tab ) {
                    case 'frontend':
                        do_settings_sections( 'wprh-settings-frontend' );
                        break;

                    case 'general':
                    default:
                        do_settings_sections( 'wprh-settings-general' );
                        break;
                }

                /**
                 * Fires after settings sections are rendered.
                 *
                 * @since 1.0.0
                 *
                 * @param string $active_tab Current active tab.
                 */
                do_action( 'wprh_settings_sections', $active_tab );

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render general section description.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_general_section() {
        echo '<p>' . esc_html__( 'Configure general plugin settings.', 'wp-resource-hub' ) . '</p>';
    }

    /**
     * Render frontend section description.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_frontend_section() {
        echo '<p>' . esc_html__( 'Configure default settings for frontend display. These will be used when rendering resources on your site.', 'wp-resource-hub' ) . '</p>';
    }

    /**
     * Render default type field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_default_type_field() {
        $settings = get_option( 'wprh_general_settings', $this->get_general_defaults() );
        $current  = isset( $settings['default_resource_type'] ) ? $settings['default_resource_type'] : '';

        $types = get_terms(
            array(
                'taxonomy'   => ResourceTypeTax::get_taxonomy(),
                'hide_empty' => false,
            )
        );
        ?>
        <select name="wprh_general_settings[default_resource_type]" id="wprh_default_resource_type">
            <option value=""><?php esc_html_e( '— None —', 'wp-resource-hub' ); ?></option>
            <?php if ( ! is_wp_error( $types ) && ! empty( $types ) ) : ?>
                <?php foreach ( $types as $type ) : ?>
                    <option value="<?php echo esc_attr( $type->slug ); ?>" <?php selected( $current, $type->slug ); ?>>
                        <?php echo esc_html( $type->name ); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Pre-select this resource type when creating new resources.', 'wp-resource-hub' ); ?></p>
        <?php
    }

    /**
     * Render default ordering field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_default_ordering_field() {
        $settings = get_option( 'wprh_general_settings', $this->get_general_defaults() );
        $current  = isset( $settings['default_ordering'] ) ? $settings['default_ordering'] : 'date';

        $options = array(
            'date'       => __( 'Date (newest first)', 'wp-resource-hub' ),
            'title'      => __( 'Title (A-Z)', 'wp-resource-hub' ),
            'modified'   => __( 'Last Modified', 'wp-resource-hub' ),
            'menu_order' => __( 'Menu Order (manual)', 'wp-resource-hub' ),
        );
        ?>
        <select name="wprh_general_settings[default_ordering]" id="wprh_default_ordering">
            <?php foreach ( $options as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Default ordering for resource listings on the frontend.', 'wp-resource-hub' ); ?></p>
        <?php
    }

    /**
     * Render items per page field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_items_per_page_field() {
        $settings = get_option( 'wprh_frontend_settings', $this->get_frontend_defaults() );
        $current  = isset( $settings['items_per_page'] ) ? $settings['items_per_page'] : 12;
        ?>
        <input type="number"
               name="wprh_frontend_settings[items_per_page]"
               id="wprh_items_per_page"
               value="<?php echo esc_attr( $current ); ?>"
               class="small-text"
               min="1"
               max="100">
        <p class="description"><?php esc_html_e( 'Number of resources to display per page in archive views.', 'wp-resource-hub' ); ?></p>
        <?php
    }

    /**
     * Render default layout field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_default_layout_field() {
        $settings = get_option( 'wprh_frontend_settings', $this->get_frontend_defaults() );
        $current  = isset( $settings['default_layout'] ) ? $settings['default_layout'] : 'grid';
        ?>
        <label>
            <input type="radio"
                   name="wprh_frontend_settings[default_layout]"
                   value="grid"
                   <?php checked( $current, 'grid' ); ?>>
            <?php esc_html_e( 'Grid', 'wp-resource-hub' ); ?>
        </label>
        <br>
        <label>
            <input type="radio"
                   name="wprh_frontend_settings[default_layout]"
                   value="list"
                   <?php checked( $current, 'list' ); ?>>
            <?php esc_html_e( 'List', 'wp-resource-hub' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Default layout for resource archive pages.', 'wp-resource-hub' ); ?></p>
        <?php
    }

    /**
     * Render enable filters field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_enable_filters_field() {
        $settings = get_option( 'wprh_frontend_settings', $this->get_frontend_defaults() );
        ?>
        <fieldset>
            <label>
                <input type="checkbox"
                       name="wprh_frontend_settings[enable_type_filter]"
                       value="1"
                       <?php checked( ! empty( $settings['enable_type_filter'] ) ); ?>>
                <?php esc_html_e( 'Resource Type filter', 'wp-resource-hub' ); ?>
            </label>
            <br>
            <label>
                <input type="checkbox"
                       name="wprh_frontend_settings[enable_topic_filter]"
                       value="1"
                       <?php checked( ! empty( $settings['enable_topic_filter'] ) ); ?>>
                <?php esc_html_e( 'Topic filter', 'wp-resource-hub' ); ?>
            </label>
            <br>
            <label>
                <input type="checkbox"
                       name="wprh_frontend_settings[enable_audience_filter]"
                       value="1"
                       <?php checked( ! empty( $settings['enable_audience_filter'] ) ); ?>>
                <?php esc_html_e( 'Audience filter', 'wp-resource-hub' ); ?>
            </label>
        </fieldset>
        <p class="description"><?php esc_html_e( 'Enable filtering options on archive pages.', 'wp-resource-hub' ); ?></p>
        <?php
    }

    /**
     * Render internal content defaults field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_internal_content_defaults_field() {
        $settings = get_option( 'wprh_frontend_settings', $this->get_frontend_defaults() );
        ?>
        <fieldset>
            <label>
                <input type="checkbox"
                       name="wprh_frontend_settings[default_show_toc]"
                       value="1"
                       <?php checked( ! empty( $settings['default_show_toc'] ) ); ?>>
                <?php esc_html_e( 'Show Table of Contents by default', 'wp-resource-hub' ); ?>
            </label>
            <br>
            <label>
                <input type="checkbox"
                       name="wprh_frontend_settings[default_show_reading_time]"
                       value="1"
                       <?php checked( ! empty( $settings['default_show_reading_time'] ) ); ?>>
                <?php esc_html_e( 'Show reading time by default', 'wp-resource-hub' ); ?>
            </label>
            <br>
            <label>
                <input type="checkbox"
                       name="wprh_frontend_settings[default_show_related]"
                       value="1"
                       <?php checked( ! empty( $settings['default_show_related'] ) ); ?>>
                <?php esc_html_e( 'Show related resources by default', 'wp-resource-hub' ); ?>
            </label>
        </fieldset>
        <p class="description"><?php esc_html_e( 'Default display options for Internal Content type resources.', 'wp-resource-hub' ); ?></p>
        <?php
    }

    /**
     * Get a setting value.
     *
     * @since 1.0.0
     *
     * @param string $group   Settings group ('general' or 'frontend').
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get_setting( $group, $key, $default = null ) {
        $option_name = 'wprh_' . $group . '_settings';
        $settings    = get_option( $option_name, array() );

        if ( isset( $settings[ $key ] ) ) {
            return $settings[ $key ];
        }

        return $default;
    }
}
