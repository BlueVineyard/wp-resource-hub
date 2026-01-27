<?php
/**
 * Import/Export Page class.
 *
 * Handles the admin page for importing and exporting resources.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\ImportExport\Exporter;
use WPResourceHub\ImportExport\Importer;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Import/Export Page class.
 *
 * @since 1.1.0
 */
class ImportExportPage {

    /**
     * Singleton instance.
     *
     * @var ImportExportPage|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return ImportExportPage
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
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_notices', array( $this, 'show_import_results' ) );
    }

    /**
     * Register the menu page.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=' . ResourcePostType::get_post_type(),
            __( 'Import / Export', 'wp-resource-hub' ),
            __( 'Import / Export', 'wp-resource-hub' ),
            'import',
            'wprh-import-export',
            array( $this, 'render_page' )
        );
    }

    /**
     * Render the page.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function render_page() {
        ?>
        <div class="wrap wprh-import-export">
            <h1><?php esc_html_e( 'Import / Export Resources', 'wp-resource-hub' ); ?></h1>

            <div class="wprh-ie-container">
                <div class="wprh-ie-section wprh-export-section">
                    <h2><?php esc_html_e( 'Export Resources', 'wp-resource-hub' ); ?></h2>
                    <p><?php esc_html_e( 'Download all your resources in CSV or JSON format.', 'wp-resource-hub' ); ?></p>

                    <div class="wprh-export-buttons">
                        <a href="<?php echo esc_url( Exporter::get_export_url( 'csv' ) ); ?>" class="button button-primary">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e( 'Export as CSV', 'wp-resource-hub' ); ?>
                        </a>
                        <a href="<?php echo esc_url( Exporter::get_export_url( 'json' ) ); ?>" class="button">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e( 'Export as JSON', 'wp-resource-hub' ); ?>
                        </a>
                    </div>

                    <p class="description">
                        <?php esc_html_e( 'CSV is best for spreadsheet editing. JSON includes full content and is better for backups.', 'wp-resource-hub' ); ?>
                    </p>
                </div>

                <div class="wprh-ie-section wprh-import-section">
                    <h2><?php esc_html_e( 'Import Resources', 'wp-resource-hub' ); ?></h2>
                    <p><?php esc_html_e( 'Upload a CSV or JSON file to import resources.', 'wp-resource-hub' ); ?></p>

                    <form method="post" enctype="multipart/form-data" class="wprh-import-form">
                        <?php wp_nonce_field( 'wprh_import' ); ?>
                        <input type="hidden" name="wprh_import" value="1">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php esc_html_e( 'Choose File', 'wp-resource-hub' ); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".csv,.json" required>
                                    <p class="description"><?php esc_html_e( 'Accepted formats: CSV, JSON', 'wp-resource-hub' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Import Mode', 'wp-resource-hub' ); ?></th>
                                <td>
                                    <label>
                                        <input type="radio" name="import_mode" value="create" checked>
                                        <?php esc_html_e( 'Create new resources only', 'wp-resource-hub' ); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="radio" name="import_mode" value="update">
                                        <?php esc_html_e( 'Update existing resources (by ID)', 'wp-resource-hub' ); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e( 'When updating, resources with matching IDs will be overwritten.', 'wp-resource-hub' ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button( __( 'Import Resources', 'wp-resource-hub' ), 'primary', 'submit', true ); ?>
                    </form>
                </div>

                <div class="wprh-ie-section wprh-format-section">
                    <h2><?php esc_html_e( 'CSV Format Guide', 'wp-resource-hub' ); ?></h2>
                    <p><?php esc_html_e( 'Your CSV file should include the following columns:', 'wp-resource-hub' ); ?></p>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Column', 'wp-resource-hub' ); ?></th>
                                <th><?php esc_html_e( 'Required', 'wp-resource-hub' ); ?></th>
                                <th><?php esc_html_e( 'Description', 'wp-resource-hub' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>title</code></td>
                                <td><?php esc_html_e( 'Yes', 'wp-resource-hub' ); ?></td>
                                <td><?php esc_html_e( 'Resource title', 'wp-resource-hub' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>resource_type</code></td>
                                <td><?php esc_html_e( 'Yes', 'wp-resource-hub' ); ?></td>
                                <td><?php esc_html_e( 'video, pdf, download, external-link, or internal-content', 'wp-resource-hub' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>status</code></td>
                                <td><?php esc_html_e( 'No', 'wp-resource-hub' ); ?></td>
                                <td><?php esc_html_e( 'publish, draft, or private (default: draft)', 'wp-resource-hub' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>topics</code></td>
                                <td><?php esc_html_e( 'No', 'wp-resource-hub' ); ?></td>
                                <td><?php esc_html_e( 'Comma-separated topic names', 'wp-resource-hub' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>audiences</code></td>
                                <td><?php esc_html_e( 'No', 'wp-resource-hub' ); ?></td>
                                <td><?php esc_html_e( 'Comma-separated audience names', 'wp-resource-hub' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>excerpt</code></td>
                                <td><?php esc_html_e( 'No', 'wp-resource-hub' ); ?></td>
                                <td><?php esc_html_e( 'Short description', 'wp-resource-hub' ); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="description" style="margin-top: 15px;">
                        <?php esc_html_e( 'Additional columns depend on resource type (e.g., video_url for videos, external_url for links).', 'wp-resource-hub' ); ?>
                        <?php esc_html_e( 'Export existing resources to see all available fields.', 'wp-resource-hub' ); ?>
                    </p>
                </div>
            </div>
        </div>

        <style>
            .wprh-import-export { max-width: 1200px; }
            .wprh-ie-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
            .wprh-ie-section { background: #fff; padding: 20px; border: 1px solid #ccd0d4; }
            .wprh-ie-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .wprh-export-buttons { display: flex; gap: 10px; margin: 20px 0; }
            .wprh-export-buttons .button { display: inline-flex; align-items: center; gap: 5px; }
            .wprh-export-buttons .dashicons { font-size: 16px; width: 16px; height: 16px; }
            .wprh-format-section { grid-column: 1 / -1; }
            .wprh-format-section code { background: #f0f0f1; padding: 2px 6px; }
            @media (max-width: 782px) {
                .wprh-ie-container { grid-template-columns: 1fr; }
            }
        </style>
        <?php
    }

    /**
     * Show import results notice.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function show_import_results() {
        $screen = get_current_screen();

        if ( ! $screen || 'resource_page_wprh-import-export' !== $screen->id ) {
            return;
        }

        if ( ! isset( $_GET['imported'] ) ) {
            return;
        }

        $results = Importer::get_stored_results();

        if ( ! $results ) {
            return;
        }

        $class = empty( $results['errors'] ) ? 'notice-success' : 'notice-warning';
        ?>
        <div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Import Complete!', 'wp-resource-hub' ); ?></strong>
            </p>
            <ul>
                <?php if ( $results['created'] > 0 ) : ?>
                    <li>
                        <?php
                        /* translators: %d: Number of created resources */
                        printf( esc_html( _n( '%d resource created', '%d resources created', $results['created'], 'wp-resource-hub' ) ), esc_html( $results['created'] ) );
                        ?>
                    </li>
                <?php endif; ?>
                <?php if ( $results['updated'] > 0 ) : ?>
                    <li>
                        <?php
                        /* translators: %d: Number of updated resources */
                        printf( esc_html( _n( '%d resource updated', '%d resources updated', $results['updated'], 'wp-resource-hub' ) ), esc_html( $results['updated'] ) );
                        ?>
                    </li>
                <?php endif; ?>
                <?php if ( $results['skipped'] > 0 ) : ?>
                    <li>
                        <?php
                        /* translators: %d: Number of skipped resources */
                        printf( esc_html( _n( '%d resource skipped', '%d resources skipped', $results['skipped'], 'wp-resource-hub' ) ), esc_html( $results['skipped'] ) );
                        ?>
                    </li>
                <?php endif; ?>
            </ul>
            <?php if ( ! empty( $results['errors'] ) ) : ?>
                <details>
                    <summary><?php esc_html_e( 'View Errors', 'wp-resource-hub' ); ?></summary>
                    <ul>
                        <?php foreach ( $results['errors'] as $error ) : ?>
                            <li><?php echo esc_html( $error ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
        <?php
    }
}
