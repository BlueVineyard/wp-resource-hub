<?php

/**
 * Collection Shortcode class.
 *
 * Handles the [resource_collection] shortcode for displaying collections.
 *
 * @package WPResourceHub
 * @since   1.2.0
 */

namespace WPResourceHub\Shortcodes;

use WPResourceHub\PostTypes\CollectionPostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Collection Shortcode class.
 *
 * @since 1.2.0
 */
class CollectionShortcode
{

    /**
     * Singleton instance.
     *
     * @var CollectionShortcode|null
     */
    private static $instance = null;

    /**
     * Shortcode tag.
     *
     * @var string
     */
    const TAG = 'resource_collection';

    /**
     * Get the singleton instance.
     *
     * @since 1.2.0
     *
     * @return CollectionShortcode
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
     * @since 1.2.0
     */
    private function __construct()
    {
        add_shortcode(self::TAG, array($this, 'render'));
    }

    /**
     * Get default attributes.
     *
     * @since 1.2.0
     *
     * @return array
     */
    private function get_defaults()
    {
        return array(
            'id'              => 0,
            'slug'            => '',
            'layout'          => '',         // Empty = use collection setting.
            'show_title'      => 'true',
            'show_description' => 'true',
            'show_progress'   => '',         // Empty = use collection setting.
            'show_count'      => 'true',
            'class'           => '',
        );
    }

    /**
     * Render the shortcode.
     *
     * @since 1.2.0
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string
     */
    public function render($atts, $content = '')
    {
        $atts = shortcode_atts($this->get_defaults(), $atts, self::TAG);

        // Normalize boolean attributes.
        $atts['show_title']       = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_description'] = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_count']       = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);

        // Get collection.
        $collection = $this->get_collection($atts);

        if (! $collection) {
            return '<div class="wprh-collection-error">' .
                esc_html__('Collection not found.', 'wp-resource-hub') .
                '</div>';
        }

        // Get collection settings.
        $saved_display_style = get_post_meta($collection->ID, '_wprh_collection_display_style', true);
        $display_style       = ! empty($atts['layout']) ? $atts['layout'] : ($saved_display_style ? $saved_display_style : 'list');

        $saved_show_progress = get_post_meta($collection->ID, '_wprh_collection_show_progress', true);
        $show_progress       = $atts['show_progress'] !== '' ? filter_var($atts['show_progress'], FILTER_VALIDATE_BOOLEAN) : (bool) $saved_show_progress;

        // Get resources.
        $resource_ids = CollectionPostType::get_collection_resources($collection->ID);

        // Enqueue assets.
        wp_enqueue_style('wprh-frontend', WPRH_PLUGIN_URL . 'assets/css/frontend.css', array(), WPRH_VERSION);

        ob_start();
