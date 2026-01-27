<?php
/**
 * Bulk Actions class.
 *
 * Handles bulk actions for resources in the list table.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bulk Actions class.
 *
 * @since 1.1.0
 */
class BulkActions {

    /**
     * Singleton instance.
     *
     * @var BulkActions|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return BulkActions
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
        add_filter( 'bulk_actions-edit-resource', array( $this, 'register_bulk_actions' ) );
        add_filter( 'handle_bulk_actions-edit-resource', array( $this, 'handle_bulk_actions' ), 10, 3 );
        add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
        add_action( 'admin_footer-edit.php', array( $this, 'bulk_action_modal' ) );
        add_action( 'wp_ajax_wprh_bulk_change_type', array( $this, 'ajax_bulk_change_type' ) );
        add_action( 'wp_ajax_wprh_bulk_add_to_collection', array( $this, 'ajax_bulk_add_to_collection' ) );
    }

    /**
     * Register custom bulk actions.
     *
     * @since 1.1.0
     *
     * @param array $actions Existing bulk actions.
     * @return array
     */
    public function register_bulk_actions( $actions ) {
        $actions['wprh_change_type']        = __( 'Change Type', 'wp-resource-hub' );
        $actions['wprh_set_featured']       = __( 'Set as Featured', 'wp-resource-hub' );
        $actions['wprh_unset_featured']     = __( 'Remove Featured', 'wp-resource-hub' );
        $actions['wprh_add_to_collection']  = __( 'Add to Collection', 'wp-resource-hub' );
        $actions['wprh_clear_stats']        = __( 'Clear Statistics', 'wp-resource-hub' );

        /**
         * Filter the resource bulk actions.
         *
         * @since 1.1.0
         *
         * @param array $actions Bulk actions.
         */
        return apply_filters( 'wprh_bulk_actions', $actions );
    }

    /**
     * Handle bulk actions.
     *
     * @since 1.1.0
     *
     * @param string $redirect_url Redirect URL.
     * @param string $action       Action being performed.
     * @param array  $post_ids     Array of post IDs.
     * @return string
     */
    public function handle_bulk_actions( $redirect_url, $action, $post_ids ) {
        $processed = 0;

        switch ( $action ) {
            case 'wprh_set_featured':
                foreach ( $post_ids as $post_id ) {
                    if ( $this->set_featured( $post_id, true ) ) {
                        $processed++;
                    }
                }
                $redirect_url = add_query_arg( 'wprh_featured_set', $processed, $redirect_url );
                break;

            case 'wprh_unset_featured':
                foreach ( $post_ids as $post_id ) {
                    if ( $this->set_featured( $post_id, false ) ) {
                        $processed++;
                    }
                }
                $redirect_url = add_query_arg( 'wprh_featured_unset', $processed, $redirect_url );
                break;

            case 'wprh_clear_stats':
                if ( class_exists( 'WPResourceHub\\Stats\\StatsManager' ) ) {
                    $stats_manager = \WPResourceHub\Stats\StatsManager::get_instance();
                    foreach ( $post_ids as $post_id ) {
                        $stats_manager->clear_stats( $post_id );
                        $processed++;
                    }
                }
                $redirect_url = add_query_arg( 'wprh_stats_cleared', $processed, $redirect_url );
                break;

            case 'wprh_change_type':
                // This action requires a modal selection, handled via AJAX.
                $redirect_url = add_query_arg(
                    array(
                        'wprh_bulk_action' => 'change_type',
                        'wprh_post_ids'    => implode( ',', $post_ids ),
                    ),
                    $redirect_url
                );
                break;

            case 'wprh_add_to_collection':
                // This action requires a modal selection, handled via AJAX.
                $redirect_url = add_query_arg(
                    array(
                        'wprh_bulk_action' => 'add_to_collection',
                        'wprh_post_ids'    => implode( ',', $post_ids ),
                    ),
                    $redirect_url
                );
                break;
        }

        /**
         * Filter the redirect URL after bulk action processing.
         *
         * @since 1.1.0
         *
         * @param string $redirect_url Redirect URL.
         * @param string $action       Action performed.
         * @param array  $post_ids     Post IDs processed.
         * @param int    $processed    Number processed.
         */
        return apply_filters( 'wprh_bulk_action_redirect', $redirect_url, $action, $post_ids, $processed );
    }

    /**
     * Set or unset featured flag.
     *
     * @since 1.1.0
     *
     * @param int  $post_id  Post ID.
     * @param bool $featured Whether to set as featured.
     * @return bool
     */
    private function set_featured( $post_id, $featured ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return false;
        }

