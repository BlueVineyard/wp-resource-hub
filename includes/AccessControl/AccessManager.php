<?php
/**
 * Access Manager class.
 *
 * Handles access control and restrictions for resources.
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

namespace WPResourceHub\AccessControl;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Admin\MetaBoxes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Access Manager class.
 *
 * @since 1.1.0
 */
class AccessManager {

    /**
     * Singleton instance.
     *
     * @var AccessManager|null
     */
    private static $instance = null;

    /**
     * Access levels.
     *
     * @var array
     */
    private $access_levels = array();

    /**
     * Get the singleton instance.
     *
     * @since 1.1.0
     *
     * @return AccessManager
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
        $this->set_access_levels();

        // Admin hooks.
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post_resource', array( $this, 'save_access_settings' ), 10, 2 );

        // Frontend hooks.
        add_filter( 'the_content', array( $this, 'filter_content' ), 5 );
        add_filter( 'wprh_can_access_download', array( $this, 'check_download_access' ), 10, 2 );
        add_action( 'template_redirect', array( $this, 'check_access' ) );

        // Add column to list table.
        add_filter( 'manage_resource_posts_columns', array( $this, 'add_access_column' ), 15 );
        add_action( 'manage_resource_posts_custom_column', array( $this, 'render_access_column' ), 10, 2 );
    }

    /**
     * Set available access levels.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function set_access_levels() {
        $this->access_levels = array(
            'public'      => array(
                'label'       => __( 'Public', 'wp-resource-hub' ),
                'description' => __( 'Anyone can access this resource.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-visibility',
            ),
            'logged_in'   => array(
                'label'       => __( 'Logged-in Users', 'wp-resource-hub' ),
                'description' => __( 'Only logged-in users can access.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-admin-users',
            ),
            'role'        => array(
                'label'       => __( 'Specific Roles', 'wp-resource-hub' ),
                'description' => __( 'Only users with selected roles can access.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-groups',
            ),
            'password'    => array(
                'label'       => __( 'Password Protected', 'wp-resource-hub' ),
                'description' => __( 'Requires a password to access.', 'wp-resource-hub' ),
                'icon'        => 'dashicons-lock',
            ),
        );

        /**
         * Filter the available access levels.
         *
         * @since 1.1.0
         *
         * @param array $access_levels Access levels configuration.
         */
        $this->access_levels = apply_filters( 'wprh_access_levels', $this->access_levels );
    }

    /**
     * Get access levels.
     *
     * @since 1.1.0
     *
     * @return array
     */
    public function get_access_levels() {
        return $this->access_levels;
    }

    /**
     * Register the access control meta box.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register_meta_box() {
        add_meta_box(
            'wprh_access_control',
            __( 'Access Control', 'wp-resource-hub' ),
            array( $this, 'render_meta_box' ),
            ResourcePostType::get_post_type(),
            'side',
            'default'
        );
    }

    /**
     * Render the access control meta box.
     *
     * @since 1.1.0
     *
     * @param \WP_Post $post Current post.
     * @return void
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'wprh_access_control', 'wprh_access_nonce' );

        $access_level   = get_post_meta( $post->ID, '_wprh_access_level', true ) ?: 'public';
        $allowed_roles  = get_post_meta( $post->ID, '_wprh_allowed_roles', true ) ?: array();
        $access_message = get_post_meta( $post->ID, '_wprh_access_message', true );

        $all_roles = wp_roles()->get_names();
        ?>
        <div class="wprh-access-control">
            <p>
                <label for="wprh_access_level"><strong><?php esc_html_e( 'Access Level', 'wp-resource-hub' ); ?></strong></label>
            </p>
            <select name="wprh_access_level" id="wprh_access_level" class="widefat">
                <?php foreach ( $this->access_levels as $level_key => $level ) : ?>
                    <option value="<?php echo esc_attr( $level_key ); ?>" <?php selected( $access_level, $level_key ); ?>>
                        <?php echo esc_html( $level['label'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="wprh-role-selection" style="<?php echo 'role' === $access_level ? '' : 'display:none;'; ?> margin-top: 10px;">
                <p><strong><?php esc_html_e( 'Allowed Roles', 'wp-resource-hub' ); ?></strong></p>
                <?php foreach ( $all_roles as $role_key => $role_name ) : ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox"
                               name="wprh_allowed_roles[]"
                               value="<?php echo esc_attr( $role_key ); ?>"
                               <?php checked( in_array( $role_key, (array) $allowed_roles, true ) ); ?>>
                        <?php echo esc_html( $role_name ); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 15px;">
                <p>
                    <label for="wprh_access_message"><strong><?php esc_html_e( 'Restricted Access Message', 'wp-resource-hub' ); ?></strong></label>
                </p>
                <textarea name="wprh_access_message"
                          id="wprh_access_message"
                          class="widefat"
                          rows="3"
                          placeholder="<?php esc_attr_e( 'Optional custom message when access is denied.', 'wp-resource-hub' ); ?>"><?php echo esc_textarea( $access_message ); ?></textarea>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#wprh_access_level').on('change', function() {
                if ($(this).val() === 'role') {
                    $('#wprh-role-selection').slideDown();
                } else {
                    $('#wprh-role-selection').slideUp();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save access settings.
     *
     * @since 1.1.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @return void
     */
    public function save_access_settings( $post_id, $post ) {
        if ( ! isset( $_POST['wprh_access_nonce'] ) ||
             ! wp_verify_nonce( $_POST['wprh_access_nonce'], 'wprh_access_control' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save access level.
        if ( isset( $_POST['wprh_access_level'] ) ) {
            $access_level = sanitize_text_field( $_POST['wprh_access_level'] );
            if ( array_key_exists( $access_level, $this->access_levels ) ) {
                update_post_meta( $post_id, '_wprh_access_level', $access_level );
            }
        }

        // Save allowed roles.
        if ( isset( $_POST['wprh_allowed_roles'] ) && is_array( $_POST['wprh_allowed_roles'] ) ) {
            $allowed_roles = array_map( 'sanitize_text_field', $_POST['wprh_allowed_roles'] );
            update_post_meta( $post_id, '_wprh_allowed_roles', $allowed_roles );
        } else {
            delete_post_meta( $post_id, '_wprh_allowed_roles' );
        }

        // Save access message.
        if ( isset( $_POST['wprh_access_message'] ) ) {
            $message = sanitize_textarea_field( $_POST['wprh_access_message'] );
            update_post_meta( $post_id, '_wprh_access_message', $message );
        }
    }

    /**
     * Check if user can access a resource.
     *
     * @since 1.1.0
     *
     * @param int      $resource_id Resource post ID.
     * @param int|null $user_id     User ID. Defaults to current user.
     * @return bool
     */
    public function can_access( $resource_id, $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $access_level = get_post_meta( $resource_id, '_wprh_access_level', true ) ?: 'public';

        // Admins always have access.
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        switch ( $access_level ) {
            case 'public':
                $can_access = true;
                break;

            case 'logged_in':
                $can_access = $user_id > 0;
                break;

            case 'role':
                $allowed_roles = get_post_meta( $resource_id, '_wprh_allowed_roles', true ) ?: array();
                $can_access    = false;

                if ( $user_id > 0 && ! empty( $allowed_roles ) ) {
                    $user = get_user_by( 'id', $user_id );
                    if ( $user ) {
                        $can_access = ! empty( array_intersect( $user->roles, $allowed_roles ) );
                    }
                }
                break;

            case 'password':
                // Password protection uses WordPress built-in functionality.
                $can_access = ! post_password_required( $resource_id );
                break;

            default:
                /**
                 * Filter access for custom access levels.
                 *
                 * @since 1.1.0
                 *
                 * @param bool   $can_access   Whether user can access.
                 * @param int    $resource_id  Resource post ID.
                 * @param int    $user_id      User ID.
                 * @param string $access_level Access level.
                 */
                $can_access = apply_filters( 'wprh_custom_access_check', true, $resource_id, $user_id, $access_level );
                break;
        }

        /**
         * Filter the final access check result.
         *
         * @since 1.1.0
         *
         * @param bool   $can_access   Whether user can access.
         * @param int    $resource_id  Resource post ID.
         * @param int    $user_id      User ID.
         * @param string $access_level Access level.
         */
        return apply_filters( 'wprh_can_access', $can_access, $resource_id, $user_id, $access_level );
    }

    /**
     * Get the access denied message.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return string
     */
    public function get_access_denied_message( $resource_id ) {
        $custom_message = get_post_meta( $resource_id, '_wprh_access_message', true );

        if ( $custom_message ) {
            return $custom_message;
        }

        $access_level = get_post_meta( $resource_id, '_wprh_access_level', true ) ?: 'public';

        switch ( $access_level ) {
            case 'logged_in':
                $message = __( 'Please log in to access this resource.', 'wp-resource-hub' );
                break;

            case 'role':
                $message = __( 'You do not have the required permissions to access this resource.', 'wp-resource-hub' );
                break;

            case 'password':
                $message = __( 'This resource is password protected.', 'wp-resource-hub' );
                break;

            default:
                $message = __( 'Access to this resource is restricted.', 'wp-resource-hub' );
                break;
        }

        /**
         * Filter the access denied message.
         *
         * @since 1.1.0
         *
         * @param string $message      The message.
         * @param int    $resource_id  Resource post ID.
         * @param string $access_level Access level.
         */
        return apply_filters( 'wprh_access_denied_message', $message, $resource_id, $access_level );
    }

    /**
     * Check access on template redirect.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function check_access() {
        if ( ! is_singular( ResourcePostType::get_post_type() ) ) {
            return;
        }

        $post_id = get_the_ID();

        if ( $this->can_access( $post_id ) ) {
            return;
        }

        $access_level = get_post_meta( $post_id, '_wprh_access_level', true );

        // For logged_in level, redirect to login.
        if ( 'logged_in' === $access_level && ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink( $post_id ) ) );
            exit;
        }

        // For other cases, show 403.
        status_header( 403 );

        /**
         * Fires when access is denied to a resource.
         *
         * @since 1.1.0
         *
         * @param int    $post_id      Post ID.
         * @param string $access_level Access level.
         */
        do_action( 'wprh_access_denied', $post_id, $access_level );
    }

    /**
     * Filter content for restricted resources.
     *
     * @since 1.1.0
     *
     * @param string $content Post content.
     * @return string
     */
    public function filter_content( $content ) {
        if ( ! is_singular( ResourcePostType::get_post_type() ) ) {
            return $content;
        }

        $post_id = get_the_ID();

        if ( $this->can_access( $post_id ) ) {
            return $content;
        }

        // Return restricted message instead of content.
        $message = $this->get_access_denied_message( $post_id );
        $access_level = get_post_meta( $post_id, '_wprh_access_level', true );

        ob_start();
        ?>
        <div class="wprh-access-denied">
            <div class="wprh-access-denied-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <div class="wprh-access-denied-message">
                <?php echo wp_kses_post( $message ); ?>
            </div>
            <?php if ( 'logged_in' === $access_level && ! is_user_logged_in() ) : ?>
                <div class="wprh-access-denied-action">
                    <a href="<?php echo esc_url( wp_login_url( get_permalink( $post_id ) ) ); ?>" class="button">
                        <?php esc_html_e( 'Log In', 'wp-resource-hub' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check download access.
     *
     * @since 1.1.0
     *
     * @param bool $can_access  Current access status.
     * @param int  $resource_id Resource post ID.
     * @return bool
     */
    public function check_download_access( $can_access, $resource_id ) {
        return $this->can_access( $resource_id );
    }

    /**
     * Add access column to list table.
     *
     * @since 1.1.0
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_access_column( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            if ( 'visibility' === $key ) {
                $new_columns['access'] = __( 'Access', 'wp-resource-hub' );
            }
            $new_columns[ $key ] = $value;
        }

        return $new_columns;
    }

    /**
     * Render access column.
     *
     * @since 1.1.0
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function render_access_column( $column, $post_id ) {
        if ( 'access' !== $column ) {
            return;
        }

        $access_level = get_post_meta( $post_id, '_wprh_access_level', true ) ?: 'public';
        $levels       = $this->get_access_levels();

        if ( isset( $levels[ $access_level ] ) ) {
            $level = $levels[ $access_level ];
            printf(
                '<span class="wprh-access-badge wprh-access-%s" title="%s"><span class="dashicons %s"></span> %s</span>',
                esc_attr( $access_level ),
                esc_attr( $level['description'] ),
                esc_attr( $level['icon'] ),
                esc_html( $level['label'] )
            );
        }
    }

    /**
     * Get access level for a resource.
     *
     * @since 1.1.0
     *
     * @param int $resource_id Resource post ID.
     * @return string
     */
    public static function get_access_level( $resource_id ) {
        return get_post_meta( $resource_id, '_wprh_access_level', true ) ?: 'public';
    }
}