?>
        <div class="wprh-collection wprh-collection-<?php echo esc_attr($display_style); ?> <?php echo esc_attr($atts['class']); ?>"
            data-collection-id="<?php echo esc_attr($collection->ID); ?>">

            <?php if ($atts['show_title'] || $atts['show_description'] || $atts['show_count']) : ?>
                <header class="wprh-collection-header">
                    <?php if ($atts['show_title']) : ?>
                        <h2 class="wprh-collection-title">
                            <a href="<?php echo esc_url(get_permalink($collection)); ?>">
                                <?php echo esc_html(get_the_title($collection)); ?>
                            </a>
                        </h2>
                    <?php endif; ?>

                    <?php if ($atts['show_count']) : ?>
                        <span class="wprh-collection-count">
                            <?php
                            /* translators: %d: Number of resources */
                            printf(esc_html(_n('%d resource', '%d resources', count($resource_ids), 'wp-resource-hub')), count($resource_ids));
                            ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($atts['show_description'] && has_excerpt($collection)) : ?>
                        <div class="wprh-collection-description">
                            <?php echo wp_kses_post(get_the_excerpt($collection)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_progress) : ?>
                        <div class="wprh-collection-progress">
                            <div class="wprh-progress-bar">
                                <div class="wprh-progress-fill" style="width: 0%;"></div>
                            </div>
                            <span class="wprh-progress-text">0 / <?php echo esc_html(count($resource_ids)); ?></span>
                        </div>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <?php if (empty($resource_ids)) : ?>
                <div class="wprh-collection-empty">
                    <?php esc_html_e('This collection has no resources yet.', 'wp-resource-hub'); ?>
                </div>
            <?php else : ?>
                <?php echo $this->render_resources($resource_ids, $display_style); ?>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Get collection by ID or slug.
     *
     * @since 1.2.0
     *
     * @param array $atts Shortcode attributes.
     * @return \WP_Post|null
     */
    private function get_collection($atts)
    {
        if (! empty($atts['id'])) {
            $post = get_post(absint($atts['id']));
            if ($post && CollectionPostType::get_post_type() === $post->post_type) {
                return $post;
            }
        }

        if (! empty($atts['slug'])) {
            $posts = get_posts(
                array(
                    'post_type'      => CollectionPostType::get_post_type(),
                    'name'           => sanitize_title($atts['slug']),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                )
            );
            if (! empty($posts)) {
                return $posts[0];
            }
        }

        return null;
    }

    /**
     * Render collection resources.
     *
     * @since 1.2.0
     *
     * @param array  $resource_ids Resource IDs.
     * @param string $layout       Display layout.
     * @return string
     */
    private function render_resources($resource_ids, $layout)
    {
        ob_start();
    ?>
        <div class="wprh-collection-resources wprh-layout-<?php echo esc_attr($layout); ?>">
            <?php
            $index = 0;
            foreach ($resource_ids as $resource_id) :
                $resource = get_post($resource_id);
                if (! $resource || 'publish' !== $resource->post_status) {
                    continue;
                }
                $index++;
                echo $this->render_resource_item($resource, $index, $layout);
            endforeach;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single resource item in the collection.
     *
     * @since 1.2.0
     *
     * @param \WP_Post $resource Resource post.
     * @param int      $index    Item index.
     * @param string   $layout   Display layout.
     * @return string
     */
    private function render_resource_item($resource, $index, $layout)
    {
        $resource_type = ResourceTypeTax::get_resource_type($resource->ID);
        $type_slug     = $resource_type ? $resource_type->slug : '';
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon($resource_type) : 'dashicons-media-default';

        ob_start();

        if ('playlist' === $layout) :
        ?>
            <div class="wprh-playlist-item wprh-type-<?php echo esc_attr($type_slug); ?>"
                data-resource-id="<?php echo esc_attr($resource->ID); ?>">
                <span class="wprh-playlist-number"><?php echo esc_html($index); ?></span>
                <span class="wprh-playlist-icon dashicons <?php echo esc_attr($type_icon); ?>"></span>
                <div class="wprh-playlist-info">
                    <a href="<?php echo esc_url(get_permalink($resource)); ?>" class="wprh-playlist-title">
                        <?php echo esc_html(get_the_title($resource)); ?>
                    </a>
                    <?php if ($resource_type) : ?>
                        <span class="wprh-playlist-type"><?php echo esc_html($resource_type->name); ?></span>
                    <?php endif; ?>
                </div>
                <span class="wprh-playlist-status dashicons dashicons-yes" style="display: none;"></span>
            </div>
        <?php
        elseif ('grid' === $layout) :
        ?>
            <article class="wprh-resource-card wprh-collection-card wprh-type-<?php echo esc_attr($type_slug); ?>"
                data-resource-id="<?php echo esc_attr($resource->ID); ?>">
                <div class="wprh-card-media">
                    <?php if (has_post_thumbnail($resource)) : ?>
                        <a href="<?php echo esc_url(get_permalink($resource)); ?>" class="wprh-card-image">
                            <?php echo get_the_post_thumbnail($resource, 'medium'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(get_permalink($resource)); ?>" class="wprh-card-image wprh-card-placeholder">
                            <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                        </a>
                    <?php endif; ?>

                    <span class="wprh-card-number"><?php echo esc_html($index); ?></span>

                    <?php if ($resource_type) : ?>
                        <span class="wprh-card-type">
                            <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="wprh-card-body">
                    <h3 class="wprh-card-title">
                        <a href="<?php echo esc_url(get_permalink($resource)); ?>">
                            <?php echo esc_html(get_the_title($resource)); ?>
                        </a>
                    </h3>
                </div>
            </article>
        <?php
        else : // list layout
        ?>
            <div class="wprh-list-item wprh-type-<?php echo esc_attr($type_slug); ?>"
                data-resource-id="<?php echo esc_attr($resource->ID); ?>">
                <span class="wprh-list-number"><?php echo esc_html($index); ?></span>

                <?php if (has_post_thumbnail($resource)) : ?>
                    <div class="wprh-list-thumbnail">
                        <a href="<?php echo esc_url(get_permalink($resource)); ?>">
                            <?php echo get_the_post_thumbnail($resource, 'thumbnail'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="wprh-list-thumbnail wprh-list-placeholder">
                        <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                    </div>
                <?php endif; ?>

                <div class="wprh-list-content">
                    <h3 class="wprh-list-title">
                        <a href="<?php echo esc_url(get_permalink($resource)); ?>">
                            <?php echo esc_html(get_the_title($resource)); ?>
                        </a>
                    </h3>
                    <?php if (has_excerpt($resource)) : ?>
                        <p class="wprh-list-excerpt"><?php echo wp_trim_words(get_the_excerpt($resource), 15, '...'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="wprh-list-meta">
                    <?php if ($resource_type) : ?>
                        <span class="wprh-list-type">
                            <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
                            <?php echo esc_html($resource_type->name); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <a href="<?php echo esc_url(get_permalink($resource)); ?>" class="wprh-list-link">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
<?php
        endif;

        return ob_get_clean();
    }
}
