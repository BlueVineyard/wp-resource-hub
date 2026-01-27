<?php
/**
 * Helpers class.
 *
 * Utility functions and helper methods for the plugin.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helpers class.
 *
 * @since 1.0.0
 */
class Helpers {

    /**
     * Extract video ID from URL.
     *
     * @since 1.0.0
     *
     * @param string $url      Video URL.
     * @param string $provider Video provider (youtube, vimeo, local).
     * @return string|null Video ID or null.
     */
    public static function extract_video_id( $url, $provider = '' ) {
        if ( empty( $url ) ) {
            return null;
        }

        // Auto-detect provider if not specified.
        if ( empty( $provider ) ) {
            if ( strpos( $url, 'youtube.com' ) !== false || strpos( $url, 'youtu.be' ) !== false ) {
                $provider = 'youtube';
            } elseif ( strpos( $url, 'vimeo.com' ) !== false ) {
                $provider = 'vimeo';
            }
        }

        switch ( $provider ) {
            case 'youtube':
                return self::extract_youtube_id( $url );

            case 'vimeo':
                return self::extract_vimeo_id( $url );

            default:
                return null;
        }
    }

    /**
     * Extract YouTube video ID.
     *
     * @since 1.0.0
     *
     * @param string $url YouTube URL.
     * @return string|null Video ID or null.
     */
    public static function extract_youtube_id( $url ) {
        $patterns = array(
            // Standard YouTube URLs.
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            // Short YouTube URLs.
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            // Embed URLs.
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            // YouTube Shorts.
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract Vimeo video ID.
     *
     * @since 1.0.0
     *
     * @param string $url Vimeo URL.
     * @return string|null Video ID or null.
     */
    public static function extract_vimeo_id( $url ) {
        $patterns = array(
            '/vimeo\.com\/(\d+)/',
            '/player\.vimeo\.com\/video\/(\d+)/',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Calculate reading time for content.
     *
     * @since 1.0.0
     *
     * @param string $content Content to calculate reading time for.
     * @param int    $wpm     Words per minute (default 200).
     * @return int Reading time in minutes.
     */
    public static function calculate_reading_time( $content, $wpm = 200 ) {
        // Strip HTML tags and shortcodes.
        $content = wp_strip_all_tags( strip_shortcodes( $content ) );

        // Count words.
        $word_count = str_word_count( $content );

        // Calculate reading time.
        $reading_time = ceil( $word_count / $wpm );

        // Minimum 1 minute.
        return max( 1, $reading_time );
    }

    /**
     * Format file size for display.
     *
     * @since 1.0.0
     *
     * @param int $bytes File size in bytes.
     * @return string Formatted file size.
     */
    public static function format_file_size( $bytes ) {
        return size_format( $bytes );
    }

    /**
     * Get video embed URL.
     *
     * @since 1.0.0
     *
     * @param string $video_id Video ID.
     * @param string $provider Video provider.
     * @return string|null Embed URL or null.
     */
    public static function get_video_embed_url( $video_id, $provider ) {
        if ( empty( $video_id ) ) {
            return null;
        }

        switch ( $provider ) {
            case 'youtube':
                return 'https://www.youtube.com/embed/' . esc_attr( $video_id );

            case 'vimeo':
                return 'https://player.vimeo.com/video/' . esc_attr( $video_id );

            default:
                return null;
        }
    }

    /**
     * Get video thumbnail URL.
     *
     * @since 1.0.0
     *
     * @param string $video_id        Video ID.
     * @param string $provider        Video provider.
     * @param string $size            Thumbnail size (for YouTube: default, mqdefault, hqdefault, sddefault, maxresdefault).
     * @return string|null Thumbnail URL or null.
     */
    public static function get_video_thumbnail_url( $video_id, $provider, $size = 'hqdefault' ) {
        if ( empty( $video_id ) ) {
            return null;
        }

        switch ( $provider ) {
            case 'youtube':
                return 'https://img.youtube.com/vi/' . esc_attr( $video_id ) . '/' . esc_attr( $size ) . '.jpg';

            case 'vimeo':
                // Vimeo requires API call - return null for now, implement in future.
                return null;

            default:
                return null;
        }
    }

    /**
     * Generate table of contents from content.
     *
     * @since 1.0.0
     *
     * @param string $content Post content.
     * @return array Array with 'toc' (HTML) and 'content' (modified content with IDs).
     */
    public static function generate_toc( $content ) {
        $toc     = array();
        $pattern = '/<h([2-4])[^>]*>(.*?)<\/h[2-4]>/i';

        // Find all headings.
        preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

        if ( empty( $matches ) ) {
            return array(
                'toc'     => '',
                'content' => $content,
            );
        }

        $counter = 0;
        foreach ( $matches as $match ) {
            $counter++;
            $level   = $match[1];
            $text    = wp_strip_all_tags( $match[2] );
            $id      = sanitize_title( $text ) . '-' . $counter;
            $old_tag = $match[0];
            $new_tag = sprintf( '<h%1$d id="%2$s">%3$s</h%1$d>', $level, $id, $match[2] );

            // Replace heading with ID-tagged version.
            $content = str_replace( $old_tag, $new_tag, $content );

            // Add to TOC.
            $toc[] = array(
                'level' => $level,
                'text'  => $text,
                'id'    => $id,
            );
        }

        // Build TOC HTML.
        $toc_html = '<nav class="wprh-toc" aria-label="' . esc_attr__( 'Table of Contents', 'wp-resource-hub' ) . '">';
        $toc_html .= '<h4 class="wprh-toc-title">' . esc_html__( 'Table of Contents', 'wp-resource-hub' ) . '</h4>';
        $toc_html .= '<ul class="wprh-toc-list">';

        foreach ( $toc as $item ) {
            $indent_class = 'wprh-toc-level-' . $item['level'];
            $toc_html .= sprintf(
                '<li class="%s"><a href="#%s">%s</a></li>',
                esc_attr( $indent_class ),
                esc_attr( $item['id'] ),
                esc_html( $item['text'] )
            );
        }

        $toc_html .= '</ul></nav>';

        return array(
            'toc'     => $toc_html,
            'content' => $content,
        );
    }

    /**
     * Get attachment URL by ID.
     *
     * @since 1.0.0
     *
     * @param int $attachment_id Attachment ID.
     * @return string|null Attachment URL or null.
     */
    public static function get_attachment_url( $attachment_id ) {
        if ( empty( $attachment_id ) ) {
            return null;
        }

        return wp_get_attachment_url( $attachment_id );
    }

    /**
     * Check if current user can manage resources.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function current_user_can_manage() {
        /**
         * Filter the capability required to manage resources.
         *
         * @since 1.0.0
         *
         * @param string $capability The capability.
         */
        $capability = apply_filters( 'wprh_manage_capability', 'edit_posts' );

        return current_user_can( $capability );
    }

    /**
     * Get resource type configuration.
     *
     * @since 1.0.0
     *
     * @param string $type_slug Resource type slug.
     * @return array|null Type configuration or null.
     */
    public static function get_resource_type_config( $type_slug ) {
        $types = array(
            'video'            => array(
                'label'       => __( 'Video', 'wp-resource-hub' ),
                'icon'        => 'dashicons-video-alt3',
                'has_content' => false,
            ),
            'pdf'              => array(
                'label'       => __( 'PDF', 'wp-resource-hub' ),
                'icon'        => 'dashicons-pdf',
                'has_content' => false,
            ),
            'download'         => array(
                'label'       => __( 'Download', 'wp-resource-hub' ),
                'icon'        => 'dashicons-download',
                'has_content' => false,
            ),
            'external-link'    => array(
                'label'       => __( 'External Link', 'wp-resource-hub' ),
                'icon'        => 'dashicons-external',
                'has_content' => false,
            ),
            'internal-content' => array(
                'label'       => __( 'Internal Content', 'wp-resource-hub' ),
                'icon'        => 'dashicons-text-page',
                'has_content' => true,
            ),
        );

        /**
         * Filter the resource type configurations.
         *
         * @since 1.0.0
         *
         * @param array $types Resource type configurations.
         */
        $types = apply_filters( 'wprh_resource_type_configs', $types );

        return isset( $types[ $type_slug ] ) ? $types[ $type_slug ] : null;
    }

    /**
     * Sanitize resource type slug.
     *
     * @since 1.0.0
     *
     * @param string $type_slug Resource type slug.
     * @return string Sanitized slug.
     */
    public static function sanitize_type_slug( $type_slug ) {
        return sanitize_title( str_replace( '_', '-', $type_slug ) );
    }

    /**
     * Get template part path.
     *
     * @since 1.0.0
     *
     * @param string $slug Template slug.
     * @param string $name Template name.
     * @return string|null Template path or null.
     */
    public static function get_template_path( $slug, $name = '' ) {
        $template = '';

        // Look in theme first.
        if ( $name ) {
            $template = locate_template( array(
                "wp-resource-hub/{$slug}-{$name}.php",
                "wp-resource-hub/{$slug}.php",
            ) );
        } else {
            $template = locate_template( array( "wp-resource-hub/{$slug}.php" ) );
        }

        // Fall back to plugin templates.
        if ( ! $template ) {
            if ( $name && file_exists( WPRH_PLUGIN_DIR . "templates/{$slug}-{$name}.php" ) ) {
                $template = WPRH_PLUGIN_DIR . "templates/{$slug}-{$name}.php";
            } elseif ( file_exists( WPRH_PLUGIN_DIR . "templates/{$slug}.php" ) ) {
                $template = WPRH_PLUGIN_DIR . "templates/{$slug}.php";
            }
        }

        /**
         * Filter the template path.
         *
         * @since 1.0.0
         *
         * @param string $template Template path.
         * @param string $slug     Template slug.
         * @param string $name     Template name.
         */
        return apply_filters( 'wprh_template_path', $template, $slug, $name );
    }
}
