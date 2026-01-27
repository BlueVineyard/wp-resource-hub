<?php
/**
 * Importer class.
 *
 * Handles importing resources from CSV and JSON formats.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\ImportExport;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;
use WPResourceHub\Admin\MetaBoxes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Importer class.
 *
 * @since 1.1.0
 */
class Importer {

    /**
     * Singleton instance.
     *
     * @var Importer|null
     */
    private static $instance = null;

    /**
     * Import results.
     *
     * @var array
     */
    private $results = array(
        'created'  => 0,
        'updated'  => 0,
        'skipped'  => 0,
        'errors'   => array(),
    );

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return Importer
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
        add_action( 'admin_init', array( $this, 'handle_import' ) );
    }

    /**
     * Handle import request.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function handle_import() {
        if ( ! isset( $_POST['wprh_import'] ) || ! isset( $_POST['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wprh_import' ) ) {
            wp_die( esc_html__( 'Invalid nonce.', 'wp-resource-hub' ) );
        }

        if ( ! current_user_can( 'import' ) ) {
            wp_die( esc_html__( 'You do not have permission to import.', 'wp-resource-hub' ) );
        }

        if ( ! isset( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_die( esc_html__( 'Please upload a valid file.', 'wp-resource-hub' ) );
        }

        $file = $_FILES['import_file'];
        $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        switch ( $extension ) {
            case 'json':
                $this->import_json( $file['tmp_name'] );
                break;

            case 'csv':
                $this->import_csv( $file['tmp_name'] );
                break;

            default:
                wp_die( esc_html__( 'Invalid file format. Please upload a CSV or JSON file.', 'wp-resource-hub' ) );
        }

        // Store results in transient for display.
        set_transient( 'wprh_import_results', $this->results, 60 );

        // Redirect to import page with results.
        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'     => 'wprh-import-export',
                    'imported' => '1',
                ),
                admin_url( 'edit.php?post_type=resource' )
            )
        );
        exit;
    }

    /**
     * Import from JSON file.
     *
     * @since 1.1.0
     *
     * @param string $file_path Path to the JSON file.
     * @return void
     */
    private function import_json( $file_path ) {
        $content = file_get_contents( $file_path );
        $data = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->results['errors'][] = __( 'Invalid JSON file.', 'wp-resource-hub' );
            return;
        }

        if ( ! isset( $data['resources'] ) || ! is_array( $data['resources'] ) ) {
            $this->results['errors'][] = __( 'No resources found in the JSON file.', 'wp-resource-hub' );
            return;
        }

        foreach ( $data['resources'] as $resource_data ) {
            $this->import_resource( $resource_data );
        }
    }

    /**
     * Import from CSV file.
     *
     * @since 1.1.0
     *
     * @param string $file_path Path to the CSV file.
     * @return void
     */
    private function import_csv( $file_path ) {
        $handle = fopen( $file_path, 'r' );

        if ( ! $handle ) {
            $this->results['errors'][] = __( 'Could not read CSV file.', 'wp-resource-hub' );
            return;
        }

        // Read header row.
        $headers = fgetcsv( $handle );

        if ( ! $headers ) {
            $this->results['errors'][] = __( 'CSV file appears to be empty.', 'wp-resource-hub' );
            fclose( $handle );
            return;
        }

        // Remove BOM if present.
        $headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $headers[0] );

        // Process data rows.
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( count( $row ) !== count( $headers ) ) {
                $this->results['errors'][] = sprintf(
                    /* translators: %d: Row number */
                    __( 'Row %d has an incorrect number of columns.', 'wp-resource-hub' ),
                    $this->results['created'] + $this->results['updated'] + $this->results['skipped'] + 2
                );
                $this->results['skipped']++;
                continue;
            }

            $resource_data = array_combine( $headers, $row );
            $this->import_resource( $resource_data );
        }

        fclose( $handle );
    }

    /**
     * Import a single resource.
     *
     * @since 1.1.0
     *
     * @param array $data Resource data.
     * @return void
     */
    private function import_resource( $data ) {
        // Validate required fields.
        if ( empty( $data['title'] ) ) {
            $this->results['errors'][] = __( 'Resource skipped: missing title.', 'wp-resource-hub' );
            $this->results['skipped']++;
            return;
        }

        // Check if updating existing resource.
        $existing_id = 0;
        $update_mode = isset( $_POST['import_mode'] ) && $_POST['import_mode'] === 'update';

        if ( $update_mode && ! empty( $data['id'] ) ) {
            $existing = get_post( absint( $data['id'] ) );
            if ( $existing && ResourcePostType::get_post_type() === $existing->post_type ) {
                $existing_id = $existing->ID;
            }
        }

        // Prepare post data.
        $post_data = array(
            'post_type'    => ResourcePostType::get_post_type(),
            'post_title'   => sanitize_text_field( $data['title'] ),
            'post_name'    => ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '',
            'post_status'  => ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'draft',
            'post_excerpt' => ! empty( $data['excerpt'] ) ? wp_kses_post( $data['excerpt'] ) : '',
            'post_content' => ! empty( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
        );

        if ( $existing_id ) {
            $post_data['ID'] = $existing_id;
            $post_id = wp_update_post( $post_data, true );

            if ( is_wp_error( $post_id ) ) {
                $this->results['errors'][] = sprintf(
                    /* translators: 1: Resource title, 2: Error message */
                    __( 'Failed to update "%1$s": %2$s', 'wp-resource-hub' ),
                    $data['title'],
                    $post_id->get_error_message()
                );
                $this->results['skipped']++;
                return;
            }

            $this->results['updated']++;
        } else {
            $post_id = wp_insert_post( $post_data, true );

            if ( is_wp_error( $post_id ) ) {
                $this->results['errors'][] = sprintf(
                    /* translators: 1: Resource title, 2: Error message */
                    __( 'Failed to create "%1$s": %2$s', 'wp-resource-hub' ),
                    $data['title'],
                    $post_id->get_error_message()
                );
                $this->results['skipped']++;
                return;
            }

            $this->results['created']++;
        }

        // Set resource type.
        if ( ! empty( $data['resource_type'] ) ) {
            $resource_type = sanitize_text_field( $data['resource_type'] );
            update_post_meta( $post_id, MetaBoxes::get_meta_prefix() . 'resource_type', $resource_type );
            wp_set_object_terms( $post_id, $resource_type, ResourceTypeTax::get_taxonomy() );
        }

        // Set topics.
        if ( ! empty( $data['topics'] ) ) {
            $topics = is_array( $data['topics'] ) ? $data['topics'] : explode( ',', $data['topics'] );
            $topics = array_map( 'trim', $topics );
            $topics = array_filter( $topics );
            wp_set_object_terms( $post_id, $topics, ResourceTopicTax::get_taxonomy() );
        }

        // Set audiences.
        if ( ! empty( $data['audiences'] ) ) {
            $audiences = is_array( $data['audiences'] ) ? $data['audiences'] : explode( ',', $data['audiences'] );
            $audiences = array_map( 'trim', $audiences );
            $audiences = array_filter( $audiences );
            wp_set_object_terms( $post_id, $audiences, ResourceAudienceTax::get_taxonomy() );
        }

        // Set type-specific meta.
        $this->import_type_meta( $post_id, $data );

        /**
         * Fires after a resource is imported.
         *
         * @since 1.1.0
         *
         * @param int   $post_id    The created/updated post ID.
         * @param array $data       The import data.
         * @param bool  $is_update  Whether this was an update.
         */
        do_action( 'wprh_resource_imported', $post_id, $data, $existing_id > 0 );
    }

    /**
     * Import type-specific meta data.
     *
     * @since 1.1.0
     *
     * @param int   $post_id Post ID.
     * @param array $data    Import data.
     * @return void
     */
    private function import_type_meta( $post_id, $data ) {
        $prefix = MetaBoxes::get_meta_prefix();

        // Video fields.
        $video_fields = array( 'video_provider', 'video_url', 'video_id', 'video_duration' );
        foreach ( $video_fields as $field ) {
            if ( isset( $data[ $field ] ) && '' !== $data[ $field ] ) {
                update_post_meta( $post_id, $prefix . $field, sanitize_text_field( $data[ $field ] ) );
            }
        }

        // PDF fields.
        if ( isset( $data['pdf_file_size'] ) && '' !== $data['pdf_file_size'] ) {
            update_post_meta( $post_id, $prefix . 'pdf_file_size', sanitize_text_field( $data['pdf_file_size'] ) );
        }
        if ( isset( $data['pdf_page_count'] ) && '' !== $data['pdf_page_count'] ) {
            update_post_meta( $post_id, $prefix . 'pdf_page_count', absint( $data['pdf_page_count'] ) );
        }
        if ( isset( $data['pdf_viewer_mode'] ) && '' !== $data['pdf_viewer_mode'] ) {
            update_post_meta( $post_id, $prefix . 'pdf_viewer_mode', sanitize_text_field( $data['pdf_viewer_mode'] ) );
        }

        // Download fields.
        if ( isset( $data['download_file_size'] ) && '' !== $data['download_file_size'] ) {
            update_post_meta( $post_id, $prefix . 'download_file_size', sanitize_text_field( $data['download_file_size'] ) );
        }
        if ( isset( $data['download_version'] ) && '' !== $data['download_version'] ) {
            update_post_meta( $post_id, $prefix . 'download_version', sanitize_text_field( $data['download_version'] ) );
        }

        // External link fields.
        if ( isset( $data['external_url'] ) && '' !== $data['external_url'] ) {
            update_post_meta( $post_id, $prefix . 'external_url', esc_url_raw( $data['external_url'] ) );
        }
        if ( isset( $data['open_new_tab'] ) ) {
            update_post_meta( $post_id, $prefix . 'open_new_tab', $data['open_new_tab'] ? '1' : '0' );
        }

        // Internal content fields.
        if ( isset( $data['summary'] ) && '' !== $data['summary'] ) {
            update_post_meta( $post_id, $prefix . 'summary', sanitize_textarea_field( $data['summary'] ) );
        }
        if ( isset( $data['reading_time'] ) && '' !== $data['reading_time'] ) {
            update_post_meta( $post_id, $prefix . 'reading_time', absint( $data['reading_time'] ) );
        }
        if ( isset( $data['show_toc'] ) ) {
            update_post_meta( $post_id, $prefix . 'show_toc', $data['show_toc'] ? '1' : '0' );
        }
        if ( isset( $data['show_related'] ) ) {
            update_post_meta( $post_id, $prefix . 'show_related', $data['show_related'] ? '1' : '0' );
        }
    }

    /**
     * Get import results.
     *
     * @since 1.1.0
     *
     * @return array
     */
    public function get_results() {
        return $this->results;
    }

    /**
     * Get import results from transient.
     *
     * @since 1.1.0
     *
     * @return array|false
     */
    public static function get_stored_results() {
        $results = get_transient( 'wprh_import_results' );
        delete_transient( 'wprh_import_results' );
        return $results;
    }
}
