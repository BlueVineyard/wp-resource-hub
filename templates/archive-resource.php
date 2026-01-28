<?php

/**
 * Archive Resource Template.
 *
 * This template displays the resource archive page.
 * It can be overridden by copying it to:
 * yourtheme/wp-resource-hub/archive-resource.php
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;
use WPResourceHub\Helpers;
use WPResourceHub\Admin\SettingsPage;

get_header();

/**
 * Fires before the resource archive content wrapper.
 *
 * @since 1.0.0
 */
do_action('wprh_before_main_content');
?>

<div id="primary" class="content-area wprh-content-area wprh-archive">
    <main id="main" class="site-main wprh-site-main">

        <?php if (have_posts()) : ?>

            <header class="page-header wprh-archive-header">
                <?php
                if (is_post_type_archive()) {
                ?>
                    <h1 class="page-title wprh-archive-title">
                        <?php post_type_archive_title(); ?>
                    </h1>
                    <?php
                    $archive_description = get_the_post_type_description();
                    if ($archive_description) {
                    ?>
                        <div class="archive-description wprh-archive-description">
                            <?php echo wp_kses_post($archive_description); ?>
                        </div>
                    <?php
                    }
                } elseif (is_tax()) {
                    $term = get_queried_object();
                    ?>
                    <h1 class="page-title wprh-archive-title">
                        <?php single_term_title(); ?>
                    </h1>
                    <?php
                    if (! empty($term->description)) {
                    ?>
                        <div class="archive-description wprh-archive-description">
                            <?php echo wp_kses_post($term->description); ?>
                        </div>
                <?php
                    }
                }
                ?>

                <?php
                /**
                 * Fires after the archive header.
                 *
                 * @since 1.0.0
                 */
                do_action('wprh_archive_header');
                ?>
            </header>

            <?php
            /**
             * Fires before the resource grid.
             *
             * @since 1.0.0
             */
            do_action('wprh_before_resource_loop');
            ?>

            <div class="wprh-resources-grid wprh-layout-grid wprh-columns-3">
                <?php
                // Check if lightbox mode is enabled.
                $lightbox_mode = SettingsPage::get_setting('frontend', 'video_lightbox_only', true);

                while (have_posts()) :
                    the_post();
                    global $post;

                    $resource_type = ResourceTypeTax::get_resource_type(get_the_ID());
                    $type_slug     = $resource_type ? $resource_type->slug : '';
                    $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon($resource_type) : 'dashicons-media-default';
                ?>

                    <article
                        class="wprh-resource-card wprh-type-<?php echo esc_attr($type_slug); ?> <?php echo ($type_slug === 'video' && $lightbox_mode) ? 'wprh-lightbox-enabled' : ''; ?>"
                        data-id="<?php echo esc_attr(get_the_ID()); ?>">

                        <div class="wprh-card-media">
                            <?php
                            $thumbnail = Helpers::get_resource_thumbnail($post, 'medium_large');

                            // For video types, add play button and video data attributes.
                            if ($type_slug === 'video') :
                                $video_provider = get_post_meta(get_the_ID(), '_wprh_video_provider', true);
                                $video_id = get_post_meta(get_the_ID(), '_wprh_video_id', true);
                                $embed_url = $video_id && $video_provider ? Helpers::get_video_embed_url($video_id, $video_provider) : '';
                            ?>
                                <div class="wprh-card-image wprh-video-card" data-video-url="<?php echo esc_attr($embed_url); ?>"
                                    data-video-title="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php if (! empty($thumbnail)) : ?>
                                        <?php echo $thumbnail; ?>
                                    <?php else : ?>
                                        <div class="wprh-card-placeholder">
                                            <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                                        </div>
                                    <?php endif; ?>
                                    <button class="wprh-play-button"
                                        aria-label="<?php esc_attr_e('Play video', 'wp-resource-hub'); ?>">
                                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="32" cy="32" r="32" fill="rgba(0,0,0,0.7)" />
                                            <path d="M26 20L44 32L26 44V20Z" fill="white" />
                                        </svg>
                                    </button>
                                </div>
                            <?php else : ?>
                                <?php if (! empty($thumbnail)) : ?>
                                    <a href="<?php the_permalink(); ?>" class="wprh-card-image">
                                        <?php echo $thumbnail; ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php the_permalink(); ?>" class="wprh-card-image wprh-card-placeholder">
                                        <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php
                            // Display video duration for video resources.
                            if ($type_slug === 'video') {
                                $duration = get_post_meta(get_the_ID(), '_wprh_video_duration', true);
                                if ($duration) : ?>
                                    <span class="wprh-video-duration-badge">
                                        <?php echo esc_html($duration); ?>
                                    </span>
                            <?php endif;
                            }
                            ?>

                            <?php if ($resource_type) : ?>
                                <span class="wprh-card-type wprh-type-badge-<?php echo esc_attr($type_slug); ?>">
                                    <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                                    <?php echo esc_html($resource_type->name); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="wprh-card-body">
                            <h3 class="wprh-card-title">
                                <?php if ($type_slug === 'video' && $lightbox_mode) : ?>
                                    <span class="wprh-video-card-title"><?php the_title(); ?></span>
                                <?php else : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                <?php endif; ?>
                            </h3>

                            <?php if (has_excerpt() || ! empty($post->post_content)) : ?>
                                <div class="wprh-card-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="wprh-card-footer">
                            <?php
                            // Get topics and audiences.
                            $topics = get_the_terms(get_the_ID(), ResourceTopicTax::get_taxonomy());
                            $audiences = get_the_terms(get_the_ID(), ResourceAudienceTax::get_taxonomy());
                            ?>

                            <?php if ($topics && ! is_wp_error($topics)) : ?>
                                <?php foreach ($topics as $topic) : ?>
                                    <a href="<?php echo esc_url(get_term_link($topic)); ?>" class="wprh-card-pill wprh-pill-topic">
                                        <?php echo esc_html($topic->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($audiences && ! is_wp_error($audiences)) : ?>
                                <?php foreach ($audiences as $audience) : ?>
                                    <a href="<?php echo esc_url(get_term_link($audience)); ?>"
                                        class="wprh-card-pill wprh-pill-audience">
                                        <?php echo esc_html($audience->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </article>

                <?php endwhile; ?>
            </div>

            <!-- Video Lightbox Modal -->
            <div id="wprh-video-lightbox" class="wprh-lightbox" style="display:none;">
                <div class="wprh-lightbox-overlay"></div>
                <div class="wprh-lightbox-content">
                    <button class="wprh-lightbox-close" aria-label="<?php esc_attr_e('Close video', 'wp-resource-hub'); ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                    <div class="wprh-lightbox-video-wrapper">
                        <iframe id="wprh-lightbox-iframe" src="" frameborder="0" allowfullscreen
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                        </iframe>
                    </div>
                </div>
            </div>

            <?php
            /**
             * Fires after the resource grid.
             *
             * @since 1.0.0
             */
            do_action('wprh_after_resource_loop');
            ?>

            <?php
            // Pagination.
            the_posts_pagination(
                array(
                    'mid_size'  => 2,
                    'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__('Previous', 'wp-resource-hub'),
                    'next_text' => esc_html__('Next', 'wp-resource-hub') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                )
            );
            ?>

        <?php else : ?>

            <div class="wprh-no-resources">
                <p><?php esc_html_e('No resources found.', 'wp-resource-hub'); ?></p>
            </div>

        <?php endif; ?>

    </main>
</div>

<?php
/**
 * Fires after the resource archive content wrapper.
 *
 * @since 1.0.0
 */
do_action('wprh_after_main_content');

/**
 * Fires to render the sidebar.
 *
 * @since 1.0.0
 */
do_action('wprh_sidebar');

get_footer();
