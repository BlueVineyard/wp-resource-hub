<?php
/**
 * Resources Shortcode class.
 *
 * Handles the [resources] shortcode for displaying resource grids/lists.
 *
 * @package WPResourceHub
 * @since   1.2.0
 */

namespace WPResourceHub\Shortcodes;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;
use WPResourceHub\Admin\SettingsPage;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resources Shortcode class.
 *
 * @since 1.2.0
 */
class ResourcesShortcode {

    /**
     * Singleton instance.
     *
     * @var ResourcesShortcode|null
     */
    private static $instance = null;

    /**
     * Shortcode tag.
     *
     * @var string
     */
    const TAG = 'resources';

    /**
     * Get the singleton instance.
     *
     * @since 1.2.0
     *
     * @return ResourcesShortcode
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
     * @since 1.2.0
     */
    private function __construct() {
        add_shortcode( self::TAG, array( $this, 'render' ) );
        add_action( 'wp_ajax_wprh_filter_resources', array( $this, 'ajax_filter' ) );
        add_action( 'wp_ajax_nopriv_wprh_filter_resources', array( $this, 'ajax_filter' ) );
    }

    /**
     * Get default attributes.
     *
     * @since 1.2.0
     *
     * @return array
     */
    private function get_defaults() {
        return array(
            'layout'          => SettingsPage::get_setting( 'frontend', 'default_layout', 'grid' ),
            'columns'         => 3,
            'limit'           => SettingsPage::get_setting( 'frontend', 'items_per_page', 12 ),
            'type'            => '',
            'topic'           => '',
            'audience'        => '',
            'orderby'         => SettingsPage::get_setting( 'general', 'default_ordering', 'date' ),
            'order'           => 'DESC',
            'show_filters'    => 'true',
            'show_type_filter'     => SettingsPage::get_setting( 'frontend', 'enable_type_filter', true ) ? 'true' : 'false',
            'show_topic_filter'    => SettingsPage::get_setting( 'frontend', 'enable_topic_filter', true ) ? 'true' : 'false',
            'show_audience_filter' => SettingsPage::get_setting( 'frontend', 'enable_audience_filter', true ) ? 'true' : 'false',
            'show_search'     => 'true',
            'show_pagination' => 'true',
            'featured_only'   => 'false',
            'exclude'         => '',
            'include'         => '',
            'class'           => '',
            'id'              => '',
        );
    }

