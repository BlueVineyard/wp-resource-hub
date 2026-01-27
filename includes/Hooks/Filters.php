<?php
/**
 * Filters class.
 *
 * Registers and manages plugin filters for extensibility.
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
 * Filters class.
 *
 * @since 1.0.0
 */
class Filters {

    /**
     * Singleton instance.
     *
     * @var Filters|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return Filters
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
        $this->register_filters();
    }

    /**
     * Register plugin filters.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function register_filters() {
        // Content filters.
        add_filter( 'the_content', array( $this, 'filter_resource_content' ), 5 );
        add_filter( 'get_the_excerpt', array( $this, 'filter_resource_excerpt' ), 10, 2 );

        // Archive title filter.
        add_filter( 'get_the_archive_title', array( $this, 'filter_archive_title' ) );

        // Body class filter.
        add_filter( 'body_class', array( $this, 'filter_body_class' ) );

        // Post class filter.
        add_filter( 'post_class', array( $this, 'filter_post_class' ), 10, 3 );
    }

    /**
     * Filter resource content for specific types.
     *
     * @since 1.0.0
     *
     * @param string $content Post content.
     * @return string
     */
    public function filter_resource_content( $content ) {
        if ( ! is_singular( ResourcePostType::get_post_type() ) ) {
            return $content;
        }

        global $post;
        $resource_type = ResourceTypeTax::get_resource_type_slug( $post );

        /**
         * Filter the resource content based on type.
         *
         * @since 1.0.0
         *
         * @param string   $content       The post content.
         * @param string   $resource_type The resource type slug.
         * @param \WP_Post $post          The post object.
         */
        return apply_filters( 'wprh_resource_content', $content, $resource_type, $post );
    }

    /**
     * Filter resource excerpt.
     *
     * @since 1.0.0
     *
     * @param string   $excerpt Post excerpt.
     * @param \WP_Post $post    Post object.
     * @return string
     */
    public function filter_resource_excerpt( $excerpt, $post ) {
        if ( ResourcePostType::get_post_type() !== $post->post_type ) {
            return $excerpt;
        }

        // Check for custom summary.
        $summary = MetaBoxes::get_meta( $post->ID, 'summary' );

        if ( ! empty( $summary ) ) {
            $excerpt = $summary;
        }

        /**
         * Filter the resource excerpt.
         *
         * @since 1.0.0
         *
         * @param string   $excerpt The excerpt.
         * @param \WP_Post $post    The post object.
         */
        return apply_filters( 'wprh_resource_excerpt', $excerpt, $post );
    }

    /**
     * Filter archive title for resource archives.
     *
     * @since 1.0.0
     *
     * @param string $title Archive title.
     * @return string
     */
    public function filter_archive_title( $title ) {
        if ( is_post_type_archive( ResourcePostType::get_post_type() ) ) {
            $title = post_type_archive_title( '', false );

            /**
             * Filter the resource archive title.
             *
             * @since 1.0.0
             *
             * @param string $title The archive title.
             */
            $title = apply_filters( 'wprh_archive_title', $title );
        }

        if ( is_tax( ResourceTypeTax::get_taxonomy() ) ) {
            $term  = get_queried_object();
            /* translators: %s: Resource type name */
            $title = sprintf( __( 'Resource Type: %s', 'wp-resource-hub' ), $term->name );
        }

        return $title;
    }

    /**
     * Filter body classes for resource pages.
     *
     * @since 1.0.0
     *
     * @param array $classes Body classes.
     * @return array
     */
    public function filter_body_class( $classes ) {
        if ( is_singular( ResourcePostType::get_post_type() ) ) {
            $classes[] = 'wprh-single-resource';

            global $post;
            $resource_type = ResourceTypeTax::get_resource_type_slug( $post );

            if ( $resource_type ) {
                $classes[] = 'wprh-resource-type-' . sanitize_html_class( $resource_type );
            }
        }

        if ( is_post_type_archive( ResourcePostType::get_post_type() ) ) {
            $classes[] = 'wprh-archive-resource';
        }

        if ( is_tax( ResourceTypeTax::get_taxonomy() ) ) {
            $classes[] = 'wprh-tax-resource-type';
        }

        return $classes;
    }

    /**
     * Filter post classes for resource items.
     *
     * @since 1.0.0
     *
     * @param array  $classes Post classes.
     * @param array  $class   Additional classes.
     * @param int    $post_id Post ID.
     * @return array
     */
    public function filter_post_class( $classes, $class, $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post || ResourcePostType::get_post_type() !== $post->post_type ) {
            return $classes;
        }

        $classes[] = 'wprh-resource';

        $resource_type = ResourceTypeTax::get_resource_type_slug( $post );
        if ( $resource_type ) {
            $classes[] = 'wprh-resource-type-' . sanitize_html_class( $resource_type );
        }

        /**
         * Filter the resource post classes.
         *
         * @since 1.0.0
         *
         * @param array    $classes Resource post classes.
         * @param \WP_Post $post    The post object.
         */
        return apply_filters( 'wprh_post_class', $classes, $post );
    }
}
