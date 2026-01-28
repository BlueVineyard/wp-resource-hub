<?php

/**
 * Single Renderer class.
 *
 * Handles rendering of single resource views based on resource type.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Frontend;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Admin\MetaBoxes;
use WPResourceHub\Helpers;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Single Renderer class.
 *
 * @since 1.0.0
 */
class SingleRenderer
{

    /**
     * Singleton instance.
     *
     * @var SingleRenderer|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return SingleRenderer
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
     * @since 1.0.0
     */
    private function __construct()
    {
        // Constructor is private for singleton.
    }

    /**
     * Render resource content based on type.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string Rendered content.
     */
    public function render($post)
    {
        $resource_type = ResourceTypeTax::get_resource_type_slug($post);

        if (! $resource_type) {
            return $this->render_default($post);
        }

        /**
         * Filter the resource content before rendering.
         *
         * @since 1.0.0
         *
         * @param string   $content       The content to be rendered.
         * @param string   $resource_type Resource type slug.
         * @param \WP_Post $post          Post object.
         */
        $content = apply_filters('wprh_pre_render_resource', '', $resource_type, $post);

        if (! empty($content)) {
            return $content;
        }

        switch ($resource_type) {
            case 'video':
                $content = $this->render_video($post);
                break;

            case 'pdf':
                $content = $this->render_pdf($post);
                break;

            case 'download':
                $content = $this->render_download($post);
                break;

            case 'external-link':
                $content = $this->render_external_link($post);
                break;

            case 'internal-content':
                $content = $this->render_internal_content($post);
                break;

            default:
                /**
                 * Filter for custom resource type rendering.
                 *
                 * @since 1.0.0
                 *
                 * @param string   $content       The content.
                 * @param string   $resource_type Resource type slug.
                 * @param \WP_Post $post          Post object.
                 */
                $content = apply_filters('wprh_render_custom_type', $this->render_default($post), $resource_type, $post);
                break;
        }

        /**
         * Filter the rendered resource content.
         *
         * @since 1.0.0
         *
         * @param string   $content       Rendered content.
         * @param string   $resource_type Resource type slug.
         * @param \WP_Post $post          Post object.
         */
        return apply_filters('wprh_render_resource', $content, $resource_type, $post);
    }

