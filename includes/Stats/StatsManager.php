<?php
/**
 * Stats Manager class.
 *
 * Central manager for resource statistics including views and downloads.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\Stats;

use WPResourceHub\PostTypes\ResourcePostType;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stats Manager class.
 *
 * @since 1.1.0
 */
class StatsManager {

    /**
     * Singleton instance.
     *
     * @var StatsManager|null
     */
    private static $instance = null;

    /**
     * Stats table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return StatsManager
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wprh_stats';

        // Initialize tracking.
        add_action( 'wp', array( $this, 'track_view' ) );
        add_action( 'wprh_download_tracked', array( $this, 'record_download' ), 10, 2 );

        // Admin hooks.
        add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
        add_filter( 'manage_resource_posts_columns', array( $this, 'add_stats_columns' ) );
        add_action( 'manage_resource_posts_custom_column', array( $this, 'render_stats_column' ), 10, 2 );
    }

    /**
     * Create the stats table.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wprh_stats';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            resource_id bigint(20) unsigned NOT NULL,
            stat_type varchar(20) NOT NULL,
            stat_date date NOT NULL,
            stat_count int(11) unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY resource_type_date (resource_id, stat_type, stat_date),
            KEY resource_id (resource_id),
            KEY stat_type (stat_type),
            KEY stat_date (stat_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Track a view on the frontend.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function track_view() {
        if ( is_admin() || ! is_singular( ResourcePostType::get_post_type() ) ) {
            return;
        }

        // Don't track for logged-in admins by default.
        if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
            /**
             * Filter whether to track views for editors/admins.
             *
             * @since 1.1.0
             *
             * @param bool $track_admin Whether to track admin views.
             */
            if ( ! apply_filters( 'wprh_track_admin_views', false ) ) {
                return;
            }
        }

        $post_id = get_the_ID();
        $this->record_stat( $post_id, 'view' );

        /**
         * Fires after a resource view is tracked.
         *
         * @since 1.1.0
         *
         * @param int $post_id The resource post ID.
         */
        do_action( 'wprh_view_tracked', $post_id );
    }

    /**
     * Record a download.
     *
     * @since 1.1.0
     *
     * @param int    $post_id  Resource post ID.
     * @param string $file_url The file URL that was downloaded.
     * @return void
     */
    public function record_download( $post_id, $file_url = '' ) {
        $this->record_stat( $post_id, 'download' );
    }

    /**
     * Record a stat.
     *
     * @since 1.1.0
     *
     * @param int    $resource_id Resource post ID.
     * @param string $stat_type   Type of stat (view, download).
     * @return bool
     */
    public function record_stat( $resource_id, $stat_type ) {
        global $wpdb;

        $today = current_time( 'Y-m-d' );

        // Try to update existing record first.
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name}
                SET stat_count = stat_count + 1
                WHERE resource_id = %d AND stat_type = %s AND stat_date = %s",
                $resource_id,
                $stat_type,
                $today
            )
        );

        // If no record exists, insert one.
        if ( 0 === $updated ) {
            $wpdb->insert(
                $this->table_name,
                array(
                    'resource_id' => $resource_id,
                    'stat_type'   => $stat_type,
                    'stat_date'   => $today,
                    'stat_count'  => 1,
                ),
                array( '%d', '%s', '%s', '%d' )
            );
        }

        // Also update post meta for quick access.
        $meta_key = '_wprh_total_' . $stat_type . 's';
        $current  = (int) get_post_meta( $resource_id, $meta_key, true );
        update_post_meta( $resource_id, $meta_key, $current + 1 );

        return true;
    }

    /**
     * Get total stat count for a resource.
     *
     * @since 1.1.0
     *
     * @param int    $resource_id Resource post ID.
     * @param string $stat_type   Type of stat (view, download).
     * @return int
     */
    public function get_total_count( $resource_id, $stat_type ) {
        // Try post meta first for performance.
        $meta_key = '_wprh_total_' . $stat_type . 's';
        $count    = get_post_meta( $resource_id, $meta_key, true );

        if ( '' !== $count ) {
            return (int) $count;
        }

        // Fall back to database query.
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE resource_id = %d AND stat_type = %s",
                $resource_id,
                $stat_type
            )
        );

        $count = (int) $count;

        // Cache in post meta.
        update_post_meta( $resource_id, $meta_key, $count );

        return $count;
    }

    /**
     * Get stats for a date range.
     *
     * @since 1.1.0
     *
     * @param int    $resource_id Resource post ID (0 for all).
     * @param string $stat_type   Type of stat (view, download, or empty for all).
     * @param string $start_date  Start date (Y-m-d).
     * @param string $end_date    End date (Y-m-d).
     * @return array
     */
    public function get_stats_range( $resource_id = 0, $stat_type = '', $start_date = '', $end_date = '' ) {
        global $wpdb;

        $where = array( '1=1' );
        $params = array();

        if ( $resource_id ) {
            $where[] = 'resource_id = %d';
            $params[] = $resource_id;
        }

        if ( $stat_type ) {
            $where[] = 'stat_type = %s';
            $params[] = $stat_type;
        }

        if ( $start_date ) {
            $where[] = 'stat_date >= %s';
            $params[] = $start_date;
        }

        if ( $end_date ) {
            $where[] = 'stat_date <= %s';
            $params[] = $end_date;
        }

        $where_clause = implode( ' AND ', $where );

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare(
                "SELECT stat_date, stat_type, SUM(stat_count) as total
                FROM {$this->table_name}
                WHERE {$where_clause}
                GROUP BY stat_date, stat_type
                ORDER BY stat_date ASC",
                $params
            );
        } else {
            $sql = "SELECT stat_date, stat_type, SUM(stat_count) as total
                FROM {$this->table_name}
                WHERE {$where_clause}
                GROUP BY stat_date, stat_type
                ORDER BY stat_date ASC";
        }

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Get top resources by stat type.
     *
     * @since 1.1.0
     *
     * @param string $stat_type Type of stat (view, download).
     * @param int    $limit     Number of results.
     * @param string $period    Period (all, week, month, year).
     * @return array
     */
    public function get_top_resources( $stat_type = 'view', $limit = 10, $period = 'all' ) {
        global $wpdb;

        $where = "stat_type = %s";
        $params = array( $stat_type );

        // Add date filter based on period.
        switch ( $period ) {
            case 'week':
                $where .= " AND stat_date >= %s";
                $params[] = date( 'Y-m-d', strtotime( '-7 days' ) );
                break;

            case 'month':
                $where .= " AND stat_date >= %s";
                $params[] = date( 'Y-m-d', strtotime( '-30 days' ) );
                break;

            case 'year':
                $where .= " AND stat_date >= %s";
                $params[] = date( 'Y-m-d', strtotime( '-365 days' ) );
                break;
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT resource_id, SUM(stat_count) as total
                FROM {$this->table_name}
                WHERE {$where}
                GROUP BY resource_id
                ORDER BY total DESC
                LIMIT %d",
                array_merge( $params, array( $limit ) )
            ),
            ARRAY_A
        );

        // Enrich with post data.
        foreach ( $results as &$result ) {
            $post = get_post( $result['resource_id'] );
            if ( $post ) {
                $result['title'] = $post->post_title;
                $result['edit_link'] = get_edit_post_link( $post->ID );
                $result['view_link'] = get_permalink( $post->ID );
            }
        }

        return $results;
    }

    /**
     * Get overall statistics summary.
     *
     * @since 1.1.0
     *
     * @return array
     */
    public function get_summary() {
        global $wpdb;

        $summary = array(
            'total_views'     => 0,
            'total_downloads' => 0,
            'views_today'     => 0,
            'downloads_today' => 0,
            'views_week'      => 0,
            'downloads_week'  => 0,
            'views_month'     => 0,
            'downloads_month' => 0,
        );

        $today = current_time( 'Y-m-d' );
        $week_ago = date( 'Y-m-d', strtotime( '-7 days' ) );
        $month_ago = date( 'Y-m-d', strtotime( '-30 days' ) );

        // Total views.
        $summary['total_views'] = (int) $wpdb->get_var(
            "SELECT SUM(stat_count) FROM {$this->table_name} WHERE stat_type = 'view'"
        );

        // Total downloads.
        $summary['total_downloads'] = (int) $wpdb->get_var(
            "SELECT SUM(stat_count) FROM {$this->table_name} WHERE stat_type = 'download'"
        );

        // Today's views.
        $summary['views_today'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE stat_type = 'view' AND stat_date = %s",
                $today
            )
        );

        // Today's downloads.
        $summary['downloads_today'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE stat_type = 'download' AND stat_date = %s",
                $today
            )
        );

        // This week's views.
        $summary['views_week'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE stat_type = 'view' AND stat_date >= %s",
                $week_ago
            )
        );

        // This week's downloads.
        $summary['downloads_week'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE stat_type = 'download' AND stat_date >= %s",
                $week_ago
            )
        );

        // This month's views.
        $summary['views_month'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE stat_type = 'view' AND stat_date >= %s",
                $month_ago
            )
        );

        // This month's downloads.
        $summary['downloads_month'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(stat_count) FROM {$this->table_name}
                WHERE stat_type = 'download' AND stat_date >= %s",
                $month_ago
            )
        );

        /**
         * Filter the stats summary.
         *
         * @since 1.1.0
         *
         * @param array $summary Statistics summary.
         */
        return apply_filters( 'wprh_stats_summary', $summary );
    }

    /**
     * Register the dashboard widget.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register_dashboard_widget() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'wprh_stats_widget',
            __( 'Resource Hub Stats', 'wp-resource-hub' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    /**
     * Render the dashboard widget.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function render_dashboard_widget() {
        $summary = $this->get_summary();
        $top_resources = $this->get_top_resources( 'view', 5, 'week' );
        ?>
        <div class="wprh-stats-widget">
            <div class="wprh-stats-grid">
                <div class="wprh-stat-box">
                    <span class="wprh-stat-number"><?php echo esc_html( number_format_i18n( $summary['views_today'] ) ); ?></span>
                    <span class="wprh-stat-label"><?php esc_html_e( 'Views Today', 'wp-resource-hub' ); ?></span>
                </div>
                <div class="wprh-stat-box">
                    <span class="wprh-stat-number"><?php echo esc_html( number_format_i18n( $summary['downloads_today'] ) ); ?></span>
                    <span class="wprh-stat-label"><?php esc_html_e( 'Downloads Today', 'wp-resource-hub' ); ?></span>
                </div>
                <div class="wprh-stat-box">
                    <span class="wprh-stat-number"><?php echo esc_html( number_format_i18n( $summary['views_week'] ) ); ?></span>
                    <span class="wprh-stat-label"><?php esc_html_e( 'Views This Week', 'wp-resource-hub' ); ?></span>
                </div>
                <div class="wprh-stat-box">
                    <span class="wprh-stat-number"><?php echo esc_html( number_format_i18n( $summary['downloads_week'] ) ); ?></span>
                    <span class="wprh-stat-label"><?php esc_html_e( 'Downloads This Week', 'wp-resource-hub' ); ?></span>
                </div>
            </div>

            <?php if ( ! empty( $top_resources ) ) : ?>
                <h4><?php esc_html_e( 'Top Resources This Week', 'wp-resource-hub' ); ?></h4>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Resource', 'wp-resource-hub' ); ?></th>
                            <th><?php esc_html_e( 'Views', 'wp-resource-hub' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $top_resources as $resource ) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( $resource['edit_link'] ); ?>">
                                        <?php echo esc_html( $resource['title'] ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( number_format_i18n( $resource['total'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p class="wprh-stats-footer">
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=resource&page=wprh-stats' ) ); ?>">
                    <?php esc_html_e( 'View Full Stats →', 'wp-resource-hub' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Add stats columns to list table.
     *
     * @since 1.1.0
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_stats_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            // Add stats columns before date.
            if ( 'visibility' === $key ) {
                $new_columns['views']     = __( 'Views', 'wp-resource-hub' );
                $new_columns['downloads'] = __( 'Downloads', 'wp-resource-hub' );
            }
        }

        return $new_columns;
    }

    /**
     * Render stats column content.
     *
     * @since 1.1.0
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function render_stats_column( $column, $post_id ) {
        switch ( $column ) {
            case 'views':
                $count = $this->get_total_count( $post_id, 'view' );
                echo '<span class="wprh-stat-count">' . esc_html( number_format_i18n( $count ) ) . '</span>';
                break;

            case 'downloads':
                $count = $this->get_total_count( $post_id, 'download' );
                echo '<span class="wprh-stat-count">' . esc_html( number_format_i18n( $count ) ) . '</span>';
                break;
        }
    }

    /**
     * Clear stats for a resource.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return bool
     */
    public function clear_stats( $resource_id ) {
        global $wpdb;

        $wpdb->delete(
            $this->table_name,
            array( 'resource_id' => $resource_id ),
            array( '%d' )
        );

        delete_post_meta( $resource_id, '_wprh_total_views' );
        delete_post_meta( $resource_id, '_wprh_total_downloads' );

        return true;
    }
}
