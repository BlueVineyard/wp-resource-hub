<?php
/**
 * Resource Audience Taxonomy class.
 *
 * Handles registration of the resource_audience taxonomy.
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
 * Resource Audience Taxonomy class.
 *
 * @since 1.0.0
 */
class ResourceAudienceTax {

    /**
     * Taxonomy slug.
     *
     * @var string
     */
    const TAXONOMY = 'resource_audience';

    /**
     * Singleton instance.
     *
     * @var ResourceAudienceTax|null
     */
    private static $instance = null;

    /**
     * Default audiences.
     *
     * @var array
     */
    private $default_audiences = array();

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return ResourceAudienceTax
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
        add_action( 'init', array( $this, 'init_default_audiences' ), 0 );
        add_action( 'wprh_activated', array( $this, 'create_default_terms' ) );
    }

    /**
     * Initialize default audiences on init to avoid early translation loading.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_default_audiences() {
        $this->set_default_audiences();
    }

    /**
     * Set default audiences.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function set_default_audiences() {
        $this->default_audiences = array(
            'beginner'     => array(
                'name'        => __( 'Beginner', 'wp-resource-hub' ),
                'slug'        => 'beginner',
                'description' => __( 'Resources suitable for beginners with little to no prior knowledge.', 'wp-resource-hub' ),
            ),
            'intermediate' => array(
                'name'        => __( 'Intermediate', 'wp-resource-hub' ),
                'slug'        => 'intermediate',
                'description' => __( 'Resources for users with some foundational knowledge and experience.', 'wp-resource-hub' ),
            ),
            'advanced'     => array(
                'name'        => __( 'Advanced', 'wp-resource-hub' ),
                'slug'        => 'advanced',
                'description' => __( 'Resources for experienced users seeking in-depth knowledge.', 'wp-resource-hub' ),
            ),
        );

        /**
         * Filter the default resource audiences.
         *
         * @since 1.0.0
         *
         * @param array $default_audiences Default audiences configuration.
         */
        $this->default_audiences = apply_filters( 'wprh_default_resource_audiences', $this->default_audiences );
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
            'name'                       => _x( 'Audiences', 'Taxonomy general name', 'wp-resource-hub' ),
            'singular_name'              => _x( 'Audience', 'Taxonomy singular name', 'wp-resource-hub' ),
            'search_items'               => __( 'Search Audiences', 'wp-resource-hub' ),
            'popular_items'              => __( 'Popular Audiences', 'wp-resource-hub' ),
            'all_items'                  => __( 'All Audiences', 'wp-resource-hub' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Audience', 'wp-resource-hub' ),
            'view_item'                  => __( 'View Audience', 'wp-resource-hub' ),
            'update_item'                => __( 'Update Audience', 'wp-resource-hub' ),
            'add_new_item'               => __( 'Add New Audience', 'wp-resource-hub' ),
            'new_item_name'              => __( 'New Audience Name', 'wp-resource-hub' ),
            'separate_items_with_commas' => __( 'Separate audiences with commas', 'wp-resource-hub' ),
            'add_or_remove_items'        => __( 'Add or remove audiences', 'wp-resource-hub' ),
            'choose_from_most_used'      => __( 'Choose from the most used audiences', 'wp-resource-hub' ),
            'not_found'                  => __( 'No audiences found.', 'wp-resource-hub' ),
            'no_terms'                   => __( 'No audiences', 'wp-resource-hub' ),
            'menu_name'                  => __( 'Audiences', 'wp-resource-hub' ),
            'back_to_items'              => __( '&larr; Back to Audiences', 'wp-resource-hub' ),
        );

        /**
         * Filter the resource audience taxonomy labels.
         *
         * @since 1.0.0
         *
         * @param array $labels Taxonomy labels.
         */
        $labels = apply_filters( 'wprh_resource_audience_labels', $labels );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'rest_base'         => 'resource-audiences',
            'query_var'         => true,
            'rewrite'           => array(
                'slug'         => 'resource-audience',
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );

        /**
         * Filter the resource audience taxonomy arguments.
         *
         * @since 1.0.0
         *
         * @param array $args Taxonomy arguments.
         */
        $args = apply_filters( 'wprh_resource_audience_args', $args );

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
        foreach ( $this->default_audiences as $key => $audience ) {
            if ( ! term_exists( $audience['slug'], self::TAXONOMY ) ) {
                wp_insert_term(
                    $audience['name'],
                    self::TAXONOMY,
                    array(
                        'slug'        => $audience['slug'],
                        'description' => $audience['description'],
                    )
                );
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
     * Get resource audiences for a post.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|null $post Post ID or object.
     * @return \WP_Term[]|false Array of terms or false.
     */
    public static function get_resource_audiences( $post = null ) {
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }

        $terms = get_the_terms( $post->ID, self::TAXONOMY );
        if ( ! $terms || is_wp_error( $terms ) ) {
            return false;
        }

        return $terms;
    }

    /**
     * Get the primary audience for a post.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|null $post Post ID or object.
     * @return \WP_Term|null The primary audience term or null.
     */
    public static function get_primary_audience( $post = null ) {
        $audiences = self::get_resource_audiences( $post );
        if ( ! $audiences ) {
            return null;
        }

        return reset( $audiences );
    }
}
