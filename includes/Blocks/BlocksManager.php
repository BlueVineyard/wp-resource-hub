<?php
/**
 * Blocks Manager class.
 *
 * Handles registration and rendering of Gutenberg blocks.
 *
 * @package WPResourceHub
 * @since   1.2.0
 */

namespace WPResourceHub\Blocks;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\PostTypes\CollectionPostType;
use WPResourceHub\Shortcodes\ResourcesShortcode;
use WPResourceHub\Shortcodes\ResourceShortcode;
use WPResourceHub\Shortcodes\CollectionShortcode;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Blocks Manager class.
 *
 * @since 1.2.0
 */
class BlocksManager {

    /**
     * Singleton instance.
     *
     * @var BlocksManager|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.2.0
     *
     * @return BlocksManager
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
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
    }

    /**
     * Register block category.
     *
     * @since 1.2.0
     *
     * @param array                    $categories Block categories.
     * @param \WP_Block_Editor_Context $context    Block editor context.
     * @return array
     */
    public function register_block_category( $categories, $context ) {
        return array_merge(
            array(
                array(
                    'slug'  => 'wp-resource-hub',
                    'title' => __( 'Resource Hub', 'wp-resource-hub' ),
                    'icon'  => 'welcome-learn-more',
                ),
            ),
            $categories
        );
    }

