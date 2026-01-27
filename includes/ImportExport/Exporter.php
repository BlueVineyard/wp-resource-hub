<?php
/**
 * Exporter class.
 *
 * Handles exporting resources to CSV and JSON formats.
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
 * Exporter class.
 *
 * @since 1.1.0
 */
class Exporter {

    /**
     * Singleton instance.
     *
     * @var Exporter|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return Exporter
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
        add_action( 'admin_init', array( $this, 'handle_export' ) );
    }

    /**
     * Handle export request.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function handle_export() {
        if ( ! isset( $_GET['wprh_export'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wprh_export' ) ) {
            wp_die( esc_html__( 'Invalid nonce.', 'wp-resource-hub' ) );
        }

        if ( ! current_user_can( 'export' ) ) {
            wp_die( esc_html__( 'You do not have permission to export.', 'wp-resource-hub' ) );
        }

        $format = isset( $_GET['format'] ) ? sanitize_text_field( $_GET['format'] ) : 'csv';

        switch ( $format ) {
            case 'json':
                $this->export_json();
                break;

            case 'csv':
            default:
                $this->export_csv();
                break;
        }
    }

    /**
     * Export resources as CSV.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function export_csv() {
        $resources = $this->get_resources_data();

        if ( empty( $resources ) ) {
            wp_die( esc_html__( 'No resources to export.', 'wp-resource-hub' ) );
        }

        $filename = 'resources-export-' . date( 'Y-m-d-His' ) . '.csv';

        // Set headers.
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // Add BOM for Excel UTF-8 compatibility.
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        // Write header row.
        fputcsv( $output, array_keys( $resources[0] ) );

        // Write data rows.
        foreach ( $resources as $resource ) {
            fputcsv( $output, $resource );
        }

        fclose( $output );
        exit;
    }

    /**
     * Export resources as JSON.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function export_json() {
        $resources = $this->get_resources_data( true );

        if ( empty( $resources ) ) {
            wp_die( esc_html__( 'No resources to export.', 'wp-resource-hub' ) );
        }

        $filename = 'resources-export-' . date( 'Y-m-d-His' ) . '.json';

        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        echo wp_json_encode(
            array(
                'export_date' => current_time( 'c' ),
                'site_url'    => home_url(),
                'plugin_version' => WPRH_VERSION,
                'resources'   => $resources,
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    /**
     * Get resources data for export.
     *
     * @since 1.1.0
     *
     * @param bool $full_data Whether to include full data for JSON export.
     * @return array
     */
    private function get_resources_data( $full_data = false ) {
        $resources = ResourcePostType::get_resources(
            array(
                'post_status' => array( 'publish', 'draft', 'private' ),
            )
        );

        $data = array();

        foreach ( $resources as $resource ) {
            $resource_type = ResourceTypeTax::get_resource_type_slug( $resource );
            $topics        = wp_get_post_terms( $resource->ID, ResourceTopicTax::get_taxonomy(), array( 'fields' => 'names' ) );
            $audiences     = wp_get_post_terms( $resource->ID, ResourceAudienceTax::get_taxonomy(), array( 'fields' => 'names' ) );

            $row = array(
                'id'            => $resource->ID,
                'title'         => $resource->post_title,
                'slug'          => $resource->post_name,
                'status'        => $resource->post_status,
                'date'          => $resource->post_date,
                'modified'      => $resource->post_modified,
                'resource_type' => $resource_type,
                'topics'        => is_array( $topics ) ? implode( ', ', $topics ) : '',
                'audiences'     => is_array( $audiences ) ? implode( ', ', $audiences ) : '',
                'excerpt'       => $resource->post_excerpt,
            );

            // Add type-specific fields.
            switch ( $resource_type ) {
                case 'video':
                    $row['video_provider'] = MetaBoxes::get_meta( $resource->ID, 'video_provider' );
                    $row['video_url']      = MetaBoxes::get_meta( $resource->ID, 'video_url' );
                    $row['video_id']       = MetaBoxes::get_meta( $resource->ID, 'video_id' );
                    $row['video_duration'] = MetaBoxes::get_meta( $resource->ID, 'video_duration' );
                    break;

                case 'pdf':
                    $pdf_file = MetaBoxes::get_meta( $resource->ID, 'pdf_file' );
                    $row['pdf_file_url']    = $pdf_file ? wp_get_attachment_url( $pdf_file ) : '';
                    $row['pdf_file_size']   = MetaBoxes::get_meta( $resource->ID, 'pdf_file_size' );
                    $row['pdf_page_count']  = MetaBoxes::get_meta( $resource->ID, 'pdf_page_count' );
                    $row['pdf_viewer_mode'] = MetaBoxes::get_meta( $resource->ID, 'pdf_viewer_mode' );
                    break;

                case 'download':
                    $download_file = MetaBoxes::get_meta( $resource->ID, 'download_file' );
                    $row['download_file_url']  = $download_file ? wp_get_attachment_url( $download_file ) : '';
                    $row['download_file_size'] = MetaBoxes::get_meta( $resource->ID, 'download_file_size' );
                    $row['download_version']   = MetaBoxes::get_meta( $resource->ID, 'download_version' );
                    break;

                case 'external-link':
                    $row['external_url']  = MetaBoxes::get_meta( $resource->ID, 'external_url' );
                    $row['open_new_tab']  = MetaBoxes::get_meta( $resource->ID, 'open_new_tab' );
                    break;

                case 'internal-content':
                    $row['summary']      = MetaBoxes::get_meta( $resource->ID, 'summary' );
                    $row['reading_time'] = MetaBoxes::get_meta( $resource->ID, 'reading_time' );
                    $row['show_toc']     = MetaBoxes::get_meta( $resource->ID, 'show_toc' );
                    $row['show_related'] = MetaBoxes::get_meta( $resource->ID, 'show_related' );
                    break;
            }

            // For JSON export, include content.
            if ( $full_data ) {
                $row['content'] = $resource->post_content;
                $row['topics']  = is_array( $topics ) ? $topics : array();
                $row['audiences'] = is_array( $audiences ) ? $audiences : array();

                // Include featured image URL.
                $thumbnail_id = get_post_thumbnail_id( $resource->ID );
                $row['featured_image'] = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '';
            }

            /**
             * Filter the export row data for a resource.
             *
             * @since 1.1.0
             *
             * @param array    $row       Row data.
             * @param \WP_Post $resource  Resource post object.
             * @param bool     $full_data Whether this is full data export.
             */
            $data[] = apply_filters( 'wprh_export_resource_data', $row, $resource, $full_data );
        }

        return $data;
    }

    /**
     * Get export URL.
     *
     * @since 1.1.0
     *
     * @param string $format Export format (csv or json).
     * @return string
     */
    public static function get_export_url( $format = 'csv' ) {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'wprh_export' => '1',
                    'format'      => $format,
                ),
                admin_url( 'edit.php?post_type=resource' )
            ),
            'wprh_export'
        );
    }
}
