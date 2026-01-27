<?php
/**
 * Collection Post Type class.
 *
 * Handles registration and configuration of the Collection custom post type.
 * Collections allow grouping resources into curated playlists/sets.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\PostTypes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Collection Post Type class.
 *
 * @since 1.1.0
 */
class CollectionPostType {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'resource_collection';

    /**
     * Singleton instance.
     *
     * @var CollectionPostType|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return CollectionPostType
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
        add_action( 'init', array( $this, 'register' ), 5 );
    }

    /**
     * Register the custom post type.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register() {
        $labels = array(
            'name'                  => _x( 'Collections', 'Post type general name', 'wp-resource-hub' ),
            'singular_name'         => _x( 'Collection', 'Post type singular name', 'wp-resource-hub' ),
            'menu_name'             => _x( 'Collections', 'Admin menu text', 'wp-resource-hub' ),
            'name_admin_bar'        => _x( 'Collection', 'Add New on toolbar', 'wp-resource-hub' ),
            'add_new'               => __( 'Add New', 'wp-resource-hub' ),
            'add_new_item'          => __( 'Add New Collection', 'wp-resource-hub' ),
            'new_item'              => __( 'New Collection', 'wp-resource-hub' ),
            'edit_item'             => __( 'Edit Collection', 'wp-resource-hub' ),
            'view_item'             => __( 'View Collection', 'wp-resource-hub' ),
            'all_items'             => __( 'Collections', 'wp-resource-hub' ),
            'search_items'          => __( 'Search Collections', 'wp-resource-hub' ),
            'parent_item_colon'     => __( 'Parent Collections:', 'wp-resource-hub' ),
            'not_found'             => __( 'No collections found.', 'wp-resource-hub' ),
            'not_found_in_trash'    => __( 'No collections found in Trash.', 'wp-resource-hub' ),
            'featured_image'        => _x( 'Collection Cover Image', 'Overrides the "Featured Image" phrase', 'wp-resource-hub' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'wp-resource-hub' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'wp-resource-hub' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'wp-resource-hub' ),
            'archives'              => _x( 'Collection archives', 'The post type archive label', 'wp-resource-hub' ),
            'insert_into_item'      => _x( 'Insert into collection', 'Overrides the "Insert into post" phrase', 'wp-resource-hub' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this collection', 'Overrides the "Uploaded to this post" phrase', 'wp-resource-hub' ),
            'filter_items_list'     => _x( 'Filter collections list', 'Screen reader text', 'wp-resource-hub' ),
            'items_list_navigation' => _x( 'Collections list navigation', 'Screen reader text', 'wp-resource-hub' ),
            'items_list'            => _x( 'Collections list', 'Screen reader text', 'wp-resource-hub' ),
        );

        /**
         * Filter the collection post type labels.
         *
         * @since 1.1.0
         *
         * @param array $labels Post type labels.
         */
        $labels = apply_filters( 'wprh_collection_post_type_labels', $labels );

        $supports = array(
            'title',
            'editor',
            'thumbnail',
            'excerpt',
            'revisions',
        );

        /**
         * Filter the collection post type supports.
         *
         * @since 1.1.0
         *
         * @param array $supports Post type supports array.
         */
        $supports = apply_filters( 'wprh_collection_post_type_supports', $supports );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=resource',
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => $this->get_rewrite_slug(),
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => $supports,
            'show_in_rest'        => true,
            'rest_base'           => 'resource-collections',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        /**
         * Filter the collection post type arguments.
         *
         * @since 1.1.0
         *
         * @param array $args Post type arguments.
         */
        $args = apply_filters( 'wprh_collection_post_type_args', $args );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Get the rewrite slug.
     *
     * @since 1.1.0
     *
     * @return string
     */
    private function get_rewrite_slug() {
        $slug = get_option( 'wprh_collection_slug', 'resource-collection' );

        /**
         * Filter the collection post type rewrite slug.
         *
         * @since 1.1.0
         *
         * @param string $slug The rewrite slug.
         */
        return apply_filters( 'wprh_collection_post_type_slug', $slug );
    }