    /**
     * Render the shortcode.
     *
     * @since 1.2.0
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string
     */
    public function render( $atts, $content = '' ) {
        $atts = shortcode_atts( $this->get_defaults(), $atts, self::TAG );

        // Normalize boolean attributes.
        $atts['show_filters']         = filter_var( $atts['show_filters'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_type_filter']     = filter_var( $atts['show_type_filter'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_topic_filter']    = filter_var( $atts['show_topic_filter'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_audience_filter'] = filter_var( $atts['show_audience_filter'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_search']          = filter_var( $atts['show_search'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_pagination']      = filter_var( $atts['show_pagination'], FILTER_VALIDATE_BOOLEAN );
        $atts['featured_only']        = filter_var( $atts['featured_only'], FILTER_VALIDATE_BOOLEAN );

        // Enqueue assets.
        $this->enqueue_assets();

        // Build query args.
        $query_args = $this->build_query_args( $atts );

        // Get resources.
        $query = new \WP_Query( $query_args );

        // Generate unique ID for this instance.
        $instance_id = ! empty( $atts['id'] ) ? $atts['id'] : 'wprh-resources-' . wp_unique_id();

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $instance_id ); ?>"
             class="wprh-resources-container <?php echo esc_attr( $atts['class'] ); ?>"
             data-atts="<?php echo esc_attr( wp_json_encode( $atts ) ); ?>">

            <?php if ( $atts['show_filters'] || $atts['show_search'] ) : ?>
                <div class="wprh-resources-toolbar">
                    <?php if ( $atts['show_search'] ) : ?>
                        <div class="wprh-resources-search">
                            <input type="text"
                                   class="wprh-search-input"
                                   placeholder="<?php esc_attr_e( 'Search resources...', 'wp-resource-hub' ); ?>"
                                   value="">
                            <span class="wprh-search-icon dashicons dashicons-search"></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $atts['show_filters'] ) : ?>
                        <div class="wprh-resources-filters">
                            <?php if ( $atts['show_type_filter'] ) : ?>
                                <?php echo $this->render_filter_dropdown( 'type', ResourceTypeTax::get_taxonomy(), __( 'All Types', 'wp-resource-hub' ), $atts['type'] ); ?>
                            <?php endif; ?>

                            <?php if ( $atts['show_topic_filter'] ) : ?>
                                <?php echo $this->render_filter_dropdown( 'topic', ResourceTopicTax::get_taxonomy(), __( 'All Topics', 'wp-resource-hub' ), $atts['topic'] ); ?>
                            <?php endif; ?>

                            <?php if ( $atts['show_audience_filter'] ) : ?>
                                <?php echo $this->render_filter_dropdown( 'audience', ResourceAudienceTax::get_taxonomy(), __( 'All Audiences', 'wp-resource-hub' ), $atts['audience'] ); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="wprh-resources-grid-wrapper">
                <div class="wprh-resources-loading" style="display: none;">
                    <span class="wprh-spinner"></span>
                    <?php esc_html_e( 'Loading...', 'wp-resource-hub' ); ?>
                </div>

                <?php echo $this->render_resources( $query, $atts ); ?>
            </div>

            <?php if ( $atts['show_pagination'] && $query->max_num_pages > 1 ) : ?>
                <div class="wprh-resources-pagination">
                    <?php echo $this->render_pagination( $query, 1 ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build query arguments.
     *
     * @since 1.2.0
     *
     * @param array $atts Shortcode attributes.
     * @param int   $paged Current page number.
     * @return array
     */
    private function build_query_args( $atts, $paged = 1 ) {
        $args = array(
            'post_type'      => ResourcePostType::get_post_type(),
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['limit'] ),
            'paged'          => $paged,
            'orderby'        => $atts['orderby'],
            'order'          => strtoupper( $atts['order'] ),
        );

        // Tax query.
        $tax_query = array();

        if ( ! empty( $atts['type'] ) ) {
            $tax_query[] = array(
                'taxonomy' => ResourceTypeTax::get_taxonomy(),
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
            );
        }

        if ( ! empty( $atts['topic'] ) ) {
            $tax_query[] = array(
                'taxonomy' => ResourceTopicTax::get_taxonomy(),
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', $atts['topic'] ) ),
            );
        }

        if ( ! empty( $atts['audience'] ) ) {
            $tax_query[] = array(
                'taxonomy' => ResourceAudienceTax::get_taxonomy(),
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', $atts['audience'] ) ),
            );
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // Meta query for featured.
        if ( $atts['featured_only'] ) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_wprh_featured',
                    'value' => '1',
                ),
            );
        }

        // Include/exclude.
        if ( ! empty( $atts['include'] ) ) {
            $args['post__in'] = array_map( 'absint', explode( ',', $atts['include'] ) );
        }

        if ( ! empty( $atts['exclude'] ) ) {
            $args['post__not_in'] = array_map( 'absint', explode( ',', $atts['exclude'] ) );
        }

        // Search.
        if ( ! empty( $atts['search'] ) ) {
            $args['s'] = sanitize_text_field( $atts['search'] );
        }

        /**
         * Filter the resources query arguments.
         *
         * @since 1.2.0
         *
         * @param array $args Query arguments.
         * @param array $atts Shortcode attributes.
         */
        return apply_filters( 'wprh_resources_query_args', $args, $atts );
    }

    /**
     * Render resources grid/list.
     *
     * @since 1.2.0
     *
     * @param \WP_Query $query Query object.
     * @param array     $atts  Shortcode attributes.
     * @return string
     */
    public function render_resources( $query, $atts ) {
        if ( ! $query->have_posts() ) {
            return '<div class="wprh-no-resources">' .
                   esc_html__( 'No resources found.', 'wp-resource-hub' ) .
                   '</div>';
        }

        $layout_class = 'wprh-layout-' . esc_attr( $atts['layout'] );
        $columns_class = 'wprh-columns-' . intval( $atts['columns'] );

        ob_start();
        ?>
        <div class="wprh-resources-grid <?php echo esc_attr( $layout_class . ' ' . $columns_class ); ?>">
            <?php while ( $query->have_posts() ) : ?>
                <?php $query->the_post(); ?>
                <?php echo $this->render_resource_card( get_post(), $atts ); ?>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single resource card.
     *
     * @since 1.2.0
     *
     * @param \WP_Post $post Resource post.
     * @param array    $atts Shortcode attributes.
     * @return string
     */
    private function render_resource_card( $post, $atts ) {
        $resource_type = ResourceTypeTax::get_resource_type( $post->ID );
        $type_slug     = $resource_type ? $resource_type->slug : '';
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon( $resource_type ) : 'dashicons-media-default';

        ob_start();
        ?>
        <article class="wprh-resource-card wprh-type-<?php echo esc_attr( $type_slug ); ?>" data-id="<?php echo esc_attr( $post->ID ); ?>">
            <div class="wprh-card-media">
                <?php if ( has_post_thumbnail( $post ) ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="wprh-card-image">
                        <?php echo get_the_post_thumbnail( $post, 'medium_large' ); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="wprh-card-image wprh-card-placeholder">
                        <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                    </a>
                <?php endif; ?>

                <?php if ( $resource_type ) : ?>
                    <span class="wprh-card-type wprh-type-badge-<?php echo esc_attr( $type_slug ); ?>">
                        <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                        <?php echo esc_html( $resource_type->name ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="wprh-card-body">
                <h3 class="wprh-card-title">
                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
                        <?php echo esc_html( get_the_title( $post ) ); ?>
                    </a>
                </h3>

                <?php if ( has_excerpt( $post ) || ! empty( $post->post_content ) ) : ?>
                    <div class="wprh-card-excerpt">
                        <?php echo wp_trim_words( get_the_excerpt( $post ), 20, '...' ); ?>
                    </div>
                <?php endif; ?>

                <div class="wprh-card-meta">
                    <?php
                    $topics = get_the_term_list( $post->ID, ResourceTopicTax::get_taxonomy(), '', ', ', '' );
                    if ( $topics && ! is_wp_error( $topics ) ) :
                    ?>
                        <span class="wprh-card-topics"><?php echo $topics; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="wprh-card-footer">
                <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="wprh-card-link">
                    <?php esc_html_e( 'View Resource', 'wp-resource-hub' ); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Render filter dropdown.
     *
     * @since 1.2.0
     *
     * @param string $filter_key Filter key.
     * @param string $taxonomy   Taxonomy name.
     * @param string $all_label  Label for "all" option.
     * @param string $selected   Selected value.
     * @return string
     */
    private function render_filter_dropdown( $filter_key, $taxonomy, $all_label, $selected = '' ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            )
        );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }

        ob_start();
        ?>
        <select class="wprh-filter-select" data-filter="<?php echo esc_attr( $filter_key ); ?>">
            <option value=""><?php echo esc_html( $all_label ); ?></option>
            <?php foreach ( $terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Render pagination.
     *
     * @since 1.2.0
     *
     * @param \WP_Query $query   Query object.
     * @param int       $current Current page.
     * @return string
     */
    public function render_pagination( $query, $current = 1 ) {
        $total = $query->max_num_pages;

        if ( $total <= 1 ) {
            return '';
        }

        ob_start();
        ?>
        <div class="wprh-pagination" data-current="<?php echo esc_attr( $current ); ?>" data-total="<?php echo esc_attr( $total ); ?>">
            <button type="button" class="wprh-page-btn wprh-prev" <?php disabled( $current, 1 ); ?>>
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e( 'Previous', 'wp-resource-hub' ); ?>
            </button>

            <span class="wprh-page-info">
                <?php
                /* translators: 1: Current page, 2: Total pages */
                printf( esc_html__( 'Page %1$d of %2$d', 'wp-resource-hub' ), $current, $total );
                ?>
            </span>

            <button type="button" class="wprh-page-btn wprh-next" <?php disabled( $current, $total ); ?>>
                <?php esc_html_e( 'Next', 'wp-resource-hub' ); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX filter handler.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public function ajax_filter() {
        check_ajax_referer( 'wprh_frontend_nonce', 'nonce' );

        $atts = isset( $_POST['atts'] ) ? json_decode( stripslashes( $_POST['atts'] ), true ) : array();
        $atts = shortcode_atts( $this->get_defaults(), $atts );

        // Override with filter values.
        if ( isset( $_POST['type'] ) ) {
            $atts['type'] = sanitize_text_field( $_POST['type'] );
        }
        if ( isset( $_POST['topic'] ) ) {
            $atts['topic'] = sanitize_text_field( $_POST['topic'] );
        }
        if ( isset( $_POST['audience'] ) ) {
            $atts['audience'] = sanitize_text_field( $_POST['audience'] );
        }
        if ( isset( $_POST['search'] ) ) {
            $atts['search'] = sanitize_text_field( $_POST['search'] );
        }

        $paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

        // Build query.
        $query_args = $this->build_query_args( $atts, $paged );
        $query = new \WP_Query( $query_args );

        wp_send_json_success(
            array(
                'html'       => $this->render_resources( $query, $atts ),
                'pagination' => $this->render_pagination( $query, $paged ),
                'found'      => $query->found_posts,
                'max_pages'  => $query->max_num_pages,
            )
        );
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 1.2.0
     *
     * @return void
     */
    private function enqueue_assets() {
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
                'nonce'   => wp_create_nonce( 'wprh_frontend_nonce' ),
                'i18n'    => array(
                    'loading'    => __( 'Loading...', 'wp-resource-hub' ),
                    'noResults'  => __( 'No resources found.', 'wp-resource-hub' ),
                    'error'      => __( 'An error occurred. Please try again.', 'wp-resource-hub' ),
                ),
            )
        );
    }
}
