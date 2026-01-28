<?php
/**
 * Resource Type Taxonomy class.
 *
 * Handles registration of the resource_type taxonomy.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Taxonomies;

use WPResourceHub\PostTypes\ResourcePostType;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resource Type Taxonomy class.
 *
 * @since 1.0.0
 */
class ResourceTypeTax {

    /**
     * Taxonomy slug.
     *
     * @var string
     */
    const TAXONOMY = 'resource_type';

    /**
     * Singleton instance.
     *
     * @var ResourceTypeTax|null
     */
    private static $instance = null;

    /**
     * Default resource types.
     *
     * @var array
     */
    private $default_types = array();

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return ResourceTypeTax
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
        add_action( 'init', array( $this, 'init_default_types' ), 0 );
        add_action( 'wprh_activated', array( $this, 'create_default_terms' ) );
    }

    /**
     * Initialize default types on init to avoid early translation loading.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_default_types() {
        $this->set_default_types();
    }

    /**
     * Set default resource types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function set_default_types() {
        $this->default_types = array(
            'video'            => array(
                'name'        => __( 'Video', 'wp-resource-hub' ),
                'slug'        => 'video',
                'description' => __( 'Video content from YouTube, Vimeo, or local uploads.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-video-alt3',
            ),
            'pdf'              => array(
                'name'        => __( 'PDF', 'wp-resource-hub' ),
                'slug'        => 'pdf',
                'description' => __( 'PDF documents for viewing or download.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-pdf',
            ),
            'download'         => array(
                'name'        => __( 'Download', 'wp-resource-hub' ),
                'slug'        => 'download',
                'description' => __( 'Downloadable files and resources.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-download',
            ),
            'external_link'    => array(
                'name'        => __( 'External Link', 'wp-resource-hub' ),
                'slug'        => 'external-link',
                'description' => __( 'Links to external websites and resources.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-external',
            ),
            'internal_content' => array(
                'name'        => __( 'Internal Content', 'wp-resource-hub' ),
                'slug'        => 'internal-content',
                'description' => __( 'Article-style content hosted on this site.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-text-page',
            ),
        );

        /**
         * Filter the default resource types.
         *
         * @since 1.0.0
         *
         * @param array $default_types Default resource types configuration.
         */
        $this->default_types = apply_filters( 'wprh_default_resource_types', $this->default_types );
    }

    /**
     * Register the taxonomy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register() {
        $labels = array(
            'name'                       => _x( 'Resource Types', 'Taxonomy general name', 'wp-resource-hub' ),
            'singular_name'              => _x( 'Resource Type', 'Taxonomy singular name', 'wp-resource-hub' ),
            'search_items'               => __( 'Search Resource Types', 'wp-resource-hub' ),
            'popular_items'              => __( 'Popular Resource Types', 'wp-resource-hub' ),
            'all_items'                  => __( 'All Resource Types', 'wp-resource-hub' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Resource Type', 'wp-resource-hub' ),
            'update_item'                => __( 'Update Resource Type', 'wp-resource-hub' ),
            'add_new_item'               => __( 'Add New Resource Type', 'wp-resource-hub' ),
            'new_item_name'              => __( 'New Resource Type Name', 'wp-resource-hub' ),
            'separate_items_with_commas' => __( 'Separate resource types with commas', 'wp-resource-hub' ),
            'add_or_remove_items'        => __( 'Add or remove resource types', 'wp-resource-hub' ),
            'choose_from_most_used'      => __( 'Choose from the most used resource types', 'wp-resource-hub' ),
            'not_found'                  => __( 'No resource types found.', 'wp-resource-hub' ),
            'menu_name'                  => __( 'Resource Types', 'wp-resource-hub' ),
            'back_to_items'              => __( '&larr; Back to Resource Types', 'wp-resource-hub' ),
        );

        /**
         * Filter the resource type taxonomy labels.
         *
         * @since 1.0.0
         *
         * @param array $labels Taxonomy labels.
         */
        $labels = apply_filters( 'wprh_resource_type_labels', $labels );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rest_base'         => 'resource-types',
            'query_var'         => true,
            'rewrite'           => array(
                'slug'         => 'resource-type',
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );

        /**
         * Filter the resource type taxonomy arguments.
         *
         * @since 1.0.0
         *
         * @param array $args Taxonomy arguments.
         */
        $args = apply_filters( 'wprh_resource_type_args', $args );

        register_taxonomy(
            self::TAXONOMY,
            ResourcePostType::get_post_type(),
            $args
        );
    }

    /**
     * Create default taxonomy terms.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_default_terms() {
        foreach ( $this->default_types as $key => $type ) {
            if ( ! term_exists( $type['slug'], self::TAXONOMY ) ) {
                $term = wp_insert_term(
                    $type['name'],
                    self::TAXONOMY,
                    array(
                        'slug'        => $type['slug'],
                        'description' => $type['description'],
                    )
                );

                if ( ! is_wp_error( $term ) && isset( $type['icon'] ) ) {
                    update_term_meta( $term['term_id'], 'wprh_icon', $type['icon'] );
                }
            }
        }
    }

    /**
     * Get the taxonomy slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_taxonomy() {
        return self::TAXONOMY;
    }

    /**
     * Get default resource types.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_default_types() {
        return $this->default_types;
    }

    /**
     * Get resource type by slug.
     *
     * @since 1.0.0
     *
     * @param string $slug The resource type slug.
     * @return array|null Type configuration or null if not found.
     */
    public function get_type_by_slug( $slug ) {
        foreach ( $this->default_types as $key => $type ) {
            if ( $type['slug'] === $slug || $key === $slug ) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Get resource type for a post.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|null $post Post ID or object.
     * @return \WP_Term|null The resource type term or null.
     */
    public static function get_resource_type( $post = null ) {
        $post = get_post( $post );
        if ( ! $post ) {
            return null;
        }

        $terms = get_the_terms( $post->ID, self::TAXONOMY );
        if ( ! $terms || is_wp_error( $terms ) ) {
            return null;
        }

        return reset( $terms );
    }

    /**
     * Get resource type slug for a post.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|null $post Post ID or object.
     * @return string|null The resource type slug or null.
     */
    public static function get_resource_type_slug( $post = null ) {
        $type = self::get_resource_type( $post );
        return $type ? $type->slug : null;
    }

    /**
     * Get icon for a resource type term.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Term $term Term ID or object.
     * @return string The icon class.
     */
    public static function get_type_icon( $term ) {
        $term_id = is_object( $term ) ? $term->term_id : $term;
        $icon    = get_term_meta( $term_id, 'wprh_icon', true );

        if ( ! $icon ) {
            $icon = 'dashicons-media-default';
        }

        /**
         * Filter the resource type icon.
         *
         * @since 1.0.0
         *
         * @param string $icon    The icon class.
         * @param int    $term_id The term ID.
         */
        return apply_filters( 'wprh_resource_type_icon', $icon, $term_id );
    }
}
