<?php
/**
 * List Table Enhancements class.
 *
 * Enhances the resource list table with custom columns and filters.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * List Table Enhancements class.
 *
 * @since 1.0.0
 */
class ListTableEnhancements {

    /**
     * Singleton instance.
     *
     * @var ListTableEnhancements|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return ListTableEnhancements
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
        // Custom columns.
        add_filter( 'manage_resource_posts_columns', array( $this, 'register_columns' ) );
        add_action( 'manage_resource_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
        add_filter( 'manage_edit-resource_sortable_columns', array( $this, 'sortable_columns' ) );

        // Custom filters.
        add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );
        add_filter( 'parse_query', array( $this, 'filter_query' ) );

        // Admin styles for list table.
        add_action( 'admin_head', array( $this, 'list_table_styles' ) );
    }

    /**
     * Register custom columns.
     *
     * @since 1.0.0
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function register_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            // Insert our columns after title.
            if ( 'title' === $key ) {
                $new_columns[ $key ] = $value;
                $new_columns['resource_type'] = __( 'Type', 'wp-resource-hub' );
                continue;
            }

            // Remove default taxonomy columns - we'll add our own.
            if ( 'taxonomy-resource_type' === $key ) {
                continue;
            }

            $new_columns[ $key ] = $value;
        }

        // Add visibility column before date.
        $date_column = isset( $new_columns['date'] ) ? $new_columns['date'] : null;
        unset( $new_columns['date'] );

        $new_columns['visibility'] = __( 'Visibility', 'wp-resource-hub' );

        if ( $date_column ) {
            $new_columns['date'] = $date_column;
        }

        /**
         * Filter the resource list table columns.
         *
         * @since 1.0.0
         *
         * @param array $new_columns List table columns.
         */
        return apply_filters( 'wprh_list_table_columns', $new_columns );
    }

    /**
     * Render custom column content.
     *
     * @since 1.0.0
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'resource_type':
                $this->render_type_column( $post_id );
                break;

            case 'visibility':
                $this->render_visibility_column( $post_id );
                break;

            default:
                /**
                 * Fires for custom column rendering.
                 *
                 * @since 1.0.0
                 *
                 * @param string $column  Column name.
                 * @param int    $post_id Post ID.
                 */
                do_action( 'wprh_render_list_column', $column, $post_id );
                break;
        }
    }

    /**
     * Render the resource type column.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_type_column( $post_id ) {
        $type = ResourceTypeTax::get_resource_type( $post_id );

        if ( ! $type ) {
            echo '<span class="wprh-no-type">' . esc_html__( 'Not set', 'wp-resource-hub' ) . '</span>';
            return;
        }

        $icon = ResourceTypeTax::get_type_icon( $type );
        $link = add_query_arg(
            array(
                'post_type'     => ResourcePostType::get_post_type(),
                'resource_type' => $type->slug,
            ),
            admin_url( 'edit.php' )
        );

        printf(
            '<a href="%s" class="wprh-type-badge wprh-type-%s"><span class="dashicons %s"></span> %s</a>',
            esc_url( $link ),
            esc_attr( $type->slug ),
            esc_attr( $icon ),
            esc_html( $type->name )
        );
    }

    /**
     * Render the visibility column.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_visibility_column( $post_id ) {
        $post   = get_post( $post_id );
        $status = $post->post_status;

        $visibility_labels = array(
            'publish' => __( 'Public', 'wp-resource-hub' ),
            'private' => __( 'Private', 'wp-resource-hub' ),
            'draft'   => __( 'Draft', 'wp-resource-hub' ),
            'pending' => __( 'Pending', 'wp-resource-hub' ),
        );

        $visibility_icons = array(
            'publish' => 'dashicons-visibility',
            'private' => 'dashicons-hidden',
            'draft'   => 'dashicons-edit',
            'pending' => 'dashicons-clock',
        );

        $label = isset( $visibility_labels[ $status ] ) ? $visibility_labels[ $status ] : ucfirst( $status );
        $icon  = isset( $visibility_icons[ $status ] ) ? $visibility_icons[ $status ] : 'dashicons-admin-post';

        // Check for password protection.
        if ( ! empty( $post->post_password ) ) {
            $label = __( 'Password Protected', 'wp-resource-hub' );
            $icon  = 'dashicons-lock';
        }

        printf(
            '<span class="wprh-visibility wprh-visibility-%s"><span class="dashicons %s"></span> %s</span>',
            esc_attr( $status ),
            esc_attr( $icon ),
            esc_html( $label )
        );
    }

    /**
     * Register sortable columns.
     *
     * @since 1.0.0
     *
     * @param array $columns Sortable columns.
     * @return array
     */
    public function sortable_columns( $columns ) {
        $columns['resource_type'] = 'resource_type';

        /**
         * Filter the sortable columns.
         *
         * @since 1.0.0
         *
         * @param array $columns Sortable columns.
         */
        return apply_filters( 'wprh_sortable_columns', $columns );
    }

    /**
     * Render filter dropdowns.
     *
     * @since 1.0.0
     *
     * @param string $post_type Current post type.
     * @return void
     */
    public function render_filters( $post_type ) {
        if ( ResourcePostType::get_post_type() !== $post_type ) {
            return;
        }

        // Resource Type filter.
        $this->render_taxonomy_filter(
            ResourceTypeTax::get_taxonomy(),
            __( 'All Types', 'wp-resource-hub' )
        );

        // Topic filter.
        $this->render_taxonomy_filter(
            ResourceTopicTax::get_taxonomy(),
            __( 'All Topics', 'wp-resource-hub' )
        );

        // Audience filter.
        $this->render_taxonomy_filter(
            ResourceAudienceTax::get_taxonomy(),
            __( 'All Audiences', 'wp-resource-hub' )
        );

        /**
         * Fires after the default filters are rendered.
         *
         * @since 1.0.0
         *
         * @param string $post_type Current post type.
         */
        do_action( 'wprh_after_list_filters', $post_type );
    }

    /**
     * Render a taxonomy filter dropdown.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy   Taxonomy name.
     * @param string $all_label  Label for "all" option.
     * @return void
     */
    private function render_taxonomy_filter( $taxonomy, $all_label ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            )
        );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return;
        }

        $current = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( $_GET[ $taxonomy ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <select name="<?php echo esc_attr( $taxonomy ); ?>" id="filter-by-<?php echo esc_attr( $taxonomy ); ?>">
            <option value=""><?php echo esc_html( $all_label ); ?></option>
            <?php foreach ( $terms as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current, $term->slug ); ?>>
                    <?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Filter the query based on dropdowns.
     *
     * @since 1.0.0
     *
     * @param \WP_Query $query The query object.
     * @return void
     */
    public function filter_query( $query ) {
        global $pagenow;

        if ( ! is_admin() || 'edit.php' !== $pagenow ) {
            return;
        }

        if ( ! isset( $query->query['post_type'] ) || ResourcePostType::get_post_type() !== $query->query['post_type'] ) {
            return;
        }

        $taxonomies = array(
            ResourceTypeTax::get_taxonomy(),
            ResourceTopicTax::get_taxonomy(),
            ResourceAudienceTax::get_taxonomy(),
        );

        $tax_query = array();

        foreach ( $taxonomies as $taxonomy ) {
            if ( isset( $_GET[ $taxonomy ] ) && ! empty( $_GET[ $taxonomy ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_GET[ $taxonomy ] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                );
            }
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Add list table styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function list_table_styles() {
        global $pagenow, $post_type;

        if ( 'edit.php' !== $pagenow || ResourcePostType::get_post_type() !== $post_type ) {
            return;
        }
        ?>
        <style>
            .wprh-type-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 2px 8px;
                border-radius: 3px;
                background: #f0f0f1;
                text-decoration: none;
                font-size: 12px;
            }
            .wprh-type-badge:hover {
                background: #e0e0e1;
            }
            .wprh-type-badge .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .wprh-type-video { background: #ffe0e0; color: #d63638; }
            .wprh-type-pdf { background: #e0e8ff; color: #2271b1; }
            .wprh-type-download { background: #e0ffe0; color: #00a32a; }
            .wprh-type-external-link { background: #fff8e0; color: #996800; }
            .wprh-type-internal-content { background: #f0e0ff; color: #8c1aff; }

            .wprh-visibility {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-size: 12px;
            }
            .wprh-visibility .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .wprh-visibility-publish { color: #00a32a; }
            .wprh-visibility-private { color: #d63638; }
            .wprh-visibility-draft { color: #646970; }
            .wprh-visibility-pending { color: #996800; }

            .wprh-no-type {
                color: #a7aaad;
                font-style: italic;
            }

            .column-resource_type { width: 140px; }
            .column-visibility { width: 130px; }
        </style>
        <?php
    }
}