        update_post_meta( $post_id, '_wprh_featured', $featured ? '1' : '0' );
        return true;
    }

    /**
     * Display bulk action notices.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function bulk_action_notices() {
        $notices = array(
            'wprh_featured_set'   => __( '%d resources marked as featured.', 'wp-resource-hub' ),
            'wprh_featured_unset' => __( '%d resources removed from featured.', 'wp-resource-hub' ),
            'wprh_stats_cleared'  => __( 'Statistics cleared for %d resources.', 'wp-resource-hub' ),
            'wprh_type_changed'   => __( 'Type changed for %d resources.', 'wp-resource-hub' ),
            'wprh_added_to_collection' => __( '%d resources added to collection.', 'wp-resource-hub' ),
        );

        foreach ( $notices as $key => $message ) {
            if ( isset( $_GET[ $key ] ) ) {
                $count = absint( $_GET[ $key ] );
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html( sprintf( $message, $count ) )
                );
            }
        }
    }

    /**
     * Output bulk action modal HTML.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function bulk_action_modal() {
        global $post_type;

        if ( ResourcePostType::get_post_type() !== $post_type ) {
            return;
        }

        $types = get_terms(
            array(
                'taxonomy'   => ResourceTypeTax::get_taxonomy(),
                'hide_empty' => false,
            )
        );

        $collections = get_posts(
            array(
                'post_type'      => 'resource_collection',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );
        ?>
        <!-- Change Type Modal -->
        <div id="wprh-change-type-modal" class="wprh-modal" style="display: none;">
            <div class="wprh-modal-content">
                <span class="wprh-modal-close">&times;</span>
                <h2><?php esc_html_e( 'Change Resource Type', 'wp-resource-hub' ); ?></h2>
                <p><?php esc_html_e( 'Select the new type for the selected resources:', 'wp-resource-hub' ); ?></p>

                <select id="wprh-new-type" class="widefat">
                    <option value=""><?php esc_html_e( '— Select Type —', 'wp-resource-hub' ); ?></option>
                    <?php foreach ( $types as $type ) : ?>
                        <option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
                    <?php endforeach; ?>
                </select>

                <p class="wprh-modal-warning">
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e( 'Warning: Changing the type may cause some metadata to become inaccessible.', 'wp-resource-hub' ); ?>
                </p>

                <div class="wprh-modal-buttons">
                    <button type="button" class="button" id="wprh-cancel-change-type"><?php esc_html_e( 'Cancel', 'wp-resource-hub' ); ?></button>
                    <button type="button" class="button button-primary" id="wprh-confirm-change-type"><?php esc_html_e( 'Change Type', 'wp-resource-hub' ); ?></button>
                </div>
            </div>
        </div>

        <!-- Add to Collection Modal -->
        <div id="wprh-add-to-collection-modal" class="wprh-modal" style="display: none;">
            <div class="wprh-modal-content">
                <span class="wprh-modal-close">&times;</span>
                <h2><?php esc_html_e( 'Add to Collection', 'wp-resource-hub' ); ?></h2>
                <p><?php esc_html_e( 'Select the collection to add the selected resources to:', 'wp-resource-hub' ); ?></p>

                <?php if ( ! empty( $collections ) ) : ?>
                    <select id="wprh-target-collection" class="widefat">
                        <option value=""><?php esc_html_e( '— Select Collection —', 'wp-resource-hub' ); ?></option>
                        <?php foreach ( $collections as $collection ) : ?>
                            <option value="<?php echo esc_attr( $collection->ID ); ?>"><?php echo esc_html( $collection->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <p class="wprh-no-collections">
                        <?php esc_html_e( 'No collections found.', 'wp-resource-hub' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=resource_collection' ) ); ?>">
                            <?php esc_html_e( 'Create one', 'wp-resource-hub' ); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <div class="wprh-modal-buttons">
                    <button type="button" class="button" id="wprh-cancel-add-collection"><?php esc_html_e( 'Cancel', 'wp-resource-hub' ); ?></button>
                    <button type="button" class="button button-primary" id="wprh-confirm-add-collection" <?php echo empty( $collections ) ? 'disabled' : ''; ?>>
                        <?php esc_html_e( 'Add to Collection', 'wp-resource-hub' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <style>
            .wprh-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .wprh-modal-content {
                background: #fff;
                padding: 20px;
                border-radius: 4px;
                max-width: 500px;
                width: 90%;
                position: relative;
            }
            .wprh-modal-close {
                position: absolute;
                right: 15px;
                top: 10px;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }
            .wprh-modal-close:hover { color: #000; }
            .wprh-modal h2 { margin-top: 0; }
            .wprh-modal select { margin: 15px 0; }
            .wprh-modal-warning {
                background: #fff8e5;
                border-left: 4px solid #ffb900;
                padding: 10px;
                margin: 15px 0;
            }
            .wprh-modal-warning .dashicons { color: #ffb900; }
            .wprh-modal-buttons {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 20px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var postIds = [];

            // Check URL for pending bulk action.
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('wprh_bulk_action')) {
                postIds = urlParams.get('wprh_post_ids').split(',');

                if (urlParams.get('wprh_bulk_action') === 'change_type') {
                    $('#wprh-change-type-modal').show();
                } else if (urlParams.get('wprh_bulk_action') === 'add_to_collection') {
                    $('#wprh-add-to-collection-modal').show();
                }
            }

            // Close modals.
            $('.wprh-modal-close, #wprh-cancel-change-type, #wprh-cancel-add-collection').on('click', function() {
                $('.wprh-modal').hide();
                // Clean URL.
                var cleanUrl = window.location.href.split('?')[0] + '?post_type=resource';
                window.history.replaceState({}, '', cleanUrl);
            });

            // Confirm change type.
            $('#wprh-confirm-change-type').on('click', function() {
                var newType = $('#wprh-new-type').val();
                if (!newType) {
                    alert('<?php echo esc_js( __( 'Please select a type.', 'wp-resource-hub' ) ); ?>');
                    return;
                }

                $(this).prop('disabled', true).text('<?php echo esc_js( __( 'Processing...', 'wp-resource-hub' ) ); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wprh_bulk_change_type',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wprh_bulk_action' ) ); ?>',
                        post_ids: postIds,
                        new_type: newType
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo esc_js( admin_url( 'edit.php?post_type=resource' ) ); ?>&wprh_type_changed=' + response.data.count;
                        } else {
                            alert(response.data.message || '<?php echo esc_js( __( 'An error occurred.', 'wp-resource-hub' ) ); ?>');
                        }
                    }
                });
            });

            // Confirm add to collection.
            $('#wprh-confirm-add-collection').on('click', function() {
                var collectionId = $('#wprh-target-collection').val();
                if (!collectionId) {
                    alert('<?php echo esc_js( __( 'Please select a collection.', 'wp-resource-hub' ) ); ?>');
                    return;
                }

                $(this).prop('disabled', true).text('<?php echo esc_js( __( 'Processing...', 'wp-resource-hub' ) ); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wprh_bulk_add_to_collection',
                        nonce: '<?php echo esc_js( wp_create_nonce( 'wprh_bulk_action' ) ); ?>',
                        post_ids: postIds,
                        collection_id: collectionId
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo esc_js( admin_url( 'edit.php?post_type=resource' ) ); ?>&wprh_added_to_collection=' + response.data.count;
                        } else {
                            alert(response.data.message || '<?php echo esc_js( __( 'An error occurred.', 'wp-resource-hub' ) ); ?>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for bulk change type.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function ajax_bulk_change_type() {
        check_ajax_referer( 'wprh_bulk_action', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-resource-hub' ) ) );
        }

        $post_ids = isset( $_POST['post_ids'] ) ? array_map( 'absint', (array) $_POST['post_ids'] ) : array();
        $new_type = isset( $_POST['new_type'] ) ? sanitize_text_field( $_POST['new_type'] ) : '';

        if ( empty( $post_ids ) || empty( $new_type ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wp-resource-hub' ) ) );
        }

        $count = 0;
        foreach ( $post_ids as $post_id ) {
            if ( current_user_can( 'edit_post', $post_id ) ) {
                update_post_meta( $post_id, '_wprh_resource_type', $new_type );
                wp_set_object_terms( $post_id, $new_type, ResourceTypeTax::get_taxonomy() );
                $count++;
            }
        }

        wp_send_json_success( array( 'count' => $count ) );
    }

    /**
     * AJAX handler for bulk add to collection.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function ajax_bulk_add_to_collection() {
        check_ajax_referer( 'wprh_bulk_action', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-resource-hub' ) ) );
        }

        $post_ids      = isset( $_POST['post_ids'] ) ? array_map( 'absint', (array) $_POST['post_ids'] ) : array();
        $collection_id = isset( $_POST['collection_id'] ) ? absint( $_POST['collection_id'] ) : 0;

        if ( empty( $post_ids ) || empty( $collection_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wp-resource-hub' ) ) );
        }

        if ( ! class_exists( 'WPResourceHub\\PostTypes\\CollectionPostType' ) ) {
            wp_send_json_error( array( 'message' => __( 'Collections not available.', 'wp-resource-hub' ) ) );
        }

        $count = 0;
        foreach ( $post_ids as $post_id ) {
            if ( \WPResourceHub\PostTypes\CollectionPostType::add_resource_to_collection( $collection_id, $post_id ) ) {
                $count++;
            }
        }

        wp_send_json_success( array( 'count' => $count ) );
    }
}
