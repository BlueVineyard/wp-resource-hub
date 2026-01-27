<?php
/**
 * Resource Shortcode class.
 *
 * Handles the [resource] shortcode for displaying a single resource.
 *
 * @package WPResourceHub
 * @since   1.2.0
 */

namespace WPResourceHub\Shortcodes;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Frontend\SingleRenderer;
use WPResourceHub\AccessControl\AccessManager;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resource Shortcode class.
 *
 * @since 1.2.0
 */
class ResourceShortcode {

    /**
     * Singleton instance.
     *
     * @var ResourceShortcode|null
     */
    private static $instance = null;

    /**
     * Shortcode tag.
     *
     * @var string
     */
    const TAG = 'resource';

    /**
     * Get the singleton instance.
     *
     * @since 1.2.0
     *
     * @return ResourceShortcode
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
            'id'          => 0,
            'slug'        => '',
            'display'     => 'full',      // full, card, embed, link.
            'show_title'  => 'true',
            'show_meta'   => 'true',
            'show_image'  => 'true',
            'class'       => '',
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
        $atts['show_title'] = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_meta']  = filter_var( $atts['show_meta'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_image'] = filter_var( $atts['show_image'], FILTER_VALIDATE_BOOLEAN );

        // Get resource.
        $resource = $this->get_resource( $atts );

        if ( ! $resource ) {
            return '<div class="wprh-resource-error">' .
                   esc_html__( 'Resource not found.', 'wp-resource-hub' ) .
                   '</div>';
        }

        // Check access.
        $access_manager = AccessManager::get_instance();
        if ( ! $access_manager->can_access( $resource->ID ) ) {
            return '<div class="wprh-resource-restricted">' .
                   esc_html( $access_manager->get_access_denied_message( $resource->ID ) ) .
                   '</div>';
        }

        // Enqueue assets.
        wp_enqueue_style( 'wprh-frontend', WPRH_PLUGIN_URL . 'assets/css/frontend.css', array(), WPRH_VERSION );

        // Render based on display mode.
        switch ( $atts['display'] ) {
            case 'card':
                return $this->render_card( $resource, $atts );

            case 'embed':
                return $this->render_embed( $resource, $atts );

            case 'link':
                return $this->render_link( $resource, $atts );

            case 'full':
            default:
                return $this->render_full( $resource, $atts );
        }
    }

    /**
     * Get resource by ID or slug.
     *
     * @since 1.2.0
     *
     * @param array $atts Shortcode attributes.
     * @return \WP_Post|null
     */
    private function get_resource( $atts ) {
        if ( ! empty( $atts['id'] ) ) {
            $post = get_post( absint( $atts['id'] ) );
            if ( $post && ResourcePostType::get_post_type() === $post->post_type ) {
                return $post;
            }
        }

        if ( ! empty( $atts['slug'] ) ) {
            $posts = get_posts(
                array(
                    'post_type'      => ResourcePostType::get_post_type(),
                    'name'           => sanitize_title( $atts['slug'] ),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                )
            );
            if ( ! empty( $posts ) ) {
                return $posts[0];
            }
        }

        return null;
    }

