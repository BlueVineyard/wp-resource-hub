<?php
/**
 * Resource Topic Taxonomy class.
 *
 * Handles registration of the resource_topic taxonomy.
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
 * Resource Topic Taxonomy class.
 *
 * @since 1.0.0
 */
class ResourceTopicTax {

    /**
     * Taxonomy slug.
     *
     * @var string
     */
    const TAXONOMY = 'resource_topic';

    /**
     * Singleton instance.
     *
     * @var ResourceTopicTax|null
     */
    private static $instance = null;

    /**
     * Default topics.
     *
     * @var array
     */
    private $default_topics = array();

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return ResourceTopicTax
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
        add_action( 'init', array( $this, 'init_default_topics' ), 0 );
        add_action( 'wprh_activated', array( $this, 'create_default_terms' ) );
    }

    /**
     * Initialize default topics on init to avoid early translation loading.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_default_topics() {
        $this->set_default_topics();
    }

    /**
     * Set default topics.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function set_default_topics() {
        $this->default_topics = array(
            'design'      => array(
                'name'        => __( 'Design', 'wp-resource-hub' ),
                'slug'        => 'design',
                'description' => __( 'Design-related resources including UI/UX, graphics, and visual design.', 'wp-resource-hub' ),
            ),
            'marketing'   => array(
                'name'        => __( 'Marketing', 'wp-resource-hub' ),
                'slug'        => 'marketing',
                'description' => __( 'Marketing strategies, campaigns, and promotional content.', 'wp-resource-hub' ),
            ),
            'wordpress'   => array(
                'name'        => __( 'WordPress', 'wp-resource-hub' ),
                'slug'        => 'wordpress',
                'description' => __( 'WordPress development, themes, plugins, and best practices.', 'wp-resource-hub' ),
            ),
            'engineering' => array(
                'name'        => __( 'Engineering', 'wp-resource-hub' ),
                'slug'        => 'engineering',
                'description' => __( 'Software engineering, coding practices, and technical resources.', 'wp-resource-hub' ),
            ),
        );

        /**
         * Filter the default resource topics.
         *
         * @since 1.0.0
         *
         * @param array $default_topics Default topics configuration.
         */
        $this->default_topics = apply_filters( 'wprh_default_resource_topics', $this->default_topics );
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
            'name'                       => _x( 'Topics', 'Taxonomy general name', 'wp-resource-hub' ),
            'singular_name'              => _x( 'Topic', 'Taxonomy singular name', 'wp-resource-hub' ),
            'search_items'               => __( 'Search Topics', 'wp-resource-hub' ),
            'popular_items'              => __( 'Popular Topics', 'wp-resource-hub' ),
            'all_items'                  => __( 'All Topics', 'wp-resource-hub' ),
            'parent_item'                => __( 'Parent Topic', 'wp-resource-hub' ),
            'parent_item_colon'          => __( 'Parent Topic:', 'wp-resource-hub' ),
            'edit_item'                  => __( 'Edit Topic', 'wp-resource-hub' ),
            'view_item'                  => __( 'View Topic', 'wp-resource-hub' ),
            'update_item'                => __( 'Update Topic', 'wp-resource-hub' ),
            'add_new_item'               => __( 'Add New Topic', 'wp-resource-hub' ),
            'new_item_name'              => __( 'New Topic Name', 'wp-resource-hub' ),
            'separate_items_with_commas' => __( 'Separate topics with commas', 'wp-resource-hub' ),
            'add_or_remove_items'        => __( 'Add or remove topics', 'wp-resource-hub' ),
            'choose_from_most_used'      => __( 'Choose from the most used topics', 'wp-resource-hub' ),
            'not_found'                  => __( 'No topics found.', 'wp-resource-hub' ),
            'no_terms'                   => __( 'No topics', 'wp-resource-hub' ),
            'menu_name'                  => __( 'Topics', 'wp-resource-hub' ),
            'back_to_items'              => __( '&larr; Back to Topics', 'wp-resource-hub' ),
        );

        /**
         * Filter the resource topic taxonomy labels.
         *
         * @since 1.0.0
         *
         * @param array $labels Taxonomy labels.
         */
        $labels = apply_filters( 'wprh_resource_topic_labels', $labels );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'rest_base'         => 'resource-topics',
            'query_var'         => true,
            'rewrite'           => array(
                'slug'         => 'resource-topic',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        );

        /**
         * Filter the resource topic taxonomy arguments.
         *
         * @since 1.0.0
         *
         * @param array $args Taxonomy arguments.
         */
        $args = apply_filters( 'wprh_resource_topic_args', $args );

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
        foreach ( $this->default_topics as $key => $topic ) {
            if ( ! term_exists( $topic['slug'], self::TAXONOMY ) ) {
                wp_insert_term(
                    $topic['name'],
                    self::TAXONOMY,
                    array(
                        'slug'        => $topic['slug'],
                        'description' => $topic['description'],
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
     * Get resource topics for a post.
     *
     * @since 1.0.0
     *
     * @param int|\WP_Post|null $post Post ID or object.
     * @return \WP_Term[]|false Array of terms or false.
     */
    public static function get_resource_topics( $post = null ) {
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
}
