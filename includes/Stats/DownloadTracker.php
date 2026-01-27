<?php
/**
 * Download Tracker class.
 *
 * Handles secure file downloads with tracking.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\Stats;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Admin\MetaBoxes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Download Tracker class.
 *
 * @since 1.1.0
 */
class DownloadTracker {

    /**
     * Singleton instance.
     *
     * @var DownloadTracker|null
     */
    private static $instance = null;

    /**
     * Download endpoint.
     *
     * @var string
     */
    const ENDPOINT = 'wprh-download';

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return DownloadTracker
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
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_action( 'template_redirect', array( $this, 'handle_download' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    /**
     * Add rewrite rules for download endpoint.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^' . self::ENDPOINT . '/([0-9]+)/?$',
            'index.php?' . self::ENDPOINT . '=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars.
     *
     * @since 1.1.0
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = self::ENDPOINT;
        return $vars;
    }

    /**
     * Handle download request.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function handle_download() {
        $resource_id = get_query_var( self::ENDPOINT );

        if ( ! $resource_id ) {
            return;
        }

        $resource_id = absint( $resource_id );
        $post = get_post( $resource_id );

        // Validate post.
        if ( ! $post || ResourcePostType::get_post_type() !== $post->post_type ) {
            wp_die(
                esc_html__( 'Invalid resource.', 'wp-resource-hub' ),
                esc_html__( 'Download Error', 'wp-resource-hub' ),
                array( 'response' => 404 )
            );
        }

        // Check post status.
        if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $resource_id ) ) {
            wp_die(
                esc_html__( 'This resource is not available.', 'wp-resource-hub' ),
                esc_html__( 'Download Error', 'wp-resource-hub' ),
                array( 'response' => 403 )
            );
        }

        // Check access restrictions.
        if ( ! $this->can_access_download( $resource_id ) ) {
            wp_die(
                esc_html__( 'You do not have permission to download this resource.', 'wp-resource-hub' ),
                esc_html__( 'Access Denied', 'wp-resource-hub' ),
                array( 'response' => 403 )
            );
        }

        // Get the file.
        $file_id = $this->get_file_attachment_id( $resource_id );

        if ( ! $file_id ) {
            wp_die(
                esc_html__( 'No file available for download.', 'wp-resource-hub' ),
                esc_html__( 'Download Error', 'wp-resource-hub' ),
                array( 'response' => 404 )
            );
        }

        $file_path = get_attached_file( $file_id );

        if ( ! $file_path || ! file_exists( $file_path ) ) {
            wp_die(
                esc_html__( 'File not found.', 'wp-resource-hub' ),
                esc_html__( 'Download Error', 'wp-resource-hub' ),
                array( 'response' => 404 )
            );
        }

        // Track the download.
        $file_url = wp_get_attachment_url( $file_id );

        /**
         * Fires when a download is tracked.
         *
         * @since 1.1.0
         *
         * @param int    $resource_id Resource post ID.
         * @param string $file_url    File URL.
         */
        do_action( 'wprh_download_tracked', $resource_id, $file_url );

        /**
         * Fires before a file download is served.
         *
         * @since 1.1.0
         *
         * @param int    $resource_id Resource post ID.
         * @param string $file_path   Full path to the file.
         */
        do_action( 'wprh_before_download', $resource_id, $file_path );

        // Serve the file.
        $this->serve_file( $file_path );
    }

    /**
     * Get the file attachment ID for a resource.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return int|null Attachment ID or null.
     */
    private function get_file_attachment_id( $resource_id ) {
        $resource_type = get_post_meta( $resource_id, '_wprh_resource_type', true );

        switch ( $resource_type ) {
            case 'pdf':
                return (int) MetaBoxes::get_meta( $resource_id, 'pdf_file' );

            case 'download':
                return (int) MetaBoxes::get_meta( $resource_id, 'download_file' );

            default:
                /**
                 * Filter the file attachment ID for custom resource types.
                 *
                 * @since 1.1.0
                 *
                 * @param int|null $attachment_id Attachment ID.
                 * @param int      $resource_id   Resource post ID.
                 * @param string   $resource_type Resource type.
                 */
                return apply_filters( 'wprh_download_file_id', null, $resource_id, $resource_type );
        }
    }

    /**
     * Check if the current user can access the download.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return bool
     */
    private function can_access_download( $resource_id ) {
        /**
         * Filter whether a user can access a download.
         *
         * @since 1.1.0
         *
         * @param bool $can_access  Whether the user can access.
         * @param int  $resource_id Resource post ID.
         */
        return apply_filters( 'wprh_can_access_download', true, $resource_id );
    }

    /**
     * Serve a file for download.
     *
     * @since 1.1.0
     *
     * @param string $file_path Full path to the file.
     * @return void
     */
    private function serve_file( $file_path ) {
        $filename = basename( $file_path );
        $filesize = filesize( $file_path );
        $mime_type = mime_content_type( $file_path );

        if ( ! $mime_type ) {
            $mime_type = 'application/octet-stream';
        }

        // Clean output buffer.
        if ( ob_get_level() ) {
            ob_end_clean();
        }

        // Set headers.
        nocache_headers();
        header( 'Content-Type: ' . $mime_type );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . $filesize );
        header( 'Content-Transfer-Encoding: binary' );

        // Serve the file.
        readfile( $file_path );
        exit;
    }

    /**
     * Get the download URL for a resource.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return string Download URL.
     */
    public static function get_download_url( $resource_id ) {
        $url = home_url( '/' . self::ENDPOINT . '/' . absint( $resource_id ) . '/' );

        /**
         * Filter the download URL.
         *
         * @since 1.1.0
         *
         * @param string $url         Download URL.
         * @param int    $resource_id Resource post ID.
         */
        return apply_filters( 'wprh_download_url', $url, $resource_id );
    }

    /**
     * Get direct file URL (without tracking).
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return string|null File URL or null.
     */
    public static function get_direct_url( $resource_id ) {
        $instance = self::get_instance();
        $file_id = $instance->get_file_attachment_id( $resource_id );

        if ( ! $file_id ) {
            return null;
        }

        return wp_get_attachment_url( $file_id );
    }
}
