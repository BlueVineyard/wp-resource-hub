<?php
/**
 * Actions class.
 *
 * Registers and manages plugin actions for extensibility.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Hooks;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Admin\MetaBoxes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Actions class.
 *
 * @since 1.0.0
 */
class Actions {

    /**
     * Singleton instance.
     *
     * @var Actions|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return Actions
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
        $this->register_actions();
    }

    /**
     * Register plugin actions.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function register_actions() {
        // Enqueue frontend assets.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Add resource meta to single view.
        add_action( 'wprh_before_resource_content', array( $this, 'render_resource_meta' ), 10 );
        add_action( 'wprh_after_resource_content', array( $this, 'render_resource_footer' ), 10 );

        // Register REST API fields.
        add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        if ( ! is_singular( ResourcePostType::get_post_type() ) && ! is_post_type_archive( ResourcePostType::get_post_type() ) ) {
            return;
        }

        wp_enqueue_style(
            'wprh-frontend',
            WPRH_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WPRH_VERSION
        );

        wp_enqueue_script(
            'wprh-frontend',
            WPRH_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            WPRH_VERSION,
            true
        );

        wp_localize_script(
            'wprh-frontend',
            'wprhFrontend',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wprh_frontend' ),
            )
        );

        /**
         * Fires after frontend assets are enqueued.
         *
         * @since 1.0.0
         */
        do_action( 'wprh_enqueue_frontend_assets' );
    }

    /**
     * Render resource meta information.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_resource_meta( $post ) {
        $resource_type = ResourceTypeTax::get_resource_type( $post );
        $reading_time  = MetaBoxes::get_meta( $post->ID, 'reading_time' );

        /**
         * Filter whether to display resource meta.
         *
         * @since 1.0.0
         *
         * @param bool     $display Whether to display meta.
         * @param \WP_Post $post    Post object.
         */
        if ( ! apply_filters( 'wprh_display_resource_meta', true, $post ) ) {
            return;
        }
        ?>
        <div class="wprh-resource-meta">
            <?php if ( $resource_type ) : ?>
                <span class="wprh-meta-type">
                    <span class="dashicons <?php echo esc_attr( ResourceTypeTax::get_type_icon( $resource_type ) ); ?>"></span>
                    <?php echo esc_html( $resource_type->name ); ?>
                </span>
            <?php endif; ?>

            <?php if ( $reading_time && 'internal-content' === ResourceTypeTax::get_resource_type_slug( $post ) ) : ?>
                <span class="wprh-meta-reading-time">
                    <span class="dashicons dashicons-clock"></span>
                    <?php
                    /* translators: %d: Number of minutes */
                    printf( esc_html( _n( '%d min read', '%d min read', $reading_time, 'wp-resource-hub' ) ), esc_html( $reading_time ) );
                    ?>
                </span>
            <?php endif; ?>

            <?php
            /**
             * Fires after resource meta items.
             *
             * @since 1.0.0
             *
             * @param \WP_Post $post Post object.
             */
            do_action( 'wprh_resource_meta_items', $post );
            ?>
        </div>
        <?php
    }

    /**
     * Render resource footer content.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_resource_footer( $post ) {
        $show_related = MetaBoxes::get_meta( $post->ID, 'show_related' );

        /**
         * Filter whether to display resource footer.
         *
         * @since 1.0.0
         *
         * @param bool     $display Whether to display footer.
         * @param \WP_Post $post    Post object.
         */
        if ( ! apply_filters( 'wprh_display_resource_footer', true, $post ) ) {
            return;
        }

        /**
         * Fires at the start of resource footer.
         *
         * @since 1.0.0
         *
         * @param \WP_Post $post Post object.
         */
        do_action( 'wprh_resource_footer_start', $post );

        // Show related resources if enabled.
        if ( $show_related ) {
            $this->render_related_resources( $post );
        }

        /**
         * Fires at the end of resource footer.
         *
         * @since 1.0.0
         *
         * @param \WP_Post $post Post object.
         */
        do_action( 'wprh_resource_footer_end', $post );
    }

    /**
     * Render related resources.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return void
     */
    private function render_related_resources( $post ) {
        $topics = wp_get_post_terms( $post->ID, 'resource_topic', array( 'fields' => 'ids' ) );

        if ( empty( $topics ) || is_wp_error( $topics ) ) {
            return;
        }

        $related = get_posts(
            array(
                'post_type'      => ResourcePostType::get_post_type(),
                'posts_per_page' => 3,
                'post__not_in'   => array( $post->ID ),
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'resource_topic',
                        'field'    => 'term_id',
                        'terms'    => $topics,
                    ),
                ),
            )
        );

        if ( empty( $related ) ) {
            return;
        }

        /**
         * Filter the related resources.
         *
         * @since 1.0.0
         *
         * @param array    $related Related posts.
         * @param \WP_Post $post    Current post.
         */
        $related = apply_filters( 'wprh_related_resources', $related, $post );
        ?>
        <div class="wprh-related-resources">
            <h3><?php esc_html_e( 'Related Resources', 'wp-resource-hub' ); ?></h3>
            <div class="wprh-related-list">
                <?php foreach ( $related as $related_post ) : ?>
                    <div class="wprh-related-item">
                        <a href="<?php echo esc_url( get_permalink( $related_post ) ); ?>">
                            <?php echo esc_html( get_the_title( $related_post ) ); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Register REST API fields.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_rest_fields() {
        register_rest_field(
            ResourcePostType::get_post_type(),
            'resource_meta',
            array(
                'get_callback' => array( $this, 'get_rest_resource_meta' ),
                'schema'       => array(
                    'description' => __( 'Resource metadata', 'wp-resource-hub' ),
                    'type'        => 'object',
                ),
            )
        );
    }

    /**
     * Get resource meta for REST API.
     *
     * @since 1.0.0
     *
     * @param array $object Post object as array.
     * @return array
     */
    public function get_rest_resource_meta( $object ) {
        $post_id       = $object['id'];
        $resource_type = MetaBoxes::get_meta( $post_id, 'resource_type' );

        $meta = array(
            'resource_type' => $resource_type,
        );

        switch ( $resource_type ) {
            case 'video':
                $meta['video_provider']  = MetaBoxes::get_meta( $post_id, 'video_provider' );
                $meta['video_url']       = MetaBoxes::get_meta( $post_id, 'video_url' );
                $meta['video_id']        = MetaBoxes::get_meta( $post_id, 'video_id' );
                $meta['video_duration']  = MetaBoxes::get_meta( $post_id, 'video_duration' );
                break;

            case 'pdf':
                $meta['pdf_file']        = MetaBoxes::get_meta( $post_id, 'pdf_file' );
                $meta['pdf_file_size']   = MetaBoxes::get_meta( $post_id, 'pdf_file_size' );
                $meta['pdf_page_count']  = MetaBoxes::get_meta( $post_id, 'pdf_page_count' );
                $meta['pdf_viewer_mode'] = MetaBoxes::get_meta( $post_id, 'pdf_viewer_mode' );
                break;

            case 'download':
                $meta['download_file']      = MetaBoxes::get_meta( $post_id, 'download_file' );
                $meta['download_file_size'] = MetaBoxes::get_meta( $post_id, 'download_file_size' );
                $meta['download_version']   = MetaBoxes::get_meta( $post_id, 'download_version' );
                break;

            case 'external-link':
                $meta['external_url']  = MetaBoxes::get_meta( $post_id, 'external_url' );
                $meta['open_new_tab']  = MetaBoxes::get_meta( $post_id, 'open_new_tab' );
                break;

            case 'internal-content':
                $meta['summary']       = MetaBoxes::get_meta( $post_id, 'summary' );
                $meta['reading_time']  = MetaBoxes::get_meta( $post_id, 'reading_time' );
                $meta['show_toc']      = MetaBoxes::get_meta( $post_id, 'show_toc' );
                $meta['show_related']  = MetaBoxes::get_meta( $post_id, 'show_related' );
                break;
        }

        /**
         * Filter the REST API resource meta.
         *
         * @since 1.0.0
         *
         * @param array $meta    Resource meta.
         * @param int   $post_id Post ID.
         */
        return apply_filters( 'wprh_rest_resource_meta', $meta, $post_id );
    }
}
