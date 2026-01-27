<?php
/**
 * Collection Meta Boxes class.
 *
 * Handles meta boxes for the Collection post type, including resource selection.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\CollectionPostType;
use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Collection Meta Boxes class.
 *
 * @since 1.1.0
 */
class CollectionMetaBoxes {

    /**
     * Singleton instance.
     *
     * @var CollectionMetaBoxes|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return CollectionMetaBoxes
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
     * @since 1.1.0
     */
    private function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_' . CollectionPostType::get_post_type(), array( $this, 'save_meta' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wprh_search_resources', array( $this, 'ajax_search_resources' ) );
    }

    /**
     * Register meta boxes.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register_meta_boxes() {
        add_meta_box(
            'wprh_collection_resources',
            __( 'Collection Resources', 'wp-resource-hub' ),
            array( $this, 'render_resources_meta_box' ),
            CollectionPostType::get_post_type(),
            'normal',
            'high'
        );

        add_meta_box(
            'wprh_collection_settings',
            __( 'Collection Settings', 'wp-resource-hub' ),
            array( $this, 'render_settings_meta_box' ),
            CollectionPostType::get_post_type(),
            'side',
            'default'
        );
    }

    /**
     * Render the resources meta box.
     *
     * @since 1.1.0
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_resources_meta_box( $post ) {
        wp_nonce_field( 'wprh_save_collection_meta', 'wprh_collection_meta_nonce' );

        $resource_ids = CollectionPostType::get_collection_resources( $post->ID );
        ?>
        <div class="wprh-collection-resources-wrapper">
            <div class="wprh-collection-search">
                <input type="text"
                       id="wprh-resource-search"
                       class="widefat"
                       placeholder="<?php esc_attr_e( 'Search for resources to add...', 'wp-resource-hub' ); ?>">
                <div id="wprh-search-results" class="wprh-search-results" style="display: none;"></div>
            </div>

            <div class="wprh-collection-resources-list" id="wprh-collection-resources">
                <?php if ( empty( $resource_ids ) ) : ?>
                    <p class="wprh-no-resources-message">
                        <?php esc_html_e( 'No resources added yet. Use the search above to find and add resources.', 'wp-resource-hub' ); ?>
                    </p>
                <?php else : ?>
                    <?php foreach ( $resource_ids as $resource_id ) : ?>
                        <?php
                        $resource = get_post( $resource_id );
                        if ( ! $resource ) {
                            continue;
                        }
                        $type      = ResourceTypeTax::get_resource_type( $resource_id );
                        $type_icon = $type ? ResourceTypeTax::get_type_icon( $type ) : 'dashicons-media-default';
                        ?>
                        <div class="wprh-collection-resource-item" data-resource-id="<?php echo esc_attr( $resource_id ); ?>">
                            <span class="wprh-resource-drag-handle dashicons dashicons-menu"></span>
                            <span class="wprh-resource-type-icon dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                            <span class="wprh-resource-title"><?php echo esc_html( $resource->post_title ); ?></span>
                            <?php if ( $type ) : ?>
                                <span class="wprh-resource-type-label"><?php echo esc_html( $type->name ); ?></span>
                            <?php endif; ?>
                            <button type="button" class="wprh-remove-resource button-link" data-resource-id="<?php echo esc_attr( $resource_id ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                                <span class="screen-reader-text"><?php esc_html_e( 'Remove', 'wp-resource-hub' ); ?></span>
                            </button>
                            <input type="hidden" name="wprh_collection_resources[]" value="<?php echo esc_attr( $resource_id ); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p class="description">
                <?php esc_html_e( 'Drag and drop to reorder resources. The order here determines the display order in the collection.', 'wp-resource-hub' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the settings meta box.
     *
     * @since 1.1.0
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_settings_meta_box( $post ) {
        $display_style = get_post_meta( $post->ID, '_wprh_collection_display_style', true ) ?: 'list';
        $show_progress = get_post_meta( $post->ID, '_wprh_collection_show_progress', true );
        $auto_advance  = get_post_meta( $post->ID, '_wprh_collection_auto_advance', true );
        ?>
        <div class="wprh-collection-settings">
            <p>
                <label for="wprh_collection_display_style">
                    <strong><?php esc_html_e( 'Display Style', 'wp-resource-hub' ); ?></strong>
                </label>
                <select name="wprh_collection_display_style" id="wprh_collection_display_style" class="widefat">
                    <option value="list" <?php selected( $display_style, 'list' ); ?>><?php esc_html_e( 'List', 'wp-resource-hub' ); ?></option>
                    <option value="grid" <?php selected( $display_style, 'grid' ); ?>><?php esc_html_e( 'Grid', 'wp-resource-hub' ); ?></option>
                    <option value="playlist" <?php selected( $display_style, 'playlist' ); ?>><?php esc_html_e( 'Playlist (sidebar)', 'wp-resource-hub' ); ?></option>
                </select>
            </p>

            <p>
                <label>
                    <input type="checkbox"
                           name="wprh_collection_show_progress"
                           value="1"
                           <?php checked( $show_progress, '1' ); ?>>
                    <?php esc_html_e( 'Show progress tracking', 'wp-resource-hub' ); ?>
                </label>
            </p>

            <p>
                <label>
                    <input type="checkbox"
                           name="wprh_collection_auto_advance"
                           value="1"
                           <?php checked( $auto_advance, '1' ); ?>>
                    <?php esc_html_e( 'Auto-advance to next resource', 'wp-resource-hub' ); ?>
                </label>
            </p>

            <?php
            /**
             * Fires after collection settings fields.
             *
             * @since 1.1.0
             *
             * @param \WP_Post $post Current post object.
             */
            do_action( 'wprh_collection_settings_fields', $post );
            ?>
        </div>
        <?php
    }

    /**
     * Save meta data.
     *
     * @since 1.1.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an update.
     * @return void
     */
    public function save_meta( $post_id, $post, $update ) {
        // Verify nonce.
        if ( ! isset( $_POST['wprh_collection_meta_nonce'] ) ||
             ! wp_verify_nonce( $_POST['wprh_collection_meta_nonce'], 'wprh_save_collection_meta' ) ) {
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

        // Save resources.
        if ( isset( $_POST['wprh_collection_resources'] ) && is_array( $_POST['wprh_collection_resources'] ) ) {
            $resource_ids = array_map( 'absint', $_POST['wprh_collection_resources'] );
            CollectionPostType::set_collection_resources( $post_id, $resource_ids );
        } else {
            CollectionPostType::set_collection_resources( $post_id, array() );
        }

        // Save display style.
        if ( isset( $_POST['wprh_collection_display_style'] ) ) {
            $valid_styles = array( 'list', 'grid', 'playlist' );
            $display_style = sanitize_text_field( $_POST['wprh_collection_display_style'] );
            if ( in_array( $display_style, $valid_styles, true ) ) {
                update_post_meta( $post_id, '_wprh_collection_display_style', $display_style );
            }
        }

        // Save show progress.
        $show_progress = isset( $_POST['wprh_collection_show_progress'] ) ? '1' : '0';
        update_post_meta( $post_id, '_wprh_collection_show_progress', $show_progress );

        // Save auto advance.
        $auto_advance = isset( $_POST['wprh_collection_auto_advance'] ) ? '1' : '0';
        update_post_meta( $post_id, '_wprh_collection_auto_advance', $auto_advance );

        /**
         * Fires after collection meta is saved.
         *
         * @since 1.1.0
         *
         * @param int      $post_id Post ID.
         * @param \WP_Post $post    Post object.
         */
        do_action( 'wprh_save_collection_meta', $post_id, $post );
    }

    /**
     * AJAX handler for searching resources.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function ajax_search_resources() {
        check_ajax_referer( 'wprh_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-resource-hub' ) ) );
        }

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $exclude = isset( $_POST['exclude'] ) ? array_map( 'absint', (array) $_POST['exclude'] ) : array();

        $args = array(
            'post_type'      => ResourcePostType::get_post_type(),
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            's'              => $search,
            'post__not_in'   => $exclude,
        );

        $query = new \WP_Query( $args );
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
     * @since 1.1.0
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        global $post_type;

        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        if ( CollectionPostType::get_post_type() !== $post_type ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_enqueue_style(
            'wprh-collection-admin',
            WPRH_PLUGIN_URL . 'assets/css/collection-admin.css',
            array(),
            WPRH_VERSION
        );

        wp_enqueue_script(
            'wprh-collection-admin',
            WPRH_PLUGIN_URL . 'assets/js/collection-admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            WPRH_VERSION,
            true
        );

        wp_localize_script(
            'wprh-collection-admin',
            'wprhCollection',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'wprh_admin_nonce' ),
                'i18n'      => array(
                    'noResults'    => __( 'No resources found.', 'wp-resource-hub' ),
                    'searching'    => __( 'Searching...', 'wp-resource-hub' ),
                    'removeConfirm' => __( 'Remove this resource from the collection?', 'wp-resource-hub' ),
                ),
            )
        );
    }
}
