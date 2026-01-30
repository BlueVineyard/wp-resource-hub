<?php
/**
 * Video Shortcode class.
 *
 * Handles the [wprh_video] shortcode for displaying a click-to-play video player.
 *
 * @package WPResourceHub
 * @since   1.5.0
 */

namespace WPResourceHub\Shortcodes;

use WPResourceHub\Admin\MetaBoxes;
use WPResourceHub\Helpers;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Video Shortcode class.
 *
 * @since 1.5.0
 */
class VideoShortcode
{

    /**
     * Singleton instance.
     *
     * @var VideoShortcode|null
     */
    private static $instance = null;

    /**
     * Shortcode tag.
     *
     * @var string
     */
    const TAG = 'wprh_video';

    /**
     * Get the singleton instance.
     *
     * @since 1.5.0
     *
     * @return VideoShortcode
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.5.0
     */
    private function __construct()
    {
        add_shortcode(self::TAG, array($this, 'render'));
    }

    /**
     * Render the shortcode.
     *
     * @since 1.5.0
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string
     */
    public function render($atts, $content = '')
    {
        $post = get_post();
        if (! $post) {
            return '';
        }

        $provider = MetaBoxes::get_meta($post->ID, 'video_provider');
        $video_id = MetaBoxes::get_meta($post->ID, 'video_id');
        $video_url = MetaBoxes::get_meta($post->ID, 'video_url');

        // Extract video ID from URL if we have URL but no ID.
        if (empty($video_id) && ! empty($video_url)) {
            $video_id = Helpers::extract_video_id($video_url, $provider);
        }

        if (empty($video_id) && empty($video_url)) {
            return '';
        }

        $embed_url = '';
        if ($video_id && $provider) {
            $embed_url = Helpers::get_video_embed_url($video_id, $provider);
        }

        if (empty($embed_url)) {
            return '';
        }

        // Enqueue assets.
        wp_enqueue_style('wprh-frontend', WPRH_PLUGIN_URL . 'assets/css/frontend.css', array(), WPRH_VERSION);
        wp_enqueue_script('wprh-frontend', WPRH_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WPRH_VERSION, true);

        // Get thumbnail.
        $thumbnail = Helpers::get_resource_thumbnail($post, 'large');
        if (empty($thumbnail)) {
            // Fallback to provider thumbnail.
            $thumb_url = Helpers::get_video_thumbnail_url($video_id, $provider, 'maxresdefault');
            if ($thumb_url) {
                $thumbnail = '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr(get_the_title($post)) . '">';
            }
        }

        $duration = MetaBoxes::get_meta($post->ID, 'video_duration');

        ob_start();
        ?>
        <div class="wprh-video-player" data-embed-url="<?php echo esc_attr($embed_url); ?>">
            <div class="wprh-video-player-thumbnail">
                <?php if (! empty($thumbnail)) : ?>
                    <?php echo $thumbnail; ?>
                <?php else : ?>
                    <div class="wprh-video-player-placeholder"></div>
                <?php endif; ?>
                <?php if ($duration) : ?>
                    <span class="wprh-video-player-duration"><?php echo esc_html($duration); ?></span>
                <?php endif; ?>
                <button class="wprh-play-button wprh-video-player-play" aria-label="<?php esc_attr_e('Play video', 'wp-resource-hub'); ?>">
                    <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="32" fill="rgba(0,0,0,0.7)" />
                        <path d="M26 20L44 32L26 44V20Z" fill="white" />
                    </svg>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
