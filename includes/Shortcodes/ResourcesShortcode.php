<?php

/**
 * Resources Shortcode class.
 *
 * Handles the [resources] shortcode for displaying resource grids/lists.
 *
 * @package WPResourceHub
 * @since   1.2.0
 */

namespace WPResourceHub\Shortcodes;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Taxonomies\ResourceTopicTax;
use WPResourceHub\Taxonomies\ResourceAudienceTax;
use WPResourceHub\Admin\SettingsPage;
use WPResourceHub\Helpers;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resources Shortcode class.
 *
 * @since 1.2.0
 */
class ResourcesShortcode
{

    /**
     * Singleton instance.
     *
     * @var ResourcesShortcode|null
     */
    private static $instance = null;

    /**
     * Shortcode tag.
     *
     * @var string
     */
    const TAG = 'resources';

    /**
     * Get the singleton instance.
     *
     * @since 1.2.0
     *
     * @return ResourcesShortcode
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
        add_action('wp_ajax_wprh_filter_resources', array($this, 'ajax_filter'));
        add_action('wp_ajax_nopriv_wprh_filter_resources', array($this, 'ajax_filter'));
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
            'layout'          => SettingsPage::get_setting('frontend', 'default_layout', 'grid'),
            'columns'         => 3,
            'limit'           => SettingsPage::get_setting('frontend', 'items_per_page', 12),
            'type'            => '',
            'topic'           => '',
            'audience'        => '',
            'orderby'         => SettingsPage::get_setting('general', 'default_ordering', 'date'),
            'order'           => 'DESC',
            'show_filters'    => 'true',
            'show_type_filter'     => SettingsPage::get_setting('frontend', 'enable_type_filter', true) ? 'true' : 'false',
            'show_topic_filter'    => SettingsPage::get_setting('frontend', 'enable_topic_filter', true) ? 'true' : 'false',
            'show_audience_filter' => SettingsPage::get_setting('frontend', 'enable_audience_filter', true) ? 'true' : 'false',
            'show_duration_filter' => SettingsPage::get_setting('frontend', 'enable_duration_filter', true) ? 'true' : 'false',
            'show_sort_filter'     => SettingsPage::get_setting('frontend', 'enable_sort_filter', true) ? 'true' : 'false',
            'show_layout_toggle'   => SettingsPage::get_setting('frontend', 'enable_layout_toggle', true) ? 'true' : 'false',
            'show_search'     => SettingsPage::get_setting('frontend', 'enable_search', true) ? 'true' : 'false',
            'show_pagination' => 'true',
            'featured_only'   => 'false',
            'exclude'         => '',
            'include'         => '',
            'class'           => '',
            'id'              => '',
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

        // Auto-detect taxonomy context on archive pages.
        if (is_tax()) {
            $queried = get_queried_object();
            if ($queried instanceof \WP_Term) {
                $tax_map = array(
                    ResourceTypeTax::get_taxonomy()     => 'type',
                    ResourceTopicTax::get_taxonomy()    => 'topic',
                    ResourceAudienceTax::get_taxonomy() => 'audience',
                );
                if (isset($tax_map[$queried->taxonomy]) && empty($atts[$tax_map[$queried->taxonomy]])) {
                    $atts[$tax_map[$queried->taxonomy]] = $queried->slug;
                }
            }
        }

        // Normalize boolean attributes.
        $atts['show_filters']         = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_type_filter']     = filter_var($atts['show_type_filter'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_topic_filter']    = filter_var($atts['show_topic_filter'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_audience_filter'] = filter_var($atts['show_audience_filter'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_duration_filter'] = filter_var($atts['show_duration_filter'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_sort_filter']     = filter_var($atts['show_sort_filter'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_layout_toggle']   = filter_var($atts['show_layout_toggle'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_search']          = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_pagination']      = filter_var($atts['show_pagination'], FILTER_VALIDATE_BOOLEAN);
        $atts['featured_only']        = filter_var($atts['featured_only'], FILTER_VALIDATE_BOOLEAN);

        // Enqueue assets.
        $this->enqueue_assets();

        // Build query args.
        $query_args = $this->build_query_args($atts);

        // Get resources.
        $query = new \WP_Query($query_args);

        // Generate unique ID for this instance.
        $instance_id = ! empty($atts['id']) ? $atts['id'] : 'wprh-resources-' . wp_unique_id();

        ob_start();
?>
<?php
        $container_classes = 'wprh-resources-container ' . $atts['class'];
        if (! SettingsPage::get_setting('frontend', 'show_card_footer', true)) {
            $container_classes .= ' wprh-hide-card-footer';
        }
        ?>
<div id="<?php echo esc_attr($instance_id); ?>" class="<?php echo esc_attr(trim($container_classes)); ?>"
    data-atts="<?php echo esc_attr(wp_json_encode($atts)); ?>">

    <?php if ($atts['show_filters'] || $atts['show_search']) : ?>
    <?php
                $filter_order = SettingsPage::get_setting('frontend', 'filter_order', array('search', 'type', 'topic', 'audience', 'duration', 'sort', 'layout_toggle'));
                if (! is_array($filter_order)) {
                    $filter_order = array('search', 'type', 'topic', 'audience', 'duration', 'sort', 'layout_toggle');
                }
                $featured_filters = SettingsPage::get_setting('frontend', 'featured_filters', array());
                if (! is_array($featured_filters)) {
                    $featured_filters = array();
                }
                ?>
    <div class="wprh-resources-toolbar">
        <?php foreach ($filter_order as $filter_key) :
                        switch ($filter_key):
                            case 'search':
                                if ($atts['show_search']) : ?>
        <div class="wprh-resources-search<?php echo in_array('search', $featured_filters, true) ? ' wprh-filter-featured' : ''; ?>">
            <input type="text" class="wprh-search-input"
                placeholder="<?php esc_attr_e('Search resources...', 'wp-resource-hub'); ?>" value="">
            <span class="wprh-search-icon dashicons dashicons-search"></span>
        </div>
        <?php endif;
                                break;

                            case 'type':
                                if ($atts['show_filters'] && $atts['show_type_filter']) :
                                    echo $this->render_filter_dropdown('type', ResourceTypeTax::get_taxonomy(), __('All Types', 'wp-resource-hub'), $atts['type']);
                                endif;
                                break;

                            case 'topic':
                                if ($atts['show_filters'] && $atts['show_topic_filter']) :
                                    echo $this->render_filter_dropdown('topic', ResourceTopicTax::get_taxonomy(), __('All Topics', 'wp-resource-hub'), $atts['topic']);
                                endif;
                                break;

                            case 'audience':
                                if ($atts['show_filters'] && $atts['show_audience_filter']) :
                                    echo $this->render_filter_dropdown('audience', ResourceAudienceTax::get_taxonomy(), __('All Audiences', 'wp-resource-hub'), $atts['audience']);
                                endif;
                                break;

                            case 'duration':
                                if ($atts['show_filters'] && $atts['show_duration_filter']) :
                                    echo $this->render_duration_filter();
                                endif;
                                break;

                            case 'sort':
                                if ($atts['show_filters'] && $atts['show_sort_filter']) :
                                    echo $this->render_sort_filter($atts['orderby']);
                                endif;
                                break;

                            case 'layout_toggle':
                                if ($atts['show_filters'] && $atts['show_layout_toggle']) : ?>
        <div class="wprh-layout-toggle<?php echo in_array('layout_toggle', $featured_filters, true) ? ' wprh-filter-featured' : ''; ?>">
            <button type="button" class="wprh-layout-btn <?php echo 'grid' === $atts['layout'] ? 'is-active' : ''; ?>"
                data-layout="grid" aria-label="<?php esc_attr_e('Grid view', 'wp-resource-hub'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px"
                    fill="currentColor">
                    <path
                        d="M120-510v-330h330v330H120Zm0 390v-330h330v330H120Zm390-390v-330h330v330H510Zm0 390v-330h330v330H510ZM180-570h210v-210H180v210Zm390 0h210v-210H570v210Zm0 390h210v-210H570v210Zm-390 0h210v-210H180v210Zm390-390Zm0 180Zm-180 0Zm0-180Z" />
                </svg>
            </button>
            <button type="button" class="wprh-layout-btn <?php echo 'list' === $atts['layout'] ? 'is-active' : ''; ?>"
                data-layout="list" aria-label="<?php esc_attr_e('List view', 'wp-resource-hub'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px"
                    fill="currentColor">
                    <path
                        d="M350-220h470v-137H350v137ZM140-603h150v-137H140v137Zm0 187h150v-127H140v127Zm0 196h150v-137H140v137Zm210-196h470v-127H350v127Zm0-187h470v-137H350v137ZM140-160q-24 0-42-18t-18-42v-520q0-24 18-42t42-18h680q24 0 42 18t18 42v520q0 24-18 42t-42 18H140Z" />
                </svg>
            </button>
        </div>
        <?php endif;
                                break;
                        endswitch;
                    endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="wprh-resources-grid-wrapper">
        <div class="wprh-resources-loading" style="display: none;">
            <span class="wprh-spinner"></span>
            <?php esc_html_e('Loading...', 'wp-resource-hub'); ?>
        </div>

        <?php echo $this->render_resources($query, $atts); ?>
    </div>

    <?php if ($atts['show_pagination'] && $query->max_num_pages > 1) : ?>
    <div class="wprh-resources-pagination">
        <?php echo $this->render_pagination($query, 1); ?>
    </div>
    <?php endif; ?>
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
        return ob_get_clean();
    }

    /**
     * Build query arguments.
     *
     * @since 1.2.0
     *
     * @param array $atts Shortcode attributes.
     * @param int   $paged Current page number.
     * @return array
     */
    private function build_query_args($atts, $paged = 1)
    {
        $args = array(
            'post_type'      => ResourcePostType::get_post_type(),
            'post_status'    => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'paged'          => $paged,
            'orderby'        => $atts['orderby'],
            'order'          => strtoupper($atts['order']),
        );

        // Tax query.
        $tax_query = array();

        if (! empty($atts['type'])) {
            $tax_query[] = array(
                'taxonomy' => ResourceTypeTax::get_taxonomy(),
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['type'])),
            );
        }

        if (! empty($atts['topic'])) {
            $tax_query[] = array(
                'taxonomy' => ResourceTopicTax::get_taxonomy(),
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['topic'])),
            );
        }

        if (! empty($atts['audience'])) {
            $tax_query[] = array(
                'taxonomy' => ResourceAudienceTax::get_taxonomy(),
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['audience'])),
            );
        }

        if (! empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // Meta query.
        $meta_query = array();

        // Featured filter.
        if ($atts['featured_only']) {
            $meta_query[] = array(
                'key'   => '_wprh_featured',
                'value' => '1',
            );
        }

        // Duration filter (for videos only).
        if (! empty($atts['duration'])) {
            // First, ensure we're filtering only video types.
            if (empty($atts['type']) || strpos($atts['type'], 'video') !== false) {
                $tax_query[] = array(
                    'taxonomy' => ResourceTypeTax::get_taxonomy(),
                    'field'    => 'slug',
                    'terms'    => 'video',
                );

                if (! empty($tax_query)) {
                    $tax_query['relation'] = 'AND';
                    $args['tax_query'] = $tax_query;
                }
            }

            // Add meta query to check duration exists.
            $meta_query[] = array(
                'key'     => '_wprh_video_duration',
                'compare' => 'EXISTS',
            );
        }

        if (! empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        // Include/exclude.
        if (! empty($atts['include'])) {
            $args['post__in'] = array_map('absint', explode(',', $atts['include']));
        }

        if (! empty($atts['exclude'])) {
            $args['post__not_in'] = array_map('absint', explode(',', $atts['exclude']));
        }

        // Search.
        if (! empty($atts['search'])) {
            $args['s'] = sanitize_text_field($atts['search']);
        }

        /**
         * Filter the resources query arguments.
         *
         * @since 1.2.0
         *
         * @param array $args Query arguments.
         * @param array $atts Shortcode attributes.
         */
        return apply_filters('wprh_resources_query_args', $args, $atts);
    }

    /**
     * Render resources grid/list.
     *
     * @since 1.2.0
     *
     * @param \WP_Query $query Query object.
     * @param array     $atts  Shortcode attributes.
     * @return string
     */
    public function render_resources($query, $atts)
    {
        if (! $query->have_posts()) {
            return '<div class="wprh-no-resources">' .
                esc_html__('No resources found.', 'wp-resource-hub') .
                '</div>';
        }

        $layout_class = 'wprh-layout-' . esc_attr($atts['layout']);
        $columns_class = 'wprh-columns-' . intval($atts['columns']);

        ob_start();
    ?>
<div class="wprh-resources-grid <?php echo esc_attr($layout_class . ' ' . $columns_class); ?>">
    <?php while ($query->have_posts()) : ?>
    <?php $query->the_post(); ?>
    <?php echo $this->render_resource_card(get_post(), $atts); ?>
    <?php endwhile; ?>
    <?php wp_reset_postdata(); ?>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Render a single resource card.
     *
     * @since 1.2.0
     *
     * @param \WP_Post $post Resource post.
     * @param array    $atts Shortcode attributes.
     * @return string
     */
    private function render_resource_card($post, $atts)
    {
        $resource_type = ResourceTypeTax::get_resource_type($post->ID);
        $type_slug     = $resource_type ? $resource_type->slug : '';
        $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon($resource_type) : 'dashicons-media-default';

        // Check if lightbox mode is enabled.
        $lightbox_mode = SettingsPage::get_setting('frontend', 'video_lightbox_only', true);

        ob_start();
    ?>
<article
    class="wprh-resource-card wprh-type-<?php echo esc_attr($type_slug); ?> <?php echo ($type_slug === 'video' && $lightbox_mode) ? 'wprh-lightbox-enabled' : ''; ?>"
    data-id="<?php echo esc_attr($post->ID); ?>">
    <div class="wprh-card-media">
        <?php
                $thumbnail = Helpers::get_resource_thumbnail($post, 'medium_large');

                // For video types, add play button and video data attributes.
                if ($type_slug === 'video') :
                    $video_provider = get_post_meta($post->ID, '_wprh_video_provider', true);
                    $video_id = get_post_meta($post->ID, '_wprh_video_id', true);
                    $embed_url = $video_id && $video_provider ? Helpers::get_video_embed_url($video_id, $video_provider) : '';
                ?>
        <div class="wprh-card-image wprh-video-card" data-video-url="<?php echo esc_attr($embed_url); ?>"
            data-video-title="<?php echo esc_attr(get_the_title($post)); ?>">
            <?php if (! empty($thumbnail)) : ?>
            <?php echo $thumbnail; ?>
            <?php else : ?>
            <div class="wprh-card-placeholder">
                <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
            </div>
            <?php endif; ?>
            <button class="wprh-play-button" aria-label="<?php esc_attr_e('Play video', 'wp-resource-hub'); ?>">
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="32" fill="rgba(0,0,0,0.7)" />
                    <path d="M26 20L44 32L26 44V20Z" fill="white" />
                </svg>
            </button>
        </div>
        <?php else : ?>
        <?php if (! empty($thumbnail)) : ?>
        <a href="<?php echo esc_url(get_permalink($post)); ?>" class="wprh-card-image">
            <?php echo $thumbnail; ?>
        </a>
        <?php else : ?>
        <a href="<?php echo esc_url(get_permalink($post)); ?>" class="wprh-card-image wprh-card-placeholder">
            <span class="dashicons <?php echo esc_attr($type_icon); ?>"></span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php
                // Display video duration for video resources.
                if ($type_slug === 'video') {
                    $duration = get_post_meta($post->ID, '_wprh_video_duration', true);
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
            <span class="wprh-video-card-title"><?php echo esc_html(get_the_title($post)); ?></span>
            <?php else : ?>
            <a href="<?php echo esc_url(get_permalink($post)); ?>">
                <?php echo esc_html(get_the_title($post)); ?>
            </a>
            <?php endif; ?>
        </h3>

        <?php if (has_excerpt($post) || ! empty($post->post_content)) : ?>
        <div class="wprh-card-excerpt">
            <?php echo wp_trim_words(get_the_excerpt($post), 20, '...'); ?>
        </div>
        <?php endif; ?>

    </div>

    <div class="wprh-card-footer">
        <?php
                // Get topics and audiences.
                $topics = get_the_terms($post->ID, ResourceTopicTax::get_taxonomy());
                $audiences = get_the_terms($post->ID, ResourceAudienceTax::get_taxonomy());
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
        <a href="<?php echo esc_url(get_term_link($audience)); ?>" class="wprh-card-pill wprh-pill-audience">
            <?php echo esc_html($audience->name); ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</article>
<?php
        return ob_get_clean();
    }

    /**
     * Render filter dropdown.
     *
     * @since 1.2.0
     *
     * @param string $filter_key Filter key.
     * @param string $taxonomy   Taxonomy name.
     * @param string $all_label  Label for "all" option.
     * @param string $selected   Selected value.
     * @return string
     */
    private function render_filter_dropdown($filter_key, $taxonomy, $all_label, $selected = '')
    {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            )
        );

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $selected_label = $all_label;
        foreach ($terms as $term) {
            if ($term->slug === $selected) {
                $selected_label = $term->name;
                break;
            }
        }

        $options = array();
        $options[] = array('value' => '', 'label' => $all_label, 'count' => 0, 'depth' => 0);

        // Check if taxonomy is hierarchical.
        $tax_obj = get_taxonomy($taxonomy);
        if ($tax_obj && $tax_obj->hierarchical) {
            $this->build_hierarchical_options($terms, $options, 0, 0);
        } else {
            foreach ($terms as $term) {
                $options[] = array('value' => $term->slug, 'label' => $term->name, 'count' => $term->count, 'depth' => 0);
            }
        }

        return $this->render_custom_dropdown($filter_key, $options, $selected, $selected_label);
    }

    /**
     * Build hierarchical options array from terms.
     *
     * @since 1.4.0
     *
     * @param array $terms   All terms.
     * @param array &$options Options array to populate.
     * @param int   $parent  Parent term ID.
     * @param int   $depth   Current depth.
     * @return void
     */
    private function build_hierarchical_options($terms, &$options, $parent, $depth)
    {
        foreach ($terms as $term) {
            if ((int) $term->parent !== $parent) {
                continue;
            }
            $options[] = array(
                'value' => $term->slug,
                'label' => $term->name,
                'count' => $term->count,
                'depth' => $depth,
            );
            $this->build_hierarchical_options($terms, $options, $term->term_id, $depth + 1);
        }
    }

    /**
     * Render duration filter dropdown.
     *
     * @since 1.2.0
     *
     * @return string
     */
    private function render_duration_filter()
    {
        $options = array(
            array('value' => '',     'label' => __('All Durations', 'wp-resource-hub')),
            array('value' => '0-5',  'label' => __('Under 5 minutes', 'wp-resource-hub')),
            array('value' => '5-15', 'label' => __('5-15 minutes', 'wp-resource-hub')),
            array('value' => '15-30', 'label' => __('15-30 minutes', 'wp-resource-hub')),
            array('value' => '30+',  'label' => __('30+ minutes', 'wp-resource-hub')),
        );

        return $this->render_custom_dropdown('duration', $options, '', $options[0]['label']);
    }

    /**
     * Render sort filter dropdown.
     *
     * @since 1.2.0
     *
     * @param string $current Current sort value.
     * @return string
     */
    private function render_sort_filter($current = 'date')
    {
        $raw_options = array(
            'date'       => __('Newest First', 'wp-resource-hub'),
            'title-asc'  => __('Title (A-Z)', 'wp-resource-hub'),
            'title-desc' => __('Title (Z-A)', 'wp-resource-hub'),
            'modified'   => __('Recently Updated', 'wp-resource-hub'),
        );

        $options = array();
        $selected_label = $raw_options['date'];
        foreach ($raw_options as $value => $label) {
            $options[] = array('value' => $value, 'label' => $label);
            if ($value === $current) {
                $selected_label = $label;
            }
        }

        return $this->render_custom_dropdown('sort', $options, $current, $selected_label);
    }

    /**
     * Render a custom dropdown component.
     *
     * @since 1.4.0
     *
     * @param string $filter_key     Filter identifier.
     * @param array  $options        Array of options with 'value', 'label', and optional 'count'.
     * @param string $selected_value Currently selected value.
     * @param string $selected_label Label for the currently selected value.
     * @return string
     */
    private function render_custom_dropdown($filter_key, $options, $selected_value, $selected_label)
    {
        $featured_filters = SettingsPage::get_setting('frontend', 'featured_filters', array());
        $is_featured = is_array($featured_filters) && in_array($filter_key, $featured_filters, true);
        ob_start();
    ?>
<div class="wprh-custom-dropdown<?php echo $is_featured ? ' wprh-filter-featured' : ''; ?>" data-filter="<?php echo esc_attr($filter_key); ?>">
    <select class="wprh-filter-select" data-filter="<?php echo esc_attr($filter_key); ?>" hidden aria-hidden="true"
        tabindex="-1">
        <?php foreach ($options as $opt) : ?>
        <option value="<?php echo esc_attr($opt['value']); ?>" <?php selected($selected_value, $opt['value']); ?>>
            <?php echo esc_html($opt['label']); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="button" class="wprh-dropdown-trigger" aria-expanded="false" aria-haspopup="listbox">
        <span class="wprh-dropdown-label"><?php echo esc_html($selected_label); ?></span>
        <svg class="wprh-dropdown-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </button>
    <div class="wprh-dropdown-menu" role="listbox">
        <?php foreach ($options as $opt) :
                    $depth = isset($opt['depth']) ? (int) $opt['depth'] : 0;
                    $depth_class = $depth > 0 ? ' wprh-dropdown-depth-' . $depth : '';
                    $indent_style = $depth > 0 ? ' style="padding-left: ' . (12 + $depth * 20) . 'px;"' : '';
                ?>
        <div class="wprh-dropdown-option<?php echo esc_attr($depth_class); ?> <?php echo $selected_value === $opt['value'] ? 'is-selected' : ''; ?>"
            data-value="<?php echo esc_attr($opt['value']); ?>" role="option" <?php echo $indent_style; ?>>
            <span class="wprh-dropdown-option-label"><?php echo esc_html($opt['label']); ?></span>
            <?php if (! empty($opt['count'])) : ?>
            <span class="wprh-dropdown-count"><?php echo esc_html($opt['count']); ?></span>
            <?php endif; ?>
            <svg class="wprh-dropdown-check" width="14" height="14" viewBox="0 0 14 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M11.5 3.5L5.5 10L2.5 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Render pagination.
     *
     * @since 1.2.0
     *
     * @param \WP_Query $query   Query object.
     * @param int       $current Current page.
     * @return string
     */
    public function render_pagination($query, $current = 1)
    {
        $total = $query->max_num_pages;

        if ($total <= 1) {
            return '';
        }

        ob_start();
    ?>
<div class="wprh-pagination" data-current="<?php echo esc_attr($current); ?>"
    data-total="<?php echo esc_attr($total); ?>">
    <button type="button" class="wprh-page-btn wprh-prev" <?php disabled($current, 1); ?>>
        <span class="dashicons dashicons-arrow-left-alt2"></span>
        <?php esc_html_e('Previous', 'wp-resource-hub'); ?>
    </button>

    <span class="wprh-page-info">
        <?php
                /* translators: 1: Current page, 2: Total pages */
                printf(esc_html__('Page %1$d of %2$d', 'wp-resource-hub'), $current, $total);
                ?>
    </span>

    <button type="button" class="wprh-page-btn wprh-next" <?php disabled($current, $total); ?>>
        <?php esc_html_e('Next', 'wp-resource-hub'); ?>
        <span class="dashicons dashicons-arrow-right-alt2"></span>
    </button>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * AJAX filter handler.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public function ajax_filter()
    {
        check_ajax_referer('wprh_frontend_nonce', 'nonce');

        $atts = isset($_POST['atts']) ? json_decode(stripslashes($_POST['atts']), true) : array();
        $atts = shortcode_atts($this->get_defaults(), $atts);

        // Override with filter values.
        if (isset($_POST['type'])) {
            $atts['type'] = sanitize_text_field($_POST['type']);
        }
        if (isset($_POST['topic'])) {
            $atts['topic'] = sanitize_text_field($_POST['topic']);
        }
        if (isset($_POST['audience'])) {
            $atts['audience'] = sanitize_text_field($_POST['audience']);
        }
        if (isset($_POST['duration'])) {
            $atts['duration'] = sanitize_text_field($_POST['duration']);
        }
        if (isset($_POST['sort'])) {
            $sort_value = sanitize_text_field($_POST['sort']);

            // Handle title sorting with direction.
            if ($sort_value === 'title-asc') {
                $atts['orderby'] = 'title';
                $atts['order'] = 'ASC';
            } elseif ($sort_value === 'title-desc') {
                $atts['orderby'] = 'title';
                $atts['order'] = 'DESC';
            } else {
                $atts['orderby'] = $sort_value;
                $atts['order'] = 'DESC';
            }
        }
        if (isset($_POST['search'])) {
            $atts['search'] = sanitize_text_field($_POST['search']);
        }

        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

        // Build query.
        $query_args = $this->build_query_args($atts, $paged);
        $query = new \WP_Query($query_args);

        wp_send_json_success(
            array(
                'html'       => $this->render_resources($query, $atts),
                'pagination' => $this->render_pagination($query, $paged),
                'found'      => $query->found_posts,
                'max_pages'  => $query->max_num_pages,
            )
        );
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 1.2.0
     *
     * @return void
     */
    private function enqueue_assets()
    {
        wp_enqueue_style(
            'wprh-frontend',
            WPRH_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WPRH_VERSION
        );

        wp_enqueue_script(
            'wprh-frontend',
            WPRH_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WPRH_VERSION,
            true
        );

        wp_localize_script(
            'wprh-frontend',
            'wprhFrontend',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('wprh_frontend_nonce'),
                'i18n'    => array(
                    'loading'    => __('Loading...', 'wp-resource-hub'),
                    'noResults'  => __('No resources found.', 'wp-resource-hub'),
                    'error'      => __('An error occurred. Please try again.', 'wp-resource-hub'),
                ),
            )
        );
    }
}