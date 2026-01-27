<?php
/**
 * Template Loader class.
 *
 * Handles loading of custom templates with theme override support.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Frontend;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template Loader class.
 *
 * @since 1.0.0
 */
class TemplateLoader {

    /**
     * Singleton instance.
     *
     * @var TemplateLoader|null
     */
    private static $instance = null;

    /**
     * Theme template directory.
     *
     * @var string
     */
    private $theme_template_dir = 'wp-resource-hub';

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return TemplateLoader
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
        add_filter( 'template_include', array( $this, 'template_loader' ) );
    }

    /**
     * Load custom templates.
     *
     * @since 1.0.0
     *
     * @param string $template The template path.
     * @return string
     */
    public function template_loader( $template ) {
        $file = '';

        // Single resource template.
        if ( is_singular( ResourcePostType::get_post_type() ) ) {
            $file = $this->get_single_template();
        }

        // Resource archive template.
        if ( is_post_type_archive( ResourcePostType::get_post_type() ) ) {
            $file = $this->get_archive_template();
        }

        // Taxonomy archives.
        if ( is_tax( ResourceTypeTax::get_taxonomy() ) ) {
            $file = $this->get_taxonomy_template( ResourceTypeTax::get_taxonomy() );
        }

        if ( is_tax( ResourceTopicTax::get_taxonomy() ) ) {
            $file = $this->get_taxonomy_template( ResourceTopicTax::get_taxonomy() );
        }

        if ( is_tax( ResourceAudienceTax::get_taxonomy() ) ) {
            $file = $this->get_taxonomy_template( ResourceAudienceTax::get_taxonomy() );
        }

        if ( $file ) {
            /**
             * Filter the loaded template path.
             *
             * @since 1.0.0
             *
             * @param string $file     Template file path.
             * @param string $template Original template path.
             */
            return apply_filters( 'wprh_template_loader', $file, $template );
        }

        return $template;
    }

    /**
     * Get single resource template.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private function get_single_template() {
        global $post;

        $resource_type = ResourceTypeTax::get_resource_type_slug( $post );
        $templates     = array();

        // Type-specific template.
        if ( $resource_type ) {
            $templates[] = "single-resource-{$resource_type}.php";
        }

        // Generic single template.
        $templates[] = 'single-resource.php';

        return $this->locate_template( $templates );
    }

    /**
     * Get archive template.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private function get_archive_template() {
        $templates = array(
            'archive-resource.php',
        );

        return $this->locate_template( $templates );
    }

    /**
     * Get taxonomy archive template.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy name.
     * @return string
     */
    private function get_taxonomy_template( $taxonomy ) {
        $term = get_queried_object();

        $templates = array();

        // Term-specific template.
        if ( $term ) {
            $templates[] = "taxonomy-{$taxonomy}-{$term->slug}.php";
        }

        // Taxonomy template.
        $templates[] = "taxonomy-{$taxonomy}.php";

        // Fallback to archive.
        $templates[] = 'archive-resource.php';

        return $this->locate_template( $templates );
    }

    /**
     * Locate a template.
     *
     * Looks in theme directory first, then falls back to plugin templates.
     *
     * @since 1.0.0
     *
     * @param array $templates Template files to search for.
     * @return string Template path.
     */
    public function locate_template( $templates ) {
        $located = '';

        foreach ( $templates as $template ) {
            if ( empty( $template ) ) {
                continue;
            }

            // Look in theme/child-theme first.
            $theme_paths = array(
                get_stylesheet_directory() . '/' . $this->theme_template_dir . '/' . $template,
                get_template_directory() . '/' . $this->theme_template_dir . '/' . $template,
            );

            foreach ( $theme_paths as $theme_path ) {
                if ( file_exists( $theme_path ) ) {
                    $located = $theme_path;
                    break 2;
                }
            }

            // Fall back to plugin templates.
            $plugin_path = WPRH_PLUGIN_DIR . 'templates/' . $template;
            if ( file_exists( $plugin_path ) ) {
                $located = $plugin_path;
                break;
            }
        }

        /**
         * Filter the located template path.
         *
         * @since 1.0.0
         *
         * @param string $located   Located template path.
         * @param array  $templates Templates searched for.
         */
        return apply_filters( 'wprh_locate_template', $located, $templates );
    }

    /**
     * Load a template part.
     *
     * @since 1.0.0
     *
     * @param string $slug Template slug.
     * @param string $name Template name (optional).
     * @param array  $args Additional arguments to pass to the template.
     * @return void
     */
    public function get_template_part( $slug, $name = '', $args = array() ) {
        $templates = array();

        if ( $name ) {
            $templates[] = "{$slug}-{$name}.php";
        }

        $templates[] = "{$slug}.php";

        $template = $this->locate_template( $templates );

        if ( $template ) {
            // Make args available in template.
            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            }

            /**
             * Fires before a template part is loaded.
             *
             * @since 1.0.0
             *
             * @param string $slug     Template slug.
             * @param string $name     Template name.
             * @param string $template Template path.
             */
            do_action( 'wprh_before_template_part', $slug, $name, $template );

            include $template;

            /**
             * Fires after a template part is loaded.
             *
             * @since 1.0.0
             *
             * @param string $slug     Template slug.
             * @param string $name     Template name.
             * @param string $template Template path.
             */
            do_action( 'wprh_after_template_part', $slug, $name, $template );
        }
    }

    /**
     * Get template part contents.
     *
     * @since 1.0.0
     *
     * @param string $slug Template slug.
     * @param string $name Template name (optional).
     * @param array  $args Additional arguments.
     * @return string Template contents.
     */
    public function get_template_part_content( $slug, $name = '', $args = array() ) {
        ob_start();
        $this->get_template_part( $slug, $name, $args );
        return ob_get_clean();
    }

    /**
     * Get the theme template directory.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_theme_template_dir() {
        /**
         * Filter the theme template directory.
         *
         * @since 1.0.0
         *
         * @param string $theme_template_dir Theme template directory name.
         */
        return apply_filters( 'wprh_theme_template_dir', $this->theme_template_dir );
    }

    /**
     * Get plugin template directory.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_plugin_template_dir() {
        return WPRH_PLUGIN_DIR . 'templates/';
    }
}