    /**
     * Render full resource display.
     *
     * @since 1.2.0
     *
     * @param \WP_Post $resource Resource post.
     * @param array    $atts     Shortcode attributes.
     * @return string
     */
    private function render_full( $resource, $atts ) {
        $resource_type = ResourceTypeTax::get_resource_type( $resource->ID );
        $type_slug     = $resource_type ? $resource_type->slug : '';
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon( $resource_type ) : 'dashicons-media-default';

        ob_start();
        ?>
        <div class="wprh-resource-single wprh-type-<?php echo esc_attr( $type_slug ); ?> <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $atts['show_title'] ) : ?>
                <header class="wprh-resource-header">
                    <h2 class="wprh-resource-title">
                        <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>">
                            <?php echo esc_html( get_the_title( $resource ) ); ?>
                        </a>
                    </h2>
                    <?php if ( $atts['show_meta'] && $resource_type ) : ?>
                        <span class="wprh-resource-type">
                            <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                            <?php echo esc_html( $resource_type->name ); ?>
                        </span>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <?php if ( $atts['show_image'] && has_post_thumbnail( $resource ) ) : ?>
                <div class="wprh-resource-image">
                    <?php echo get_the_post_thumbnail( $resource, 'large' ); ?>
                </div>
            <?php endif; ?>

            <div class="wprh-resource-content">
                <?php echo SingleRenderer::get_instance()->render( $resource ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render card display.
     *
     * @since 1.2.0
     *
     * @param \WP_Post $resource Resource post.
     * @param array    $atts     Shortcode attributes.
     * @return string
     */
    private function render_card( $resource, $atts ) {
        $resource_type = ResourceTypeTax::get_resource_type( $resource->ID );
        $type_slug     = $resource_type ? $resource_type->slug : '';
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon( $resource_type ) : 'dashicons-media-default';

        ob_start();
        ?>
        <article class="wprh-resource-card wprh-type-<?php echo esc_attr( $type_slug ); ?> <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $atts['show_image'] ) : ?>
                <div class="wprh-card-media">
                    <?php if ( has_post_thumbnail( $resource ) ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>" class="wprh-card-image">
                            <?php echo get_the_post_thumbnail( $resource, 'medium_large' ); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>" class="wprh-card-image wprh-card-placeholder">
                            <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                        </a>
                    <?php endif; ?>

                    <?php if ( $resource_type && $atts['show_meta'] ) : ?>
                        <span class="wprh-card-type">
                            <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                            <?php echo esc_html( $resource_type->name ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="wprh-card-body">
                <?php if ( $atts['show_title'] ) : ?>
                    <h3 class="wprh-card-title">
                        <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>">
                            <?php echo esc_html( get_the_title( $resource ) ); ?>
                        </a>
                    </h3>
                <?php endif; ?>

                <div class="wprh-card-excerpt">
                    <?php echo wp_trim_words( get_the_excerpt( $resource ), 20, '...' ); ?>
                </div>
            </div>

            <div class="wprh-card-footer">
                <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>" class="wprh-card-link">
                    <?php esc_html_e( 'View Resource', 'wp-resource-hub' ); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Render embed display (for videos, PDFs).
     *
     * @since 1.2.0
     *
     * @param \WP_Post $resource Resource post.
     * @param array    $atts     Shortcode attributes.
     * @return string
     */
    private function render_embed( $resource, $atts ) {
        $resource_type = ResourceTypeTax::get_resource_type_slug( $resource );

        ob_start();
        ?>
        <div class="wprh-resource-embed wprh-type-<?php echo esc_attr( $resource_type ); ?> <?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( $atts['show_title'] ) : ?>
                <h3 class="wprh-embed-title">
                    <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>">
                        <?php echo esc_html( get_the_title( $resource ) ); ?>
                    </a>
                </h3>
            <?php endif; ?>

            <div class="wprh-embed-content">
                <?php echo SingleRenderer::get_instance()->render( $resource ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render link display.
     *
     * @since 1.2.0
     *
     * @param \WP_Post $resource Resource post.
     * @param array    $atts     Shortcode attributes.
     * @return string
     */
    private function render_link( $resource, $atts ) {
        $resource_type = ResourceTypeTax::get_resource_type( $resource->ID );
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon( $resource_type ) : 'dashicons-media-default';

        return sprintf(
            '<a href="%s" class="wprh-resource-link %s"><span class="dashicons %s"></span> %s</a>',
            esc_url( get_permalink( $resource ) ),
            esc_attr( $atts['class'] ),
            esc_attr( $type_icon ),
            esc_html( get_the_title( $resource ) )
        );
    }
}
