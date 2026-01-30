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
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings Page class.
 *
 * @since 1.0.0
 */
class SettingsPage
{

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
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register the settings menu.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_menu()
    {
        add_submenu_page(
            'edit.php?post_type=' . ResourcePostType::get_post_type(),
            __('Settings', 'wp-resource-hub'),
            __('Settings', 'wp-resource-hub'),
            'manage_options',
            'wprh-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_settings()
    {
        // Register settings.
        register_setting(
            'wprh_settings_general',
            'wprh_general_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize_general_settings'),
                'default'           => $this->get_general_defaults(),
            )
        );

        register_setting(
            'wprh_settings_frontend',
            'wprh_frontend_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize_frontend_settings'),
                'default'           => $this->get_frontend_defaults(),
            )
        );

        // General settings section.
        add_settings_section(
            'wprh_general_section',
            __('General Settings', 'wp-resource-hub'),
            array($this, 'render_general_section'),
            'wprh-settings-general'
        );

        // General settings fields.
        add_settings_field(
            'default_resource_type',
            __('Default Resource Type', 'wp-resource-hub'),
            array($this, 'render_default_type_field'),
            'wprh-settings-general',
            'wprh_general_section'
        );

        add_settings_field(
            'default_ordering',
            __('Default Ordering', 'wp-resource-hub'),
            array($this, 'render_default_ordering_field'),
            'wprh-settings-general',
            'wprh_general_section'
        );

        add_settings_field(
            'enable_accordions',
            __('Accordion Builder', 'wp-resource-hub'),
            array($this, 'render_enable_accordions_field'),
            'wprh-settings-general',
            'wprh_general_section'
        );

        // Frontend settings section.
        add_settings_section(
            'wprh_frontend_section',
            __('Frontend Defaults', 'wp-resource-hub'),
            array($this, 'render_frontend_section'),
            'wprh-settings-frontend'
        );

        // Frontend settings fields.
        add_settings_field(
            'items_per_page',
            __('Items Per Page', 'wp-resource-hub'),
            array($this, 'render_items_per_page_field'),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'default_layout',
            __('Default Layout', 'wp-resource-hub'),
            array($this, 'render_default_layout_field'),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'enable_filters',
            __('Toolbar Filters &amp; Order', 'wp-resource-hub'),
            array($this, 'render_enable_filters_field'),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'card_elements',
            __('Card Elements', 'wp-resource-hub'),
            array($this, 'render_card_elements_field'),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'internal_content_defaults',
            __('Internal Content Defaults', 'wp-resource-hub'),
            array($this, 'render_internal_content_defaults_field'),
            'wprh-settings-frontend',
            'wprh_frontend_section'
        );

        add_settings_field(
            'video_lightbox_only',
            __('Video Display Mode', 'wp-resource-hub'),
            array($this, 'render_video_lightbox_field'),
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
    private function get_general_defaults()
    {
        return array(
            'default_resource_type' => '',
            'default_ordering'      => 'date',
            'enable_accordions'     => false,
        );
    }

    /**
     * Get default frontend settings.
     *
     * @since 1.0.0
     *
     * @return array
     */
    private function get_frontend_defaults()
    {
        return array(
            'items_per_page'          => 12,
            'default_layout'          => 'grid',
            'enable_search'           => true,
            'enable_type_filter'      => true,
            'enable_topic_filter'     => true,
            'enable_audience_filter'  => true,
            'enable_duration_filter'  => true,
            'enable_sort_filter'      => true,
            'enable_layout_toggle'    => true,
            'filter_order'            => array('search', 'type', 'topic', 'audience', 'duration', 'sort', 'layout_toggle'),
            'featured_filters'        => array(),
            'show_card_footer'        => true,
            'default_show_toc'        => false,
            'default_show_reading_time' => true,
            'default_show_related'    => true,
            'video_lightbox_only'     => true,
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
    public function sanitize_general_settings($input)
    {
        $sanitized = array();

        $sanitized['default_resource_type'] = isset($input['default_resource_type'])
            ? sanitize_text_field($input['default_resource_type'])
            : '';

        $valid_orderings = array('date', 'title', 'manual', 'modified', 'menu_order');
        $sanitized['default_ordering'] = isset($input['default_ordering']) && in_array($input['default_ordering'], $valid_orderings, true)
            ? $input['default_ordering']
            : 'date';

        $sanitized['enable_accordions'] = ! empty($input['enable_accordions']);

        /**
         * Filter the sanitized general settings.
         *
         * @since 1.0.0
         *
         * @param array $sanitized Sanitized settings.
         * @param array $input     Raw input.
         */
        return apply_filters('wprh_sanitize_general_settings', $sanitized, $input);
    }

    /**
     * Sanitize frontend settings.
     *
     * @since 1.0.0
     *
     * @param array $input Input values.
     * @return array
     */
    public function sanitize_frontend_settings($input)
    {
        $sanitized = array();

        $sanitized['items_per_page'] = isset($input['items_per_page'])
            ? absint($input['items_per_page'])
            : 12;

        $valid_layouts = array('grid', 'list');
        $sanitized['default_layout'] = isset($input['default_layout']) && in_array($input['default_layout'], $valid_layouts, true)
            ? $input['default_layout']
            : 'grid';

        // Boolean fields.
        $sanitized['enable_search']           = ! empty($input['enable_search']);
        $sanitized['enable_type_filter']      = ! empty($input['enable_type_filter']);
        $sanitized['enable_topic_filter']     = ! empty($input['enable_topic_filter']);
        $sanitized['enable_audience_filter']  = ! empty($input['enable_audience_filter']);
        $sanitized['enable_duration_filter']  = ! empty($input['enable_duration_filter']);
        $sanitized['enable_sort_filter']      = ! empty($input['enable_sort_filter']);
        $sanitized['enable_layout_toggle']    = ! empty($input['enable_layout_toggle']);

        // Filter order.
        $valid_filter_keys = array('search', 'type', 'topic', 'audience', 'duration', 'sort', 'layout_toggle');
        $defaults = $this->get_frontend_defaults();
        if (! empty($input['filter_order']) && is_array($input['filter_order'])) {
            $order = array_values(array_intersect($input['filter_order'], $valid_filter_keys));
            // Append any missing keys.
            foreach ($valid_filter_keys as $key) {
                if (! in_array($key, $order, true)) {
                    $order[] = $key;
                }
            }
            $sanitized['filter_order'] = $order;
        } else {
            $sanitized['filter_order'] = $defaults['filter_order'];
        }

        // Featured filters.
        if (! empty($input['featured_filters']) && is_array($input['featured_filters'])) {
            $sanitized['featured_filters'] = array_values(array_intersect($input['featured_filters'], $valid_filter_keys));
        } else {
            $sanitized['featured_filters'] = array();
        }

        $sanitized['show_card_footer']        = ! empty($input['show_card_footer']);
        $sanitized['default_show_toc']        = ! empty($input['default_show_toc']);
        $sanitized['default_show_reading_time'] = ! empty($input['default_show_reading_time']);
        $sanitized['default_show_related']    = ! empty($input['default_show_related']);
        $sanitized['video_lightbox_only']     = ! empty($input['video_lightbox_only']);

        /**
         * Filter the sanitized frontend settings.
         *
         * @since 1.0.0
         *
         * @param array $sanitized Sanitized settings.
         * @param array $input     Raw input.
         */
        return apply_filters('wprh_sanitize_frontend_settings', $sanitized, $input);
    }

    /**
     * Render the settings page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $tabs = array(
            'general'     => __('General', 'wp-resource-hub'),
            'frontend'    => __('Frontend', 'wp-resource-hub'),
            'shortcodes'  => __('Shortcodes', 'wp-resource-hub'),
            'walkthrough' => __('Walkthrough', 'wp-resource-hub'),
        );

        /**
         * Filter the settings page tabs.
         *
         * @since 1.0.0
         *
         * @param array $tabs Settings tabs.
         */
        $tabs = apply_filters('wprh_settings_tabs', $tabs);
?>
        <div class="wrap wprh-settings">
            <h1><?php esc_html_e('Resource Hub Settings', 'wp-resource-hub'); ?></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name) : ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_id)); ?>"
                        class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($active_tab === 'walkthrough') : ?>
                <?php $this->render_walkthrough_content(); ?>
            <?php elseif ($active_tab === 'shortcodes') : ?>
                <?php $this->render_shortcodes_content(); ?>
            <?php else : ?>
                <form method="post" action="options.php">
                    <?php
                    switch ($active_tab) {
                        case 'frontend':
                            settings_fields('wprh_settings_frontend');
                            do_settings_sections('wprh-settings-frontend');
                            break;

                        case 'general':
                        default:
                            settings_fields('wprh_settings_general');
                            do_settings_sections('wprh-settings-general');
                            break;
                    }

                    /**
                     * Fires after settings sections are rendered.
                     *
                     * @since 1.0.0
                     *
                     * @param string $active_tab Current active tab.
                     */
                    do_action('wprh_settings_sections', $active_tab);

                    submit_button();
                    ?>
                </form>
            <?php endif; ?>
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
    public function render_general_section()
    {
        echo '<p>' . esc_html__('Configure general plugin settings.', 'wp-resource-hub') . '</p>';
    }

    /**
     * Render frontend section description.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_frontend_section()
    {
        echo '<p>' . esc_html__('Configure default settings for frontend display. These will be used when rendering resources on your site.', 'wp-resource-hub') . '</p>';
    }

    /**
     * Render default type field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_default_type_field()
    {
        $settings = get_option('wprh_general_settings', $this->get_general_defaults());
        $current  = isset($settings['default_resource_type']) ? $settings['default_resource_type'] : '';

        $types = get_terms(
            array(
                'taxonomy'   => ResourceTypeTax::get_taxonomy(),
                'hide_empty' => false,
            )
        );
    ?>
        <select name="wprh_general_settings[default_resource_type]" id="wprh_default_resource_type">
            <option value=""><?php esc_html_e('— None —', 'wp-resource-hub'); ?></option>
            <?php if (! is_wp_error($types) && ! empty($types)) : ?>
                <?php foreach ($types as $type) : ?>
                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($current, $type->slug); ?>>
                        <?php echo esc_html($type->name); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Pre-select this resource type when creating new resources.', 'wp-resource-hub'); ?></p>
    <?php
    }

    /**
     * Render default ordering field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_default_ordering_field()
    {
        $settings = get_option('wprh_general_settings', $this->get_general_defaults());
        $current  = isset($settings['default_ordering']) ? $settings['default_ordering'] : 'date';

        $options = array(
            'date'       => __('Date (newest first)', 'wp-resource-hub'),
            'title'      => __('Title (A-Z)', 'wp-resource-hub'),
            'modified'   => __('Last Modified', 'wp-resource-hub'),
            'menu_order' => __('Menu Order (manual)', 'wp-resource-hub'),
        );
    ?>
        <select name="wprh_general_settings[default_ordering]" id="wprh_default_ordering">
            <?php foreach ($options as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Default ordering for resource listings on the frontend.', 'wp-resource-hub'); ?></p>
    <?php
    }

    /**
     * Render enable accordions field.
     *
     * @since 1.3.0
     *
     * @return void
     */
    public function render_enable_accordions_field()
    {
        $settings = get_option('wprh_general_settings', $this->get_general_defaults());
    ?>
        <label>
            <input type="checkbox" name="wprh_general_settings[enable_accordions]" value="1"
                <?php checked(! empty($settings['enable_accordions'])); ?>>
            <?php esc_html_e('Enable the Accordion Builder post type and shortcode', 'wp-resource-hub'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, adds the Accordions post type under Resources, allowing you to build custom nested accordion structures. Use the [wprh_accordion id="123"] shortcode to display them.', 'wp-resource-hub'); ?>
        </p>
    <?php
    }

    /**
     * Render items per page field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_items_per_page_field()
    {
        $settings = get_option('wprh_frontend_settings', $this->get_frontend_defaults());
        $current  = isset($settings['items_per_page']) ? $settings['items_per_page'] : 12;
    ?>
        <input type="number" name="wprh_frontend_settings[items_per_page]" id="wprh_items_per_page"
            value="<?php echo esc_attr($current); ?>" class="small-text" min="1" max="100">
        <p class="description">
            <?php esc_html_e('Number of resources to display per page in archive views.', 'wp-resource-hub'); ?></p>
    <?php
    }

    /**
     * Render default layout field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_default_layout_field()
    {
        $settings = get_option('wprh_frontend_settings', $this->get_frontend_defaults());
        $current  = isset($settings['default_layout']) ? $settings['default_layout'] : 'grid';
    ?>
        <label>
            <input type="radio" name="wprh_frontend_settings[default_layout]" value="grid" <?php checked($current, 'grid'); ?>>
            <?php esc_html_e('Grid', 'wp-resource-hub'); ?>
        </label>
        <br>
        <label>
            <input type="radio" name="wprh_frontend_settings[default_layout]" value="list" <?php checked($current, 'list'); ?>>
            <?php esc_html_e('List', 'wp-resource-hub'); ?>
        </label>
        <p class="description"><?php esc_html_e('Default layout for resource archive pages.', 'wp-resource-hub'); ?></p>
    <?php
    }

    /**
     * Render enable filters field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_enable_filters_field()
    {
        $defaults = $this->get_frontend_defaults();
        $settings = get_option('wprh_frontend_settings', $defaults);

        $filter_items = array(
            'search'        => array(
                'label'   => __('Search', 'wp-resource-hub'),
                'setting' => 'enable_search',
                'icon'    => 'dashicons-search',
            ),
            'type'          => array(
                'label'   => __('Resource Type filter', 'wp-resource-hub'),
                'setting' => 'enable_type_filter',
                'icon'    => 'dashicons-tag',
            ),
            'topic'         => array(
                'label'   => __('Topic filter', 'wp-resource-hub'),
                'setting' => 'enable_topic_filter',
                'icon'    => 'dashicons-category',
            ),
            'audience'      => array(
                'label'   => __('Audience filter', 'wp-resource-hub'),
                'setting' => 'enable_audience_filter',
                'icon'    => 'dashicons-groups',
            ),
            'duration'      => array(
                'label'   => __('Duration filter (for videos)', 'wp-resource-hub'),
                'setting' => 'enable_duration_filter',
                'icon'    => 'dashicons-clock',
            ),
            'sort'          => array(
                'label'   => __('Sort By filter', 'wp-resource-hub'),
                'setting' => 'enable_sort_filter',
                'icon'    => 'dashicons-sort',
            ),
            'layout_toggle' => array(
                'label'   => __('Grid / List view toggle', 'wp-resource-hub'),
                'setting' => 'enable_layout_toggle',
                'icon'    => 'dashicons-grid-view',
            ),
        );

        $order = isset($settings['filter_order']) && is_array($settings['filter_order'])
            ? $settings['filter_order']
            : $defaults['filter_order'];

        // Ensure all keys are present.
        foreach (array_keys($filter_items) as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        $featured_filters = isset($settings['featured_filters']) && is_array($settings['featured_filters'])
            ? $settings['featured_filters']
            : array();

        wp_enqueue_script('jquery-ui-sortable');
    ?>
        <style>
            .wprh-filter-order-list {
                margin: 0;
                padding: 0;
                list-style: none;
                max-width: 400px;
            }
            .wprh-filter-order-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                margin-bottom: 4px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: move;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .wprh-filter-order-item:hover {
                border-color: #2271b1;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            }
            .wprh-filter-order-item.ui-sortable-helper {
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-color: #2271b1;
            }
            .wprh-filter-order-item.ui-sortable-placeholder {
                visibility: visible !important;
                background: #f0f6fc;
                border: 2px dashed #2271b1;
            }
            .wprh-filter-order-item .dashicons.wprh-drag-handle {
                color: #c3c4c7;
                font-size: 16px;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
            }
            .wprh-filter-order-item:hover .dashicons.wprh-drag-handle {
                color: #646970;
            }
            .wprh-filter-order-item .dashicons.wprh-filter-icon {
                color: #646970;
                font-size: 16px;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
            }
            .wprh-filter-order-item label {
                flex: 1;
                cursor: pointer;
                font-size: 13px;
            }
            .wprh-filter-order-item input[type="checkbox"] {
                margin: 0;
            }
            .wprh-featured-toggle {
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
                color: #c3c4c7;
                font-size: 16px;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                transition: color 0.2s;
            }
            .wprh-featured-toggle:hover {
                color: #dba617;
            }
            .wprh-featured-toggle.is-featured {
                color: #dba617;
            }
        </style>

        <ul class="wprh-filter-order-list" id="wprh-filter-order-list">
            <?php foreach ($order as $key) :
                if (! isset($filter_items[$key])) continue;
                $item = $filter_items[$key];
                $setting_key = $item['setting'];
                $is_enabled = isset($settings[$setting_key]) ? ! empty($settings[$setting_key]) : true;
                $field_name = 'wprh_frontend_settings[' . $setting_key . ']';
                $is_featured = in_array($key, $featured_filters, true);
            ?>
                <li class="wprh-filter-order-item" data-key="<?php echo esc_attr($key); ?>">
                    <span class="dashicons dashicons-menu wprh-drag-handle"></span>
                    <span class="dashicons <?php echo esc_attr($item['icon']); ?> wprh-filter-icon"></span>
                    <input type="checkbox" name="<?php echo esc_attr($field_name); ?>" value="1"
                        <?php checked($is_enabled); ?>>
                    <label><?php echo esc_html($item['label']); ?></label>
                    <button type="button" class="wprh-featured-toggle <?php echo $is_featured ? 'is-featured' : ''; ?>" title="<?php esc_attr_e('Toggle featured', 'wp-resource-hub'); ?>">
                        <span class="dashicons dashicons-star-<?php echo $is_featured ? 'filled' : 'empty'; ?>"></span>
                    </button>
                    <input type="hidden" class="wprh-featured-input" name="wprh_frontend_settings[featured_filters][]" value="<?php echo esc_attr($key); ?>" <?php echo $is_featured ? '' : 'disabled'; ?>>
                    <input type="hidden" name="wprh_frontend_settings[filter_order][]" value="<?php echo esc_attr($key); ?>">
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="description"><?php esc_html_e('Drag to reorder. Check to enable. This controls the toolbar order on archive pages.', 'wp-resource-hub'); ?></p>

        <script>
            jQuery(document).ready(function($) {
                $('#wprh-filter-order-list').sortable({
                    handle: '.wprh-drag-handle',
                    placeholder: 'wprh-filter-order-item ui-sortable-placeholder',
                    forcePlaceholderSize: true,
                    cursor: 'move',
                    opacity: 0.9
                });

                $('#wprh-filter-order-list').on('click', '.wprh-featured-toggle', function() {
                    var $btn = $(this);
                    var $icon = $btn.find('.dashicons');
                    var $input = $btn.siblings('.wprh-featured-input');
                    var isFeatured = $btn.hasClass('is-featured');

                    if (isFeatured) {
                        $btn.removeClass('is-featured');
                        $icon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                        $input.prop('disabled', true);
                    } else {
                        $btn.addClass('is-featured');
                        $icon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
                        $input.prop('disabled', false);
                    }
                });
            });
        </script>
    <?php
    }

    /**
     * Render card elements field.
     *
     * @since 1.4.0
     *
     * @return void
     */
    public function render_card_elements_field()
    {
        $settings = get_option('wprh_frontend_settings', $this->get_frontend_defaults());
    ?>
        <fieldset>
            <label>
                <input type="checkbox" name="wprh_frontend_settings[show_card_footer]" value="1"
                    <?php checked(! empty($settings['show_card_footer'])); ?>>
                <?php esc_html_e('Show card footer (taxonomy pills)', 'wp-resource-hub'); ?>
            </label>
        </fieldset>
        <p class="description"><?php esc_html_e('Control which elements are visible on resource cards in grid and list views.', 'wp-resource-hub'); ?></p>
    <?php
    }

    /**
     * Render internal content defaults field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_internal_content_defaults_field()
    {
        $settings = get_option('wprh_frontend_settings', $this->get_frontend_defaults());
    ?>
        <fieldset>
            <label>
                <input type="checkbox" name="wprh_frontend_settings[default_show_toc]" value="1"
                    <?php checked(! empty($settings['default_show_toc'])); ?>>
                <?php esc_html_e('Show Table of Contents by default', 'wp-resource-hub'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="wprh_frontend_settings[default_show_reading_time]" value="1"
                    <?php checked(! empty($settings['default_show_reading_time'])); ?>>
                <?php esc_html_e('Show reading time by default', 'wp-resource-hub'); ?>
            </label>
            <br>
            <label>
                <input type="checkbox" name="wprh_frontend_settings[default_show_related]" value="1"
                    <?php checked(! empty($settings['default_show_related'])); ?>>
                <?php esc_html_e('Show related resources by default', 'wp-resource-hub'); ?>
            </label>
        </fieldset>
        <p class="description">
            <?php esc_html_e('Default display options for Internal Content type resources.', 'wp-resource-hub'); ?></p>
    <?php
    }

    /**
     * Render shortcodes reference content.
     *
     * @since 1.3.0
     *
     * @return void
     */
    public function render_shortcodes_content()
    {
        $accordions_enabled = \WPResourceHub\Plugin::is_accordions_enabled();
    ?>
        <style>
            .wprh-shortcodes-ref {
                max-width: 900px;
                margin: 20px 0;
            }
            .wprh-shortcode-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .wprh-shortcode-card h3 {
                margin-top: 0;
                font-size: 16px;
                color: #1d2327;
            }
            .wprh-shortcode-card .wprh-shortcode-tag {
                display: inline-block;
                background: #f0f0f1;
                border: 1px solid #c3c4c7;
                padding: 6px 12px;
                border-radius: 3px;
                font-family: Consolas, Monaco, monospace;
                font-size: 13px;
                margin: 8px 0 12px;
                user-select: all;
            }
            .wprh-shortcode-card table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px;
            }
            .wprh-shortcode-card th,
            .wprh-shortcode-card td {
                text-align: left;
                padding: 8px 12px;
                border-bottom: 1px solid #f0f0f1;
                font-size: 13px;
            }
            .wprh-shortcode-card th {
                background: #f6f7f7;
                font-weight: 600;
            }
            .wprh-shortcode-card code {
                background: #f6f7f7;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: Consolas, Monaco, monospace;
                font-size: 12px;
            }
            .wprh-shortcode-disabled {
                opacity: 0.6;
            }
            .wprh-shortcode-disabled .wprh-shortcode-badge {
                display: inline-block;
                background: #d63638;
                color: #fff;
                font-size: 11px;
                padding: 2px 8px;
                border-radius: 3px;
                margin-left: 8px;
                vertical-align: middle;
            }
        </style>

        <div class="wprh-shortcodes-ref">
            <h2><?php esc_html_e('Available Shortcodes', 'wp-resource-hub'); ?></h2>
            <p><?php esc_html_e('Copy and paste these shortcodes into any page, post, or widget to display your resources.', 'wp-resource-hub'); ?></p>

            <!-- Resources Grid -->
            <div class="wprh-shortcode-card">
                <h3><?php esc_html_e('Resources Grid / List', 'wp-resource-hub'); ?></h3>
                <p><?php esc_html_e('Displays a filterable grid or list of resources.', 'wp-resource-hub'); ?></p>
                <div class="wprh-shortcode-tag">[resources]</div>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Attribute', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Default', 'wp-resource-hub'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>type</code></td>
                            <td><?php esc_html_e('Filter by resource type slug (e.g., video, pdf, download)', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('All types', 'wp-resource-hub'); ?></em></td>
                        </tr>
                        <tr>
                            <td><code>topic</code></td>
                            <td><?php esc_html_e('Filter by topic slug', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('All topics', 'wp-resource-hub'); ?></em></td>
                        </tr>
                        <tr>
                            <td><code>audience</code></td>
                            <td><?php esc_html_e('Filter by audience slug', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('All audiences', 'wp-resource-hub'); ?></em></td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td><?php esc_html_e('Display layout: grid or list', 'wp-resource-hub'); ?></td>
                            <td><code>grid</code></td>
                        </tr>
                        <tr>
                            <td><code>per_page</code></td>
                            <td><?php esc_html_e('Number of resources per page', 'wp-resource-hub'); ?></td>
                            <td><code>12</code></td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td><?php esc_html_e('Sort by: date, title, modified, menu_order', 'wp-resource-hub'); ?></td>
                            <td><code>date</code></td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td><?php esc_html_e('Sort direction: ASC or DESC', 'wp-resource-hub'); ?></td>
                            <td><code>DESC</code></td>
                        </tr>
                        <tr>
                            <td><code>show_filters</code></td>
                            <td><?php esc_html_e('Show filter bar: true or false', 'wp-resource-hub'); ?></td>
                            <td><code>true</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php esc_html_e('Additional CSS class', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('None', 'wp-resource-hub'); ?></em></td>
                        </tr>
                    </tbody>
                </table>
                <p><strong><?php esc_html_e('Examples:', 'wp-resource-hub'); ?></strong></p>
                <div class="wprh-shortcode-tag">[resources type="video" layout="list" per_page="6"]</div>
                <div class="wprh-shortcode-tag">[resources topic="marketing" audience="beginners"]</div>
            </div>

            <!-- Single Resource -->
            <div class="wprh-shortcode-card">
                <h3><?php esc_html_e('Single Resource', 'wp-resource-hub'); ?></h3>
                <p><?php esc_html_e('Embeds a single resource inline.', 'wp-resource-hub'); ?></p>
                <div class="wprh-shortcode-tag">[resource id="123"]</div>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Attribute', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Default', 'wp-resource-hub'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td><?php esc_html_e('Resource post ID (required)', 'wp-resource-hub'); ?></td>
                            <td>&mdash;</td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php esc_html_e('Additional CSS class', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('None', 'wp-resource-hub'); ?></em></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Collection -->
            <div class="wprh-shortcode-card">
                <h3><?php esc_html_e('Collection', 'wp-resource-hub'); ?></h3>
                <p><?php esc_html_e('Displays a resource collection with its chosen layout style.', 'wp-resource-hub'); ?></p>
                <div class="wprh-shortcode-tag">[resource_collection id="456"]</div>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Attribute', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Default', 'wp-resource-hub'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td><?php esc_html_e('Collection post ID', 'wp-resource-hub'); ?></td>
                            <td>&mdash;</td>
                        </tr>
                        <tr>
                            <td><code>slug</code></td>
                            <td><?php esc_html_e('Collection slug (alternative to ID)', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('None', 'wp-resource-hub'); ?></em></td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td><?php esc_html_e('Override layout: list, grid, playlist, nested-accordion', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('Collection setting', 'wp-resource-hub'); ?></em></td>
                        </tr>
                        <tr>
                            <td><code>show_title</code></td>
                            <td><?php esc_html_e('Show collection title: true or false', 'wp-resource-hub'); ?></td>
                            <td><code>true</code></td>
                        </tr>
                        <tr>
                            <td><code>show_description</code></td>
                            <td><?php esc_html_e('Show collection description: true or false', 'wp-resource-hub'); ?></td>
                            <td><code>true</code></td>
                        </tr>
                        <tr>
                            <td><code>show_count</code></td>
                            <td><?php esc_html_e('Show resource count: true or false', 'wp-resource-hub'); ?></td>
                            <td><code>true</code></td>
                        </tr>
                        <tr>
                            <td><code>show_progress</code></td>
                            <td><?php esc_html_e('Show progress bar: true or false', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('Collection setting', 'wp-resource-hub'); ?></em></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php esc_html_e('Additional CSS class', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('None', 'wp-resource-hub'); ?></em></td>
                        </tr>
                    </tbody>
                </table>
                <p><strong><?php esc_html_e('Examples:', 'wp-resource-hub'); ?></strong></p>
                <div class="wprh-shortcode-tag">[resource_collection id="456" layout="nested-accordion"]</div>
                <div class="wprh-shortcode-tag">[resource_collection slug="getting-started" show_progress="true"]</div>
            </div>

            <!-- Accordion -->
            <div class="wprh-shortcode-card <?php echo ! $accordions_enabled ? 'wprh-shortcode-disabled' : ''; ?>">
                <h3>
                    <?php esc_html_e('Accordion Builder', 'wp-resource-hub'); ?>
                    <?php if (! $accordions_enabled) : ?>
                        <span class="wprh-shortcode-badge"><?php esc_html_e('Disabled', 'wp-resource-hub'); ?></span>
                    <?php endif; ?>
                </h3>
                <p><?php esc_html_e('Displays a custom-built accordion structure with nested sections and resources.', 'wp-resource-hub'); ?></p>
                <?php if (! $accordions_enabled) : ?>
                    <p><em><?php esc_html_e('Enable the Accordion Builder in the General tab to use this shortcode.', 'wp-resource-hub'); ?></em></p>
                <?php endif; ?>
                <div class="wprh-shortcode-tag">[wprh_accordion id="789"]</div>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Attribute', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-resource-hub'); ?></th>
                            <th><?php esc_html_e('Default', 'wp-resource-hub'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td><?php esc_html_e('Accordion post ID (required)', 'wp-resource-hub'); ?></td>
                            <td>&mdash;</td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php esc_html_e('Additional CSS class', 'wp-resource-hub'); ?></td>
                            <td><em><?php esc_html_e('None', 'wp-resource-hub'); ?></em></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php
            /**
             * Fires after shortcode reference cards are rendered.
             *
             * @since 1.3.0
             */
            do_action('wprh_shortcodes_reference');
            ?>

            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                <strong><?php esc_html_e('Finding Post IDs', 'wp-resource-hub'); ?></strong><br>
                <?php esc_html_e('To find a resource, collection, or accordion ID: edit the item and look at the number in the browser URL bar after "post=". For example, in "post.php?post=123&action=edit", the ID is 123.', 'wp-resource-hub'); ?>
            </div>
        </div>
    <?php
    }

    /**
     * Render walkthrough content.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_walkthrough_content()
    {
    ?>
        <style>
            .wprh-walkthrough {
                max-width: 900px;
                margin: 20px 0;
            }

            .wprh-walkthrough h2 {
                color: #1d2327;
                font-size: 24px;
                margin-top: 30px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }

            .wprh-walkthrough h3 {
                color: #2271b1;
                font-size: 18px;
                margin-top: 20px;
            }

            .wprh-walkthrough .step-number {
                display: inline-block;
                background: #2271b1;
                color: white;
                width: 30px;
                height: 30px;
                line-height: 30px;
                text-align: center;
                border-radius: 50%;
                margin-right: 10px;
                font-weight: bold;
            }

            .wprh-walkthrough .info-box {
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                padding: 15px;
                margin: 15px 0;
            }

            .wprh-walkthrough .warning-box {
                background: #fcf9e8;
                border-left: 4px solid #dba617;
                padding: 15px;
                margin: 15px 0;
            }

            .wprh-walkthrough .success-box {
                background: #f0f9f4;
                border-left: 4px solid #00a32a;
                padding: 15px;
                margin: 15px 0;
            }

            .wprh-walkthrough code {
                background: #f6f7f7;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: Consolas, Monaco, monospace;
            }

            .wprh-walkthrough ul,
            .wprh-walkthrough ol {
                margin-left: 20px;
            }

            .wprh-walkthrough li {
                margin: 8px 0;
                line-height: 1.6;
            }
        </style>

        <div class="wprh-walkthrough">
            <h2>📚 Welcome to Resource Hub - Complete Walkthrough</h2>
            <p>This guide will walk you through everything you need to know about using the Resource Hub plugin, explained in
                simple, everyday language. No technical jargon - just clear, step-by-step instructions!</p>

            <div class="info-box">
                <strong>💡 What is Resource Hub?</strong><br>
                Think of Resource Hub as your digital filing cabinet for organizing and sharing different types of content with
                your website visitors. You can store videos, PDF documents, downloadable files, links to other websites, and
                even written articles - all in one organized place!
            </div>

            <h2>🎯 Part 1: Understanding the Basics</h2>

            <h3>What Are "Resources"?</h3>
            <p>Resources are pieces of content you want to share with your visitors. The plugin supports 5 different types:</p>
            <ul>
                <li><strong>Video</strong> - Embed videos from YouTube or Vimeo directly on your site</li>
                <li><strong>PDF</strong> - Share PDF documents that visitors can view or download</li>
                <li><strong>Download</strong> - Offer files for visitors to download (like worksheets, templates, or software)
                </li>
                <li><strong>External Link</strong> - Point visitors to other websites or web pages</li>
                <li><strong>Internal Content</strong> - Create article-style content hosted on your site</li>
            </ul>

            <h3>What Are "Collections"?</h3>
            <p>Collections are groups of related resources. Think of them like playlists - you can bundle multiple resources
                together to create a course, a thematic collection, or any logical grouping that makes sense for your content.
            </p>

            <h2>🚀 Part 2: Creating Your First Resource</h2>

            <h3><span class="step-number">1</span> Navigate to Resources</h3>
            <p>In your WordPress dashboard (the admin area), look at the left sidebar. You'll see a menu item called
                <strong>"Resources Hub"</strong> with a welcome icon. Hover over it and click <strong>"Add New"</strong>.
            </p>

            <h3><span class="step-number">2</span> Give Your Resource a Title</h3>
            <p>At the top of the page, you'll see a field that says "Add title". Type in a clear, descriptive name for your
                resource. For example: "How to Bake Chocolate Chip Cookies" or "2024 Marketing Strategy Template".</p>

            <h3><span class="step-number">3</span> Choose the Resource Type</h3>
            <p>On the right side, you'll see a box labeled <strong>"Resource Type"</strong>. This is where you tell the plugin
                what kind of content you're adding. Click to select one:</p>
            <ul>
                <li><strong>Video</strong> - If you're sharing a YouTube or Vimeo video</li>
                <li><strong>PDF</strong> - If you have a PDF document</li>
                <li><strong>Download</strong> - If you have a file people can download</li>
                <li><strong>External Link</strong> - If you're pointing to another website</li>
                <li><strong>Internal Content</strong> - If you're writing an article</li>
            </ul>

            <div class="info-box">
                <strong>💡 What happens when you select a type?</strong><br>
                The page will automatically update to show you relevant fields! For example, if you choose "Video", you'll see a
                field to paste your YouTube or Vimeo link. If you choose "PDF", you'll see an upload button.
            </div>

            <h3><span class="step-number">4</span> Fill in the Type-Specific Fields</h3>

            <h4>If You Chose VIDEO:</h4>
            <ul>
                <li>Find the <strong>"Video URL"</strong> field</li>
                <li>Go to YouTube or Vimeo, copy the video URL from your browser's address bar</li>
                <li>Paste it into the "Video URL" field</li>
                <li>The plugin automatically figures out if it's YouTube or Vimeo!</li>
            </ul>

            <h4>If You Chose PDF:</h4>
            <ul>
                <li>Click the <strong>"Choose PDF File"</strong> button</li>
                <li>Either upload a new PDF or select one from your media library</li>
                <li>Check the box if you want to allow visitors to download the PDF (otherwise they can only view it)</li>
            </ul>

            <h4>If You Chose DOWNLOAD:</h4>
            <ul>
                <li>Click the <strong>"Choose Download File"</strong> button</li>
                <li>Upload any type of file you want people to download</li>
                <li>Optionally customize the download button text (like "Get Your Free Template")</li>
            </ul>

            <h4>If You Chose EXTERNAL LINK:</h4>
            <ul>
                <li>Paste the website URL into the <strong>"External URL"</strong> field</li>
                <li>Check the box if you want the link to open in a new browser tab</li>
            </ul>

            <h4>If You Chose INTERNAL CONTENT:</h4>
            <ul>
                <li>Use the big text editor (just like writing a blog post) to create your content</li>
                <li>The plugin will automatically calculate reading time</li>
                <li>It can also create a Table of Contents from your headings!</li>
            </ul>

            <h3><span class="step-number">5</span> Add a Description (Optional but Recommended)</h3>
            <p>Scroll down to find the main text editor. This is where you can add a description, introduction, or any
                additional information about your resource. This helps visitors understand what they're about to see!</p>

            <h3><span class="step-number">6</span> Categorize Your Resource</h3>
            <p>Look at the right sidebar for these helpful organizers:</p>
            <ul>
                <li><strong>Topic</strong> - What subject is this about? (like "Marketing", "Education", "Health")</li>
                <li><strong>Audience</strong> - Who is this for? (like "Beginners", "Advanced", "Teachers")</li>
            </ul>
            <p>Click to add or create new topics and audiences. These help visitors find resources later!</p>

            <h3><span class="step-number">7</span> Set Who Can Access This Resource</h3>
            <p>Scroll down to find the <strong>"Access Control"</strong> box. Choose one:</p>
            <ul>
                <li><strong>Public</strong> - Anyone can see this resource, even if they're not logged in</li>
                <li><strong>Logged-in Users</strong> - Only people with accounts on your site can see it</li>
                <li><strong>Specific Roles</strong> - Only certain types of users (like subscribers, members, etc.)</li>
            </ul>

            <div class="warning-box">
                <strong>⚠️ Important about Access Control:</strong><br>
                If you choose "Logged-in Users" or "Specific Roles", visitors who don't meet the requirements will see a message
                that they need to log in or don't have permission. Make sure this matches your intentions!
            </div>

            <h3><span class="step-number">8</span> Add a Featured Image (Highly Recommended)</h3>
            <p>In the right sidebar, click <strong>"Set featured image"</strong>. This image appears as a thumbnail when listing
                your resources. Choose an eye-catching image that represents your content!</p>

            <h3><span class="step-number">9</span> Publish Your Resource</h3>
            <p>When you're happy with everything, click the big blue <strong>"Publish"</strong> button in the top right. Your
                resource is now live!</p>

            <div class="success-box">
                <strong>🎉 Congratulations!</strong> You've created your first resource! Visitors can now find and interact with
                it on your website.
            </div>

            <h2>📦 Part 3: Creating Collections</h2>

            <h3><span class="step-number">1</span> Create a New Collection</h3>
            <p>Hover over <strong>"Resources Hub"</strong> in the left sidebar and click <strong>"Collections"</strong>, then
                click <strong>"Add New"</strong>.</p>

            <h3><span class="step-number">2</span> Name Your Collection</h3>
            <p>Give it a descriptive name like "Beginner's Marketing Course" or "Cookie Baking Masterclass".</p>

            <h3><span class="step-number">3</span> Add Resources to Your Collection</h3>
            <p>Scroll down to the <strong>"Collection Resources"</strong> box. You'll see a search field:</p>
            <ul>
                <li>Type the name of a resource you've already created</li>
                <li>Click on it when it appears in the search results</li>
                <li>It will be added to your collection!</li>
                <li>Repeat to add more resources</li>
            </ul>

            <div class="info-box">
                <strong>💡 Pro Tip:</strong> You can drag and drop resources to reorder them! The order matters - it's how
                they'll appear to visitors.
            </div>

            <h3><span class="step-number">4</span> Choose a Layout</h3>
            <p>In the <strong>"Collection Settings"</strong> box, choose how you want resources displayed:</p>
            <ul>
                <li><strong>Grid</strong> - Shows resources as cards in a grid (like Instagram)</li>
                <li><strong>List</strong> - Shows resources as a vertical list (like a traditional list)</li>
            </ul>

            <h3><span class="step-number">5</span> Publish Your Collection</h3>
            <p>Click <strong>"Publish"</strong> to make your collection live!</p>

            <h2>🎨 Part 4: Displaying Resources on Your Site</h2>

            <h3>Method 1: Automatic Archive Page</h3>
            <p>WordPress automatically creates a page that lists all your resources at:</p>
            <code>yourwebsite.com/resource/</code>
            <p>Visitors can click on any resource to view its full details.</p>

            <h3>Method 2: Using Shortcodes (Copy & Paste Method)</h3>
            <p>Shortcodes are special tags you can paste into any page or post to display resources. Here are the three main
                ones:</p>

            <h4>Show Multiple Resources:</h4>
            <code>[resources]</code>
            <p>Paste this anywhere to display a grid of all your resources. Visitors can filter by type, topic, and audience!
            </p>

            <h4>Show a Single Resource:</h4>
            <code>[resource id="123"]</code>
            <p>Replace 123 with your resource ID number (you can find this in the URL when editing a resource).</p>

            <h4>Show a Collection:</h4>
            <code>[collection id="456"]</code>
            <p>Replace 456 with your collection ID number.</p>

            <div class="info-box">
                <strong>💡 Where do I paste shortcodes?</strong><br>
                Edit any page or post, and paste the shortcode where you want the resources to appear. That's it! The plugin
                handles the rest.
            </div>

            <h3>Method 3: Using Blocks (Visual Method)</h3>
            <p>If you use the WordPress block editor:</p>
            <ol>
                <li>Edit a page or post</li>
                <li>Click the <strong>+</strong> button to add a new block</li>
                <li>Search for "Resource" or "Collection"</li>
                <li>You'll see: <strong>Resources Grid</strong>, <strong>Single Resource</strong>, and
                    <strong>Collection</strong> blocks
                </li>
                <li>Choose one and configure it visually - no code needed!</li>
            </ol>

            <h2>📊 Part 5: Understanding Statistics</h2>

            <h3>What Gets Tracked?</h3>
            <p>The plugin automatically tracks:</p>
            <ul>
                <li>How many times each resource has been viewed</li>
                <li>How many times files have been downloaded</li>
            </ul>

            <h3>Where to See Statistics</h3>
            <p>Go to <strong>Resources Hub > All Resources</strong>. You'll see view and download counts in the list!</p>

            <div class="info-box">
                <strong>💡 Why are statistics useful?</strong><br>
                They help you understand what content your visitors find most valuable, so you can create more of what works!
            </div>

            <h2>🔧 Part 6: Managing Resources</h2>

            <h3>Editing Resources</h3>
            <p>Go to <strong>Resources Hub > All Resources</strong>, hover over any resource, and click <strong>"Edit"</strong>.
                Make your changes and click <strong>"Update"</strong>.</p>

            <h3>Bulk Actions (Working with Multiple Resources at Once)</h3>
            <p>Check the boxes next to multiple resources, then use the "Bulk Actions" dropdown to:</p>
            <ul>
                <li>Mark multiple resources as featured</li>
                <li>Change the type of multiple resources</li>
                <li>Add multiple resources to a collection</li>
            </ul>

            <h3>Filtering and Finding Resources</h3>
            <p>At the top of the All Resources page, use the dropdown filters to show only:</p>
            <ul>
                <li>Specific resource types (only videos, only PDFs, etc.)</li>
                <li>Specific topics</li>
                <li>Specific audiences</li>
            </ul>
            <p>This makes managing large libraries much easier!</p>

            <h2>💾 Part 7: Import & Export</h2>

            <h3>Exporting Resources (Backing Up or Moving)</h3>
            <ol>
                <li>Go to <strong>Resources Hub > Import/Export</strong></li>
                <li>Choose export format:
                    <ul>
                        <li><strong>CSV</strong> - Opens in Excel, good for simple lists</li>
                        <li><strong>JSON</strong> - Complete backup with all details</li>
                    </ul>
                </li>
                <li>Click <strong>"Export Resources"</strong></li>
                <li>A file will download to your computer</li>
            </ol>

            <h3>Importing Resources (Adding in Bulk)</h3>
            <ol>
                <li>Go to <strong>Resources Hub > Import/Export</strong></li>
                <li>Click <strong>"Choose File"</strong> and select your CSV or JSON file</li>
                <li>Click <strong>"Import Resources"</strong></li>
                <li>The plugin will show you how many resources were successfully imported</li>
            </ol>

            <div class="warning-box">
                <strong>⚠️ Tip:</strong> Always export before making big changes or updates to your site. It's like having a
                safety net!
            </div>

            <h2>🎓 Part 8: Advanced Tips & Tricks</h2>

            <h3>Creating a Members-Only Resource Library</h3>
            <ol>
                <li>Create resources with access level set to "Logged-in Users"</li>
                <li>Use a membership plugin (like MemberPress or Restrict Content Pro)</li>
                <li>Your resource library becomes exclusive content!</li>
            </ol>

            <h3>Building an Online Course</h3>
            <ol>
                <li>Create individual resources for each lesson (videos, PDFs, etc.)</li>
                <li>Group them into collections by module or week</li>
                <li>Set access levels to control who can see what</li>
                <li>Display collections on pages using shortcodes or blocks</li>
            </ol>

            <h3>Creating a Resource Center for Your Team</h3>
            <ol>
                <li>Create resources for training materials, templates, and guides</li>
                <li>Use topics to organize by department</li>
                <li>Use access control to show resources only to logged-in team members</li>
                <li>Track statistics to see what's being used most</li>
            </ol>

            <h2>❓ Part 9: Common Questions</h2>

            <h3>Can I change a resource's type after creating it?</h3>
            <p>Yes! Edit the resource, change the type in the Resource Type box, fill in the new type-specific fields, and
                update.</p>

            <h3>Can a resource be in multiple collections?</h3>
            <p>Yes! The same resource can appear in as many collections as you want.</p>

            <h3>What happens if I delete a resource that's in a collection?</h3>
            <p>It will be removed from all collections automatically. The collections themselves remain intact.</p>

            <h3>Can visitors search for resources?</h3>
            <p>Yes! The [resources] shortcode includes built-in filters, and visitors can use your site's search function.</p>

            <h3>How do I customize the look of resources?</h3>
            <p>The plugin uses your theme's styling automatically. For advanced customization, you can override the templates in
                your theme folder (ask your developer for help with this).</p>

            <h2>🆘 Part 10: Getting Help</h2>

            <div class="success-box">
                <strong>Need more help?</strong><br>
                <ul>
                    <li>Check the plugin's documentation (if available)</li>
                    <li>Contact your site administrator</li>
                    <li>Post in WordPress support forums</li>
                </ul>
            </div>

            <h2>🎉 You're All Set!</h2>
            <p>You now know everything you need to create, organize, and display resources on your WordPress site. Start small -
                create one or two resources, see how they look, and build from there. Before you know it, you'll have a full
                resource library that your visitors love!</p>

            <div class="info-box">
                <strong>Remember:</strong> Don't be afraid to experiment! You can always edit or delete resources. The best way
                to learn is by doing. Have fun building your resource library! 🚀
            </div>
        </div>
    <?php
    }

    /**
     * Render video lightbox field.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_video_lightbox_field()
    {
        $settings = get_option('wprh_frontend_settings', $this->get_frontend_defaults());
    ?>
        <label>
            <input type="checkbox" name="wprh_frontend_settings[video_lightbox_only]" value="1"
                <?php checked(! empty($settings['video_lightbox_only'])); ?>>
            <?php esc_html_e('Open videos in lightbox player (no single page)', 'wp-resource-hub'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, clicking on video cards opens a lightbox player instead of going to the single resource page. Recommended for video-focused sites.', 'wp-resource-hub'); ?>
        </p>
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
    public static function get_setting($group, $key, $default = null)
    {
        $option_name = 'wprh_' . $group . '_settings';
        $settings    = get_option($option_name, array());

        if (isset($settings[$key])) {
            return $settings[$key];
        }

        return $default;
    }
}
