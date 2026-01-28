<?php
/**
 * Accordion Post Type class.
 *
 * Handles registration and configuration of the Accordion custom post type.
 * Accordions allow building custom nested accordion structures with resources.
 *
 * @package WPResourceHub
 * @since   1.3.0
 */

namespace WPResourceHub\PostTypes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Accordion Post Type class.
 *
 * @since 1.3.0
 */
class AccordionPostType {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'resource_accordion';

    /**
     * Singleton instance.
     *
     * @var AccordionPostType|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.3.0
     *
     * @return AccordionPostType
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
        add_action( 'init', array( $this, 'register' ), 5 );
    }

    /**
     * Register the custom post type.
     *
     * @since 1.3.0
     *
     * @return void
     */
    public function register() {
        $labels = array(
            'name'                  => _x( 'Accordions', 'Post type general name', 'wp-resource-hub' ),
            'singular_name'         => _x( 'Accordion', 'Post type singular name', 'wp-resource-hub' ),
            'menu_name'             => _x( 'Accordions', 'Admin menu text', 'wp-resource-hub' ),
            'name_admin_bar'        => _x( 'Accordion', 'Add New on toolbar', 'wp-resource-hub' ),
            'add_new'               => __( 'Add New', 'wp-resource-hub' ),
            'add_new_item'          => __( 'Add New Accordion', 'wp-resource-hub' ),
            'new_item'              => __( 'New Accordion', 'wp-resource-hub' ),
            'edit_item'             => __( 'Edit Accordion', 'wp-resource-hub' ),
            'view_item'             => __( 'View Accordion', 'wp-resource-hub' ),
            'all_items'             => __( 'Accordions', 'wp-resource-hub' ),
            'search_items'          => __( 'Search Accordions', 'wp-resource-hub' ),
            'parent_item_colon'     => __( 'Parent Accordions:', 'wp-resource-hub' ),
            'not_found'             => __( 'No accordions found.', 'wp-resource-hub' ),
            'not_found_in_trash'    => __( 'No accordions found in Trash.', 'wp-resource-hub' ),
            'archives'              => _x( 'Accordion archives', 'The post type archive label', 'wp-resource-hub' ),
            'insert_into_item'      => _x( 'Insert into accordion', 'Overrides the "Insert into post" phrase', 'wp-resource-hub' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this accordion', 'Overrides the "Uploaded to this post" phrase', 'wp-resource-hub' ),
            'filter_items_list'     => _x( 'Filter accordions list', 'Screen reader text', 'wp-resource-hub' ),
            'items_list_navigation' => _x( 'Accordions list navigation', 'Screen reader text', 'wp-resource-hub' ),
            'items_list'            => _x( 'Accordions list', 'Screen reader text', 'wp-resource-hub' ),
        );

        /**
         * Filter the accordion post type labels.
         *
         * @since 1.3.0
         *
         * @param array $labels Post type labels.
         */
        $labels = apply_filters( 'wprh_accordion_post_type_labels', $labels );

        $supports = array(
            'title',
            'editor',
        );

        /**
         * Filter the accordion post type supports.
         *
         * @since 1.3.0
         *
         * @param array $supports Post type supports array.
         */
        $supports = apply_filters( 'wprh_accordion_post_type_supports', $supports );

        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=resource',
            'query_var'             => true,
            'rewrite'               => array(
                'slug'       => $this->get_rewrite_slug(),
                'with_front' => false,
            ),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'menu_icon'             => 'dashicons-editor-justify',
            'supports'              => $supports,
            'show_in_rest'          => true,
            'rest_base'             => 'resource-accordions',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        /**
         * Filter the accordion post type arguments.
         *
         * @since 1.3.0
         *
         * @param array $args Post type arguments.
         */
        $args = apply_filters( 'wprh_accordion_post_type_args', $args );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Get the rewrite slug.
     *
     * @since 1.3.0
     *
     * @return string
     */
    private function get_rewrite_slug() {
        $slug = get_option( 'wprh_accordion_slug', 'resource-accordion' );

        /**
         * Filter the accordion post type rewrite slug.
         *
         * @since 1.3.0
         *
         * @param string $slug The rewrite slug.
         */
        return apply_filters( 'wprh_accordion_post_type_slug', $slug );
    }

    /**
     * Get the post type slug.
     *
     * @since 1.3.0
     *
     * @return string
     */
    public static function get_post_type() {
        return self::POST_TYPE;
    }

    /**
     * Check if a post is an accordion.
     *
     * @since 1.3.0
     *
     * @param int|\WP_Post|null $post Post ID or object. Defaults to current post.
     * @return bool
     */
    public static function is_accordion( $post = null ) {
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }
        return self::POST_TYPE === $post->post_type;
    }

    /**
     * Get accordion structure.
     *
     * @since 1.3.0
     *
     * @param int $accordion_id Accordion post ID.
     * @return array
     */
    public static function get_accordion_structure( $accordion_id ) {
        $structure = get_post_meta( $accordion_id, '_wprh_accordion_structure', true );

        if ( ! is_array( $structure ) ) {
            return array();
        }

        /**
         * Filter the accordion structure.
         *
         * @since 1.3.0
         *
         * @param array $structure    The accordion structure.
         * @param int   $accordion_id Accordion post ID.
         */
        return apply_filters( 'wprh_accordion_structure', $structure, $accordion_id );
    }

    /**
     * Set accordion structure.
     *
     * @since 1.3.0
     *
     * @param int   $accordion_id Accordion post ID.
     * @param array $structure    The accordion structure.
     * @return bool
     */
    public static function set_accordion_structure( $accordion_id, $structure ) {
        return update_post_meta( $accordion_id, '_wprh_accordion_structure', $structure );
    }
}