    /**
     * Get the post type slug.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public static function get_post_type() {
        return self::POST_TYPE;
    }

    /**
     * Check if a post is a collection.
     *
     * @since 1.1.0
     *
     * @param int|\WP_Post|null $post Post ID or object. Defaults to current post.
     * @return bool
     */
    public static function is_collection( $post = null ) {
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }
        return self::POST_TYPE === $post->post_type;
    }

    /**
     * Get resources in a collection.
     *
     * @since 1.1.0
     *
     * @param int $collection_id Collection post ID.
     * @return int[] Array of resource post IDs.
     */
    public static function get_collection_resources( $collection_id ) {
        $resource_ids = get_post_meta( $collection_id, '_wprh_collection_resources', true );

        if ( ! is_array( $resource_ids ) ) {
            return array();
        }

        /**
         * Filter the resources in a collection.
         *
         * @since 1.1.0
         *
         * @param int[] $resource_ids   Array of resource IDs.
         * @param int   $collection_id  Collection post ID.
         */
        return apply_filters( 'wprh_collection_resources', $resource_ids, $collection_id );
    }

    /**
     * Set resources in a collection.
     *
     * @since 1.1.0
     *
     * @param int   $collection_id Collection post ID.
     * @param int[] $resource_ids  Array of resource post IDs.
     * @return bool
     */
    public static function set_collection_resources( $collection_id, $resource_ids ) {
        $resource_ids = array_map( 'absint', $resource_ids );
        $resource_ids = array_filter( $resource_ids );
        $resource_ids = array_unique( $resource_ids );

        return update_post_meta( $collection_id, '_wprh_collection_resources', $resource_ids );
    }

    /**
     * Add a resource to a collection.
     *
     * @since 1.1.0
     *
     * @param int $collection_id Collection post ID.
     * @param int $resource_id   Resource post ID.
     * @return bool
     */
    public static function add_resource_to_collection( $collection_id, $resource_id ) {
        $resources = self::get_collection_resources( $collection_id );

        if ( in_array( $resource_id, $resources, true ) ) {
            return true;
        }

        $resources[] = absint( $resource_id );

        return self::set_collection_resources( $collection_id, $resources );
    }

    /**
     * Remove a resource from a collection.
     *
     * @since 1.1.0
     *
     * @param int $collection_id Collection post ID.
     * @param int $resource_id   Resource post ID.
     * @return bool
     */
    public static function remove_resource_from_collection( $collection_id, $resource_id ) {
        $resources = self::get_collection_resources( $collection_id );
        $key       = array_search( absint( $resource_id ), $resources, true );

        if ( false === $key ) {
            return true;
        }

        unset( $resources[ $key ] );

        return self::set_collection_resources( $collection_id, $resources );
    }

    /**
     * Get collections containing a resource.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return \WP_Post[] Array of collection posts.
     */
    public static function get_resource_collections( $resource_id ) {
        global $wpdb;

        $collection_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_wprh_collection_resources'
                AND meta_value LIKE %s",
                '%i:' . absint( $resource_id ) . ';%'
            )
        );

        if ( empty( $collection_ids ) ) {
            // Try serialized array format.
            $collection_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_wprh_collection_resources'
                    AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)",
                    '%"' . absint( $resource_id ) . '"%',
                    '%i:' . absint( $resource_id ) . ';%',
                    '%:' . absint( $resource_id ) . ';%'
                )
            );
        }

        if ( empty( $collection_ids ) ) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => self::POST_TYPE,
                'post__in'       => $collection_ids,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            )
        );
    }

    /**
     * Get collection resource count.
     *
     * @since 1.1.0
     *
     * @param int $collection_id Collection post ID.
     * @return int
     */
    public static function get_resource_count( $collection_id ) {
        return count( self::get_collection_resources( $collection_id ) );
    }
}