    /**
     * Render video resource.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string
     */
    private function render_video($post)
    {
        $provider  = MetaBoxes::get_meta($post->ID, 'video_provider');
        $video_url = MetaBoxes::get_meta($post->ID, 'video_url');
        $video_id  = MetaBoxes::get_meta($post->ID, 'video_id');
        $duration  = MetaBoxes::get_meta($post->ID, 'video_duration');

        // Extract video ID from URL if we have URL but no ID.
        if (empty($video_id) && ! empty($video_url)) {
            $video_id = Helpers::extract_video_id($video_url, $provider);
        }

        // Still no video data after extraction attempt.
        if (empty($video_url) && empty($video_id)) {
            return '<div class="wprh-notice wprh-notice-warning">' .
                esc_html__('No video has been configured for this resource.', 'wp-resource-hub') .
                '</div>';
        }

        $embed_url = '';
        if ($video_id && $provider) {
            $embed_url = Helpers::get_video_embed_url($video_id, $provider);
        }

        ob_start();
?>
        <div class="wprh-resource-video">
            <?php if ($embed_url) : ?>
                <div class="wprh-video-wrapper">
                    <iframe src="<?php echo esc_url($embed_url); ?>" frameborder="0" allowfullscreen
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        title="<?php echo esc_attr(get_the_title($post)); ?>">
                    </iframe>
                </div>
            <?php elseif ('local' === $provider && $video_url) : ?>
                <div class="wprh-video-wrapper wprh-video-local">
                    <video controls>
                        <source src="<?php echo esc_url($video_url); ?>">
                        <?php esc_html_e('Your browser does not support the video tag.', 'wp-resource-hub'); ?>
                    </video>
                </div>
            <?php endif; ?>

            <?php if ($duration) : ?>
                <div class="wprh-video-meta">
                    <span class="wprh-video-duration">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($duration); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (! empty($post->post_content)) : ?>
                <div class="wprh-video-description">
                    <?php echo wp_kses_post(apply_filters('the_content', $post->post_content)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render PDF resource.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string
     */
    private function render_pdf($post)
    {
        $pdf_file    = MetaBoxes::get_meta($post->ID, 'pdf_file');
        $file_size   = MetaBoxes::get_meta($post->ID, 'pdf_file_size');
        $page_count  = MetaBoxes::get_meta($post->ID, 'pdf_page_count');
        $viewer_mode = MetaBoxes::get_meta($post->ID, 'pdf_viewer_mode', 'embedded');

        if (empty($pdf_file)) {
            return '<div class="wprh-notice wprh-notice-warning">' .
                esc_html__('No PDF file has been uploaded for this resource.', 'wp-resource-hub') .
                '</div>';
        }

        $pdf_url = wp_get_attachment_url($pdf_file);

        ob_start();
    ?>
        <div class="wprh-resource-pdf">
            <div class="wprh-pdf-meta">
                <?php if ($file_size) : ?>
                    <span class="wprh-pdf-size">
                        <span class="dashicons dashicons-media-document"></span>
                        <?php echo esc_html($file_size); ?>
                    </span>
                <?php endif; ?>

                <?php if ($page_count) : ?>
                    <span class="wprh-pdf-pages">
                        <span class="dashicons dashicons-media-text"></span>
                        <?php
                        /* translators: %d: Number of pages */
                        printf(esc_html(_n('%d page', '%d pages', $page_count, 'wp-resource-hub')), esc_html($page_count));
                        ?>
                    </span>
                <?php endif; ?>

                <a href="<?php echo esc_url($pdf_url); ?>" class="button wprh-pdf-download" download>
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Download PDF', 'wp-resource-hub'); ?>
                </a>
            </div>

            <?php if ('embedded' === $viewer_mode) : ?>
                <div class="wprh-pdf-viewer">
                    <iframe src="<?php echo esc_url($pdf_url); ?>" type="application/pdf"
                        title="<?php echo esc_attr(get_the_title($post)); ?>">
                        <p><?php esc_html_e('Your browser does not support embedded PDFs.', 'wp-resource-hub'); ?>
                            <a
                                href="<?php echo esc_url($pdf_url); ?>"><?php esc_html_e('Download the PDF', 'wp-resource-hub'); ?></a>
                        </p>
                    </iframe>
                </div>
            <?php endif; ?>

            <?php if (! empty($post->post_content)) : ?>
                <div class="wprh-pdf-description">
                    <?php echo wp_kses_post(apply_filters('the_content', $post->post_content)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render download resource.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string
     */
    private function render_download($post)
    {
        $download_file = MetaBoxes::get_meta($post->ID, 'download_file');
        $file_size     = MetaBoxes::get_meta($post->ID, 'download_file_size');
        $version       = MetaBoxes::get_meta($post->ID, 'download_version');

        if (empty($download_file)) {
            return '<div class="wprh-notice wprh-notice-warning">' .
                esc_html__('No download file has been uploaded for this resource.', 'wp-resource-hub') .
                '</div>';
        }

        $download_url = wp_get_attachment_url($download_file);
        $filename     = basename(get_attached_file($download_file));

        ob_start();
    ?>
        <div class="wprh-resource-download">
            <div class="wprh-download-box">
                <div class="wprh-download-info">
                    <span class="dashicons dashicons-download"></span>
                    <div class="wprh-download-details">
                        <span class="wprh-download-filename"><?php echo esc_html($filename); ?></span>
                        <span class="wprh-download-meta">
                            <?php if ($file_size) : ?>
                                <span class="wprh-download-size"><?php echo esc_html($file_size); ?></span>
                            <?php endif; ?>
                            <?php if ($version) : ?>
                                <span class="wprh-download-version">
                                    <?php
                                    /* translators: %s: Version number */
                                    printf(esc_html__('Version %s', 'wp-resource-hub'), esc_html($version));
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <a href="<?php echo esc_url($download_url); ?>" class="button button-primary wprh-download-button" download>
                    <?php esc_html_e('Download', 'wp-resource-hub'); ?>
                </a>
            </div>

            <?php if (! empty($post->post_content)) : ?>
                <div class="wprh-download-description">
                    <?php echo wp_kses_post(apply_filters('the_content', $post->post_content)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render external link resource.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string
     */
    private function render_external_link($post)
    {
        $external_url = MetaBoxes::get_meta($post->ID, 'external_url');
        $open_new_tab = MetaBoxes::get_meta($post->ID, 'open_new_tab');

        if (empty($external_url)) {
            return '<div class="wprh-notice wprh-notice-warning">' .
                esc_html__('No external URL has been configured for this resource.', 'wp-resource-hub') .
                '</div>';
        }

        $target = $open_new_tab ? '_blank' : '_self';
        $rel    = $open_new_tab ? 'noopener noreferrer' : '';

        ob_start();
    ?>
        <div class="wprh-resource-external-link">
            <div class="wprh-external-link-box">
                <span class="dashicons dashicons-external"></span>
                <div class="wprh-external-link-content">
                    <p><?php esc_html_e('This resource links to an external website:', 'wp-resource-hub'); ?></p>
                    <a href="<?php echo esc_url($external_url); ?>" target="<?php echo esc_attr($target); ?>"
                        rel="<?php echo esc_attr($rel); ?>" class="button button-primary wprh-external-link-button">
                        <?php esc_html_e('Visit External Resource', 'wp-resource-hub'); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </div>
            </div>

            <?php if (! empty($post->post_content)) : ?>
                <div class="wprh-external-link-description">
                    <?php echo wp_kses_post(apply_filters('the_content', $post->post_content)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render internal content resource.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string
     */
    private function render_internal_content($post)
    {
        $show_toc     = MetaBoxes::get_meta($post->ID, 'show_toc');
        $reading_time = MetaBoxes::get_meta($post->ID, 'reading_time');
        $content      = $post->post_content;

        // Generate TOC if enabled.
        $toc_html = '';
        if ($show_toc && ! empty($content)) {
            $toc_data = Helpers::generate_toc($content);
            $toc_html = $toc_data['toc'];
            $content  = $toc_data['content'];
        }

        ob_start();
    ?>
        <div class="wprh-resource-internal-content">
            <?php if ($reading_time) : ?>
                <div class="wprh-reading-time">
                    <span class="dashicons dashicons-clock"></span>
                    <?php
                    /* translators: %d: Number of minutes */
                    printf(esc_html(_n('%d minute read', '%d minute read', $reading_time, 'wp-resource-hub')), esc_html($reading_time));
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($toc_html) : ?>
                <?php echo $toc_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                ?>
            <?php endif; ?>

            <div class="wprh-content-body">
                <?php echo wp_kses_post(apply_filters('the_content', $content)); ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render default resource (fallback).
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Post object.
     * @return string
     */
    private function render_default($post)
    {
        ob_start();
    ?>
        <div class="wprh-resource-default">
            <div class="wprh-content-body">
                <?php echo wp_kses_post(apply_filters('the_content', $post->post_content)); ?>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
