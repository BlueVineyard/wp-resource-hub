<?php
/**
 * Accordion Shortcode class.
 *
 * Handles the [wprh_accordion] shortcode for displaying custom accordion structures.
 *
 * @package WPResourceHub
 * @since   1.3.0
 */

namespace WPResourceHub\Shortcodes;

use WPResourceHub\PostTypes\AccordionPostType;
use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Accordion Shortcode class.
 *
 * @since 1.3.0
 */
class AccordionShortcode {

    /**
     * Singleton instance.
     *
     * @var AccordionShortcode|null
     */
    private static $instance = null;

    /**
     * Shortcode tag.
     *
     * @var string
     */
    const TAG = 'wprh_accordion';

    /**
     * Resource index counter for numbering.
     *
     * @var int
     */
    private $resource_index = 0;

    /**
     * Get the singleton instance.
     *
     * @since 1.3.0
     *
     * @return AccordionShortcode
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
        add_shortcode( self::TAG, array( $this, 'render' ) );
    }

    /**
     * Get default attributes.
     *
     * @since 1.3.0
     *
     * @return array
     */
    private function get_defaults() {
        return array(
            'id'    => 0,
            'class' => '',
        );
    }

    /**
     * Render the shortcode.
     *
     * @since 1.3.0
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string
     */
    public function render( $atts, $content = '' ) {
        $atts = shortcode_atts( $this->get_defaults(), $atts, self::TAG );

        $accordion_id = absint( $atts['id'] );
        if ( ! $accordion_id ) {
            return '<div class="wprh-accordion-error">' .
                esc_html__( 'Accordion ID is required.', 'wp-resource-hub' ) .
                '</div>';
        }

        $post = get_post( $accordion_id );
        if ( ! $post || AccordionPostType::get_post_type() !== $post->post_type || 'publish' !== $post->post_status ) {
            return '<div class="wprh-accordion-error">' .
                esc_html__( 'Accordion not found.', 'wp-resource-hub' ) .
                '</div>';
        }

        $structure = AccordionPostType::get_accordion_structure( $accordion_id );
        if ( empty( $structure ) ) {
            return '<div class="wprh-accordion-error">' .
                esc_html__( 'This accordion has no items.', 'wp-resource-hub' ) .
                '</div>';
        }

        // Enqueue assets.
        wp_enqueue_style( 'wprh-frontend', WPRH_PLUGIN_URL . 'assets/css/frontend.css', array(), WPRH_VERSION );
        wp_enqueue_script( 'wprh-frontend', WPRH_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), WPRH_VERSION, true );
        wp_localize_script( 'wprh-frontend', 'wprhFrontend', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wprh_frontend' ),
        ) );

        // Reset counter.
        $this->resource_index = 0;

        ob_start();
        ?>
        <div class="wprh-custom-accordion <?php echo esc_attr( $atts['class'] ); ?>" data-accordion-id="<?php echo esc_attr( $accordion_id ); ?>">
            <?php $this->render_items( $structure ); ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render accordion items recursively.
     *
     * @since 1.3.0
     *
     * @param array $items Structure items.
     * @return void
     */
    private function render_items( $items ) {
        foreach ( $items as $item ) {
            $type = isset( $item['type'] ) ? $item['type'] : '';

            switch ( $type ) {
                case 'heading':
                    $this->render_heading( $item );
                    break;

                case 'resource':
                    $this->render_resource( $item );
                    break;

                case 'accordion':
                    $this->render_nested_accordion( $item );
                    break;

                default:
                    /**
                     * Fires for custom accordion item types.
                     *
                     * @since 1.3.0
                     *
                     * @param array $item The item data.
                     */
                    do_action( 'wprh_render_accordion_item', $item );
                    break;
            }
        }
    }

    /**
     * Render a heading item.
     *
     * @since 1.3.0
     *
     * @param array $item Item data.
     * @return void
     */
    private function render_heading( $item ) {
        $title = isset( $item['title'] ) ? $item['title'] : '';
        if ( empty( $title ) ) {
            return;
        }
        ?>
        <h3 class="wprh-accordion-section-heading"><?php echo esc_html( $title ); ?></h3>
        <?php
    }

    /**
     * Render a resource item as an accordion.
     *
     * @since 1.3.0
     *
     * @param array $item Item data.
     * @return void
     */
    private function render_resource( $item ) {
        $resource_id = isset( $item['resource_id'] ) ? absint( $item['resource_id'] ) : 0;
        if ( ! $resource_id ) {
            return;
        }

        $resource = get_post( $resource_id );
        if ( ! $resource || 'publish' !== $resource->post_status ) {
            return;
        }

        $this->resource_index++;
        $resource_type = ResourceTypeTax::get_resource_type( $resource->ID );
        $type_slug     = $resource_type ? $resource_type->slug : '';
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon( $resource_type ) : 'dashicons-media-default';

        // Video-specific handling.
        if ( 'video' === $type_slug ) {
            $this->render_video_resource( $resource, $resource_type, $type_icon );
            return;
        }

        ?>
        <div class="wprh-accordion-item wprh-type-<?php echo esc_attr( $type_slug ); ?>"
            data-resource-id="<?php echo esc_attr( $resource->ID ); ?>">
            <button class="wprh-accordion-trigger" aria-expanded="false">
                <span class="wprh-accordion-number"><?php echo esc_html( $this->resource_index ); ?></span>
                <span class="wprh-accordion-icon dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                <span class="wprh-accordion-title"><?php echo esc_html( get_the_title( $resource ) ); ?></span>
                <span class="wprh-accordion-arrow dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="wprh-accordion-content">
                <div class="wprh-accordion-inner">
                    <?php if ( $resource_type ) : ?>
                        <div class="wprh-accordion-meta">
                            <span class="wprh-accordion-type">
                                <strong><?php esc_html_e( 'Type:', 'wp-resource-hub' ); ?></strong>
                                <?php echo esc_html( $resource_type->name ); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ( has_excerpt( $resource ) || $resource->post_content ) : ?>
                        <div class="wprh-accordion-description">
                            <?php echo wp_kses_post( wpautop( get_the_excerpt( $resource ) ) ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="wprh-accordion-actions">
                        <a href="<?php echo esc_url( get_permalink( $resource ) ); ?>" class="wprh-accordion-button">
                            <?php esc_html_e( 'View Resource', 'wp-resource-hub' ); ?>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a video resource item.
     *
     * @since 1.3.0
     *
     * @param \WP_Post      $resource      Resource post.
     * @param \WP_Term|null $resource_type Resource type term.
     * @param string        $type_icon     Type icon class.
     * @return void
     */
    private function render_video_resource( $resource, $resource_type, $type_icon ) {
        $video_provider = get_post_meta( $resource->ID, '_wprh_video_provider', true );
        $video_id       = get_post_meta( $resource->ID, '_wprh_video_id', true );
        $embed_url      = $video_id && $video_provider ? Helpers::get_video_embed_url( $video_id, $video_provider ) : '';
        $duration        = get_post_meta( $resource->ID, '_wprh_video_duration', true );
        ?>
        <div class="wprh-accordion-item wprh-accordion-video wprh-type-video"
            data-resource-id="<?php echo esc_attr( $resource->ID ); ?>">
            <button class="wprh-accordion-trigger" aria-expanded="false">
                <span class="wprh-accordion-number"><?php echo esc_html( $this->resource_index ); ?></span>
                <span class="wprh-accordion-icon dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                <span class="wprh-accordion-title"><?php echo esc_html( get_the_title( $resource ) ); ?></span>
                <?php if ( $duration ) : ?>
                    <span class="wprh-accordion-duration"><?php echo esc_html( $duration ); ?></span>
                <?php endif; ?>
                <span class="wprh-accordion-arrow dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="wprh-accordion-content">
                <div class="wprh-accordion-inner wprh-accordion-video-inner">
                    <?php if ( has_excerpt( $resource ) || $resource->post_content ) : ?>
                        <div class="wprh-accordion-description">
                            <?php echo wp_kses_post( wpautop( get_the_excerpt( $resource ) ) ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="wprh-accordion-video-container">
                        <div class="wprh-accordion-video-thumbnail wprh-video-card"
                            data-video-url="<?php echo esc_attr( $embed_url ); ?>"
                            data-video-title="<?php echo esc_attr( get_the_title( $resource ) ); ?>">
                            <?php
                            $thumbnail = Helpers::get_resource_thumbnail( $resource, 'medium_large' );
                            if ( ! empty( $thumbnail ) ) :
                                echo $thumbnail;
                            else :
                            ?>
                                <div class="wprh-video-default-overlay">
                                    <img src="<?php echo esc_url( WPRH_PLUGIN_URL . 'assets/images/video-overlay.webp' ); ?>" alt=""
                                        class="wprh-overlay-bg">
                                    <div class="wprh-overlay-gradient"></div>
                                    <div class="wprh-overlay-title"><?php echo esc_html( get_the_title( $resource ) ); ?></div>
                                </div>
                            <?php endif; ?>
                            <button class="wprh-play-button" aria-label="<?php esc_attr_e( 'Play video', 'wp-resource-hub' ); ?>">
                                <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="32" cy="32" r="32" fill="rgba(0,0,0,0.7)" />
                                    <path d="M26 20L44 32L26 44V20Z" fill="white" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a nested accordion item.
     *
     * @since 1.3.0
     *
     * @param array $item Item data.
     * @return void
     */
    private function render_nested_accordion( $item ) {
        $title    = isset( $item['title'] ) ? $item['title'] : '';
        $children = isset( $item['children'] ) ? $item['children'] : array();

        if ( empty( $title ) ) {
            return;
        }
        ?>
        <div class="wprh-accordion-item wprh-nested-group">
            <button class="wprh-accordion-trigger" aria-expanded="false">
                <span class="wprh-accordion-title"><?php echo esc_html( $title ); ?></span>
                <span class="wprh-accordion-arrow dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="wprh-accordion-content">
                <div class="wprh-accordion-inner">
                    <?php if ( ! empty( $children ) ) : ?>
                        <?php $this->render_items( $children ); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