    /**
     * Register blocks.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public function register_blocks() {
        // Resources Grid Block.
        register_block_type(
            'wp-resource-hub/resources-grid',
            array(
                'api_version'     => 2,
                'editor_script'   => 'wprh-blocks-editor',
                'editor_style'    => 'wprh-blocks-editor',
                'style'           => 'wprh-frontend',
                'render_callback' => array( $this, 'render_resources_grid' ),
                'attributes'      => array(
                    'layout'             => array( 'type' => 'string', 'default' => 'grid' ),
                    'columns'            => array( 'type' => 'number', 'default' => 3 ),
                    'limit'              => array( 'type' => 'number', 'default' => 12 ),
                    'type'               => array( 'type' => 'string', 'default' => '' ),
                    'topic'              => array( 'type' => 'string', 'default' => '' ),
                    'audience'           => array( 'type' => 'string', 'default' => '' ),
                    'orderby'            => array( 'type' => 'string', 'default' => 'date' ),
                    'order'              => array( 'type' => 'string', 'default' => 'DESC' ),
                    'showFilters'        => array( 'type' => 'boolean', 'default' => true ),
                    'showTypeFilter'     => array( 'type' => 'boolean', 'default' => true ),
                    'showTopicFilter'    => array( 'type' => 'boolean', 'default' => true ),
                    'showAudienceFilter' => array( 'type' => 'boolean', 'default' => true ),
                    'showSearch'         => array( 'type' => 'boolean', 'default' => true ),
                    'showPagination'     => array( 'type' => 'boolean', 'default' => true ),
                    'featuredOnly'       => array( 'type' => 'boolean', 'default' => false ),
                    'className'          => array( 'type' => 'string', 'default' => '' ),
                ),
            )
        );

        // Single Resource Block.
        register_block_type(
            'wp-resource-hub/resource',
            array(
                'api_version'     => 2,
                'editor_script'   => 'wprh-blocks-editor',
                'editor_style'    => 'wprh-blocks-editor',
                'style'           => 'wprh-frontend',
                'render_callback' => array( $this, 'render_resource' ),
                'attributes'      => array(
                    'resourceId'  => array( 'type' => 'number', 'default' => 0 ),
                    'display'     => array( 'type' => 'string', 'default' => 'card' ),
                    'showTitle'   => array( 'type' => 'boolean', 'default' => true ),
                    'showMeta'    => array( 'type' => 'boolean', 'default' => true ),
                    'showImage'   => array( 'type' => 'boolean', 'default' => true ),
                    'className'   => array( 'type' => 'string', 'default' => '' ),
                ),
            )
        );

        // Collection Block.
        register_block_type(
            'wp-resource-hub/collection',
            array(
                'api_version'     => 2,
                'editor_script'   => 'wprh-blocks-editor',
                'editor_style'    => 'wprh-blocks-editor',
                'style'           => 'wprh-frontend',
                'render_callback' => array( $this, 'render_collection' ),
                'attributes'      => array(
                    'collectionId'    => array( 'type' => 'number', 'default' => 0 ),
                    'layout'          => array( 'type' => 'string', 'default' => '' ),
                    'showTitle'       => array( 'type' => 'boolean', 'default' => true ),
                    'showDescription' => array( 'type' => 'boolean', 'default' => true ),
                    'showProgress'    => array( 'type' => 'boolean', 'default' => false ),
                    'showCount'       => array( 'type' => 'boolean', 'default' => true ),
                    'className'       => array( 'type' => 'string', 'default' => '' ),
                ),
            )
        );
    }

    /**
     * Enqueue editor assets.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public function enqueue_editor_assets() {
        // Editor script.
        wp_enqueue_script(
            'wprh-blocks-editor',
            WPRH_PLUGIN_URL . 'assets/js/blocks-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
            WPRH_VERSION,
            true
        );

        // Editor styles.
        wp_enqueue_style(
            'wprh-blocks-editor',
            WPRH_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            WPRH_VERSION
        );

        // Localize script with data.
        wp_localize_script(
            'wprh-blocks-editor',
            'wprhBlocks',
            array(
                'resources'   => $this->get_resources_for_editor(),
                'collections' => $this->get_collections_for_editor(),
                'types'       => $this->get_terms_for_editor( 'resource_type' ),
                'topics'      => $this->get_terms_for_editor( 'resource_topic' ),
                'audiences'   => $this->get_terms_for_editor( 'resource_audience' ),
                'i18n'        => array(
                    'selectResource'   => __( 'Select a Resource', 'wp-resource-hub' ),
                    'selectCollection' => __( 'Select a Collection', 'wp-resource-hub' ),
                    'noResources'      => __( 'No resources found.', 'wp-resource-hub' ),
                    'noCollections'    => __( 'No collections found.', 'wp-resource-hub' ),
                    'searchResources'  => __( 'Search resources...', 'wp-resource-hub' ),
                ),
            )
        );
    }

    /**
     * Get resources for editor.
     *
     * @since 1.2.0
     *
     * @return array
     */
    private function get_resources_for_editor() {
        $resources = get_posts(
            array(
                'post_type'      => ResourcePostType::get_post_type(),
                'posts_per_page' => 100,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $data = array();
        foreach ( $resources as $resource ) {
            $data[] = array(
                'id'    => $resource->ID,
                'title' => $resource->post_title,
            );
        }

        return $data;
    }

    /**
     * Get collections for editor.
     *
     * @since 1.2.0
     *
     * @return array
     */
    private function get_collections_for_editor() {
        $collections = get_posts(
            array(
                'post_type'      => CollectionPostType::get_post_type(),
                'posts_per_page' => 100,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $data = array();
        foreach ( $collections as $collection ) {
            $data[] = array(
                'id'    => $collection->ID,
                'title' => $collection->post_title,
            );
        }

        return $data;
    }

    /**
     * Get terms for editor.
     *
     * @since 1.2.0
     *
     * @param string $taxonomy Taxonomy name.
     * @return array
     */
    private function get_terms_for_editor( $taxonomy ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        $data = array();
        foreach ( $terms as $term ) {
            $data[] = array(
                'slug'  => $term->slug,
                'name'  => $term->name,
                'count' => $term->count,
            );
        }

        return $data;
    }

    /**
     * Render Resources Grid block.
     *
     * @since 1.2.0
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public function render_resources_grid( $attributes ) {
        $atts = array(
            'layout'               => $attributes['layout'],
            'columns'              => $attributes['columns'],
            'limit'                => $attributes['limit'],
            'type'                 => $attributes['type'],
            'topic'                => $attributes['topic'],
            'audience'             => $attributes['audience'],
            'orderby'              => $attributes['orderby'],
            'order'                => $attributes['order'],
            'show_filters'         => $attributes['showFilters'] ? 'true' : 'false',
            'show_type_filter'     => $attributes['showTypeFilter'] ? 'true' : 'false',
            'show_topic_filter'    => $attributes['showTopicFilter'] ? 'true' : 'false',
            'show_audience_filter' => $attributes['showAudienceFilter'] ? 'true' : 'false',
            'show_search'          => $attributes['showSearch'] ? 'true' : 'false',
            'show_pagination'      => $attributes['showPagination'] ? 'true' : 'false',
            'featured_only'        => $attributes['featuredOnly'] ? 'true' : 'false',
            'class'                => $attributes['className'],
        );

        return ResourcesShortcode::get_instance()->render( $atts );
    }

    /**
     * Render Resource block.
     *
     * @since 1.2.0
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public function render_resource( $attributes ) {
        if ( empty( $attributes['resourceId'] ) ) {
            return '<div class="wprh-block-placeholder">' .
                   esc_html__( 'Please select a resource.', 'wp-resource-hub' ) .
                   '</div>';
        }

        $atts = array(
            'id'         => $attributes['resourceId'],
            'display'    => $attributes['display'],
            'show_title' => $attributes['showTitle'] ? 'true' : 'false',
            'show_meta'  => $attributes['showMeta'] ? 'true' : 'false',
            'show_image' => $attributes['showImage'] ? 'true' : 'false',
            'class'      => $attributes['className'],
        );

        return ResourceShortcode::get_instance()->render( $atts );
    }

    /**
     * Render Collection block.
     *
     * @since 1.2.0
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public function render_collection( $attributes ) {
        if ( empty( $attributes['collectionId'] ) ) {
            return '<div class="wprh-block-placeholder">' .
                   esc_html__( 'Please select a collection.', 'wp-resource-hub' ) .
                   '</div>';
        }

        $atts = array(
            'id'               => $attributes['collectionId'],
            'layout'           => $attributes['layout'],
            'show_title'       => $attributes['showTitle'] ? 'true' : 'false',
            'show_description' => $attributes['showDescription'] ? 'true' : 'false',
            'show_progress'    => $attributes['showProgress'] ? 'true' : 'false',
            'show_count'       => $attributes['showCount'] ? 'true' : 'false',
            'class'            => $attributes['className'],
        );

        return CollectionShortcode::get_instance()->render( $atts );
    }
}
