<?php
/**
 * Resource Post Type class.
 *
 * Handles registration and configuration of the Resource custom post type.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\PostTypes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resource Post Type class.
 *
 * @since 1.0.0
 */
class ResourcePostType {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'resource';

    /**
     * Singleton instance.
     *
     * @var ResourcePostType|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return ResourcePostType
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
        // Private constructor for singleton.
    }

    /**
     * Register the custom post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register() {
        $labels = array(
            'name'                  => _x( 'Resources', 'Post type general name', 'wp-resource-hub' ),
            'singular_name'         => _x( 'Resource', 'Post type singular name', 'wp-resource-hub' ),
            'menu_name'             => _x( 'Resources Hub', 'Admin menu text', 'wp-resource-hub' ),
            'name_admin_bar'        => _x( 'Resource', 'Add New on toolbar', 'wp-resource-hub' ),
            'add_new'               => __( 'Add New', 'wp-resource-hub' ),
            'add_new_item'          => __( 'Add New Resource', 'wp-resource-hub' ),
            'new_item'              => __( 'New Resource', 'wp-resource-hub' ),
            'edit_item'             => __( 'Edit Resource', 'wp-resource-hub' ),
            'view_item'             => __( 'View Resource', 'wp-resource-hub' ),
            'all_items'             => __( 'All Resources', 'wp-resource-hub' ),
            'search_items'          => __( 'Search Resources', 'wp-resource-hub' ),
            'parent_item_colon'     => __( 'Parent Resources:', 'wp-resource-hub' ),
            'not_found'             => __( 'No resources found.', 'wp-resource-hub' ),
            'not_found_in_trash'    => __( 'No resources found in Trash.', 'wp-resource-hub' ),
            'featured_image'        => _x( 'Resource Cover Image', 'Overrides the "Featured Image" phrase', 'wp-resource-hub' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'wp-resource-hub' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'wp-resource-hub' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'wp-resource-hub' ),
            'archives'              => _x( 'Resource archives', 'The post type archive label', 'wp-resource-hub' ),
            'insert_into_item'      => _x( 'Insert into resource', 'Overrides the "Insert into post" phrase', 'wp-resource-hub' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this resource', 'Overrides the "Uploaded to this post" phrase', 'wp-resource-hub' ),
            'filter_items_list'     => _x( 'Filter resources list', 'Screen reader text', 'wp-resource-hub' ),
            'items_list_navigation' => _x( 'Resources list navigation', 'Screen reader text', 'wp-resource-hub' ),
            'items_list'            => _x( 'Resources list', 'Screen reader text', 'wp-resource-hub' ),
        );

        /**
         * Filter the resource post type labels.
         *
         * @since 1.0.0
         *
         * @param array $labels Post type labels.
         */
        $labels = apply_filters( 'wprh_post_type_labels', $labels );

        $supports = array(
            'title',
            'editor',
            'thumbnail',
            'custom-fields',
            'excerpt',
            'revisions',
        );

        /**
         * Filter the resource post type supports.
         *
         * @since 1.0.0
         *
         * @param array $supports Post type supports array.
         */
        $supports = apply_filters( 'wprh_post_type_supports', $supports );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => $this->get_rewrite_slug(),
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-welcome-learn-more',
            'supports'            => $supports,
            'show_in_rest'        => true,
            'rest_base'           => 'resources',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'template'            => array(),
            'template_lock'       => false,
        );

        /**
         * Filter the resource post type arguments.
         *
         * @since 1.0.0
         *
         * @param array $args Post type arguments.
         */
        $args = apply_filters( 'wprh_post_type_args', $args );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Get the rewrite slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private function get_rewrite_slug() {
        $slug = get_option( 'wprh_resource_slug', 'resource' );

        /**
         * Filter the resource post type rewrite slug.
         *
         * @since 1.0.0
         *
         * @param string $slug The rewrite slug.
         */
        return apply_filters( 'wprh_post_type_slug', $slug );
    }

    /**
     * Get the post type slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_post_type() {
        return self::POST_TYPE;
    }

    /**
     * Check if a post is a resource.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|null $post Post ID or object. Defaults to current post.
     * @return bool
     */
    public static function is_resource( $post = null ) {
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }
        return self::POST_TYPE === $post->post_type;
    }

    /**
     * Get all resources.
     *
     * @since 1.0.0
     *
     * @param array $args Optional. Query arguments.
     * @return \WP_Post[]
     */
    public static function get_resources( $args = array() ) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        /**
         * Filter the query arguments for getting resources.
         *
         * @since 1.0.0
         *
         * @param array $args Query arguments.
         */
        $args = apply_filters( 'wprh_get_resources_args', $args );

        return get_posts( $args );
    }
}
