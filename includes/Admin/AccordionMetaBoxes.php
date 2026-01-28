<?php
/**
 * Accordion Meta Boxes class.
 *
 * Handles the accordion builder meta box for creating nested accordion structures.
 *
 * @package WPResourceHub
 * @since   1.3.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\AccordionPostType;
use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Accordion Meta Boxes class.
 *
 * @since 1.3.0
 */
class AccordionMetaBoxes {

    /**
     * Singleton instance.
     *
     * @var AccordionMetaBoxes|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.3.0
     *
     * @return AccordionMetaBoxes
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
     * @since 1.3.0
     */
    private function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_' . AccordionPostType::get_post_type(), array( $this, 'save_meta' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wprh_search_accordion_resources', array( $this, 'ajax_search_resources' ) );
    }

    /**
     * Register meta boxes.
     *
     * @since 1.3.0
     *
     * @return void
     */
    public function register_meta_boxes() {
        add_meta_box(
            'wprh_accordion_builder',
            __( 'Accordion Builder', 'wp-resource-hub' ),
            array( $this, 'render_builder_meta_box' ),
            AccordionPostType::get_post_type(),
            'normal',
            'high'
        );
    }

    /**
     * Render the accordion builder meta box.
     *
     * @since 1.3.0
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_builder_meta_box( $post ) {
        wp_nonce_field( 'wprh_save_accordion_meta', 'wprh_accordion_meta_nonce' );

        $structure = AccordionPostType::get_accordion_structure( $post->ID );
        ?>
        <div class="wprh-accordion-builder-wrapper">
            <div class="wprh-accordion-builder-toolbar">
                <button type="button" class="button wprh-add-heading" data-type="heading">
                    <span class="dashicons dashicons-heading"></span>
                    <?php esc_html_e( 'Add Heading', 'wp-resource-hub' ); ?>
                </button>
                <button type="button" class="button wprh-add-resource-btn" data-type="resource">
                    <span class="dashicons dashicons-media-default"></span>
                    <?php esc_html_e( 'Add Resource', 'wp-resource-hub' ); ?>
                </button>
                <button type="button" class="button wprh-add-nested" data-type="accordion">
                    <span class="dashicons dashicons-editor-justify"></span>
                    <?php esc_html_e( 'Add Nested Accordion', 'wp-resource-hub' ); ?>
                </button>
            </div>

            <!-- Resource Search Modal -->
            <div class="wprh-accordion-resource-search" id="wprh-accordion-resource-search" style="display: none;">
                <div class="wprh-accordion-search-inner">
                    <input type="text" id="wprh-accordion-search-input" class="widefat"
                        placeholder="<?php esc_attr_e( 'Search for resources...', 'wp-resource-hub' ); ?>">
                    <div id="wprh-accordion-search-results" class="wprh-search-results" style="display: none;"></div>
                    <button type="button" class="button wprh-accordion-search-cancel">
                        <?php esc_html_e( 'Cancel', 'wp-resource-hub' ); ?>
                    </button>
                </div>
            </div>

            <div class="wprh-accordion-builder-items" id="wprh-accordion-builder-items">
                <?php if ( empty( $structure ) ) : ?>
                    <p class="wprh-no-items-message">
                        <?php esc_html_e( 'No items added yet. Use the buttons above to build your accordion structure.', 'wp-resource-hub' ); ?>
                    </p>
                <?php else : ?>
                    <?php $this->render_builder_items( $structure ); ?>
                <?php endif; ?>
            </div>

            <input type="hidden" name="wprh_accordion_structure" id="wprh-accordion-structure-data" value="<?php echo esc_attr( wp_json_encode( $structure ) ); ?>">

            <p class="description">
                <?php esc_html_e( 'Drag and drop to reorder items. Nested accordions can contain headings and resources.', 'wp-resource-hub' ); ?>
            </p>
        </div>
        <?php

        /**
         * Fires after the accordion builder meta box content.
         *
         * @since 1.3.0
         *
         * @param \WP_Post $post Current post object.
         */
        do_action( 'wprh_accordion_builder_meta_box', $post );
    }

    /**
     * Render builder items recursively.
     *
     * @since 1.3.0
     *
     * @param array $items Structure items.
     * @return void
     */
    private function render_builder_items( $items ) {
        foreach ( $items as $item ) {
            $this->render_builder_item( $item );
        }
    }

    /**
     * Render a single builder item.
     *
     * @since 1.3.0
     *
     * @param array $item Item data.
     * @return void
     */
    private function render_builder_item( $item ) {
        $type = isset( $item['type'] ) ? $item['type'] : '';
        $id   = isset( $item['id'] ) ? $item['id'] : wp_generate_uuid4();

        switch ( $type ) {
            case 'heading':
                $title = isset( $item['title'] ) ? $item['title'] : '';
                ?>
                <div class="wprh-builder-item wprh-builder-heading" data-type="heading" data-id="<?php echo esc_attr( $id ); ?>">
                    <span class="wprh-builder-drag-handle dashicons dashicons-menu"></span>
                    <span class="wprh-builder-item-icon dashicons dashicons-heading"></span>
                    <input type="text" class="wprh-builder-heading-input" value="<?php echo esc_attr( $title ); ?>"
                        placeholder="<?php esc_attr_e( 'Enter heading text...', 'wp-resource-hub' ); ?>">
                    <button type="button" class="wprh-builder-remove button-link">
                        <span class="dashicons dashicons-no-alt"></span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Remove', 'wp-resource-hub' ); ?></span>
                    </button>
                </div>
                <?php
                break;

            case 'resource':
                $resource_id = isset( $item['resource_id'] ) ? absint( $item['resource_id'] ) : 0;
                $resource    = get_post( $resource_id );
                if ( ! $resource ) {
                    return;
                }
                $type_term = ResourceTypeTax::get_resource_type( $resource_id );
                $type_icon = $type_term ? ResourceTypeTax::get_type_icon( $type_term ) : 'dashicons-media-default';
                ?>
                <div class="wprh-builder-item wprh-builder-resource" data-type="resource" data-id="<?php echo esc_attr( $id ); ?>" data-resource-id="<?php echo esc_attr( $resource_id ); ?>">
                    <span class="wprh-builder-drag-handle dashicons dashicons-menu"></span>
                    <span class="wprh-builder-item-icon dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                    <span class="wprh-builder-resource-title"><?php echo esc_html( $resource->post_title ); ?></span>
                    <?php if ( $type_term ) : ?>
                        <span class="wprh-builder-type-label"><?php echo esc_html( $type_term->name ); ?></span>
                    <?php endif; ?>
                    <button type="button" class="wprh-builder-remove button-link">
                        <span class="dashicons dashicons-no-alt"></span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Remove', 'wp-resource-hub' ); ?></span>
                    </button>
                </div>
                <?php
                break;

            case 'accordion':
                $title    = isset( $item['title'] ) ? $item['title'] : '';
                $children = isset( $item['children'] ) ? $item['children'] : array();
                ?>
                <div class="wprh-builder-item wprh-builder-accordion" data-type="accordion" data-id="<?php echo esc_attr( $id ); ?>">
                    <div class="wprh-builder-accordion-header">
                        <span class="wprh-builder-drag-handle dashicons dashicons-menu"></span>
                        <span class="wprh-builder-item-icon dashicons dashicons-editor-justify"></span>
                        <input type="text" class="wprh-builder-heading-input" value="<?php echo esc_attr( $title ); ?>"
                            placeholder="<?php esc_attr_e( 'Enter accordion title...', 'wp-resource-hub' ); ?>">
                        <button type="button" class="wprh-builder-toggle button-link" title="<?php esc_attr_e( 'Toggle', 'wp-resource-hub' ); ?>">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <button type="button" class="wprh-builder-remove button-link">
                            <span class="dashicons dashicons-no-alt"></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Remove', 'wp-resource-hub' ); ?></span>
                        </button>
                    </div>
                    <div class="wprh-builder-accordion-children">
                        <div class="wprh-builder-children-toolbar">
                            <button type="button" class="button button-small wprh-add-child-heading" data-type="heading">
                                <span class="dashicons dashicons-heading"></span>
                                <?php esc_html_e( 'Heading', 'wp-resource-hub' ); ?>
                            </button>
                            <button type="button" class="button button-small wprh-add-child-resource" data-type="resource">
                                <span class="dashicons dashicons-media-default"></span>
                                <?php esc_html_e( 'Resource', 'wp-resource-hub' ); ?>
                            </button>
                            <button type="button" class="button button-small wprh-add-child-accordion" data-type="accordion">
                                <span class="dashicons dashicons-editor-justify"></span>
                                <?php esc_html_e( 'Nested', 'wp-resource-hub' ); ?>
                            </button>
                        </div>
                        <div class="wprh-builder-children-list">
                            <?php if ( ! empty( $children ) ) : ?>
                                <?php $this->render_builder_items( $children ); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                break;
        }
    }

    /**
     * Save meta data.
     *
     * @since 1.3.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an update.
     * @return void
     */
    public function save_meta( $post_id, $post, $update ) {
        // Verify nonce.
        if (
            ! isset( $_POST['wprh_accordion_meta_nonce'] ) ||
            ! wp_verify_nonce( $_POST['wprh_accordion_meta_nonce'], 'wprh_save_accordion_meta' )
        ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save structure.
        if ( isset( $_POST['wprh_accordion_structure'] ) ) {
            $raw_structure = sanitize_text_field( wp_unslash( $_POST['wprh_accordion_structure'] ) );
            $structure     = json_decode( $raw_structure, true );

            if ( is_array( $structure ) ) {
                $structure = $this->sanitize_structure( $structure );
                AccordionPostType::set_accordion_structure( $post_id, $structure );
            } else {
                AccordionPostType::set_accordion_structure( $post_id, array() );
            }
        }

        /**
         * Fires after accordion meta is saved.
         *
         * @since 1.3.0
         *
         * @param int      $post_id Post ID.
         * @param \WP_Post $post    Post object.
         */
        do_action( 'wprh_save_accordion_meta', $post_id, $post );
    }

    /**
     * Sanitize the accordion structure recursively.
     *
     * @since 1.3.0
     *
     * @param array $items Structure items.
     * @return array
     */
    private function sanitize_structure( $items ) {
        $sanitized = array();

        foreach ( $items as $item ) {
            if ( ! isset( $item['type'] ) ) {
                continue;
            }

            $clean = array(
                'type' => sanitize_text_field( $item['type'] ),
                'id'   => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : wp_generate_uuid4(),
            );

            switch ( $clean['type'] ) {
                case 'heading':
                    $clean['title'] = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
                    break;

                case 'resource':
                    $clean['resource_id'] = isset( $item['resource_id'] ) ? absint( $item['resource_id'] ) : 0;
                    if ( ! $clean['resource_id'] ) {
                        continue 2;
                    }
                    break;

                case 'accordion':
                    $clean['title']    = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
                    $clean['children'] = isset( $item['children'] ) && is_array( $item['children'] )
                        ? $this->sanitize_structure( $item['children'] )
                        : array();
                    break;

                default:
                    /**
                     * Filter to allow sanitizing custom item types.
                     *
                     * @since 1.3.0
                     *
                     * @param array $clean The sanitized item.
                     * @param array $item  The raw item.
                     */
                    $clean = apply_filters( 'wprh_accordion_sanitize_item', $clean, $item );
                    break;
            }

            $sanitized[] = $clean;
        }

        return $sanitized;
    }

    /**
     * AJAX handler for searching resources.
     *
     * @since 1.3.0
     *
     * @return void
     */
    public function ajax_search_resources() {
        check_ajax_referer( 'wprh_accordion_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-resource-hub' ) ) );
        }

        $search  = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $exclude = isset( $_POST['exclude'] ) ? array_map( 'absint', (array) $_POST['exclude'] ) : array();

        $args = array(
            'post_type'      => ResourcePostType::get_post_type(),
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            's'              => $search,
            'post__not_in'   => $exclude,
        );

        $query   = new \WP_Query( $args );
        $results = array();

        foreach ( $query->posts as $post ) {
            $type      = ResourceTypeTax::get_resource_type( $post->ID );
            $type_icon = $type ? ResourceTypeTax::get_type_icon( $type ) : 'dashicons-media-default';

            $results[] = array(
                'id'        => $post->ID,
                'title'     => $post->post_title,
                'type'      => $type ? $type->name : '',
                'type_slug' => $type ? $type->slug : '',
                'type_icon' => $type_icon,
            );
        }

        wp_send_json_success( array( 'resources' => $results ) );
    }

    /**
     * Enqueue scripts and styles.
     *
     * @since 1.3.0
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        global $post_type;

        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        if ( AccordionPostType::get_post_type() !== $post_type ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_enqueue_style(
            'wprh-accordion-admin',
            WPRH_PLUGIN_URL . 'assets/css/accordion-admin.css',
            array(),
            WPRH_VERSION
        );

        wp_enqueue_script(
            'wprh-accordion-admin',
            WPRH_PLUGIN_URL . 'assets/js/accordion-admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            WPRH_VERSION,
            true
        );

        wp_localize_script(
            'wprh-accordion-admin',
            'wprhAccordion',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wprh_accordion_admin_nonce' ),
                'i18n'    => array(
                    'noResults'     => __( 'No resources found.', 'wp-resource-hub' ),
                    'searching'     => __( 'Searching...', 'wp-resource-hub' ),
                    'removeConfirm' => __( 'Remove this item?', 'wp-resource-hub' ),
                    'headingPlaceholder'   => __( 'Enter heading text...', 'wp-resource-hub' ),
                    'accordionPlaceholder' => __( 'Enter accordion title...', 'wp-resource-hub' ),
                ),
            )
        );
    }
}
