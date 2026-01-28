<?php

/**
 * Single Resource Template.
 *
 * This template can be overridden by copying it to:
 * yourtheme/wp-resource-hub/single-resource.php
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

get_header();

/**
 * Fires before the resource content wrapper.
 *
 * @since 1.0.0
 */
do_action('wprh_before_main_content');
?>

<div id="primary" class="content-area wprh-content-area">
    <main id="main" class="site-main wprh-site-main">

        <?php while (have_posts()) : ?>
            <?php
            the_post();
            global $post;
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('wprh-single-resource'); ?>>

                <?php
                /**
                 * Fires before the resource header.
                 *
                 * @since 1.0.0
                 *
                 * @param WP_Post $post Current post object.
                 */
                do_action('wprh_before_resource_header', $post);
                ?>

                <header class="entry-header wprh-resource-header">
                    <?php the_title('<h1 class="entry-title wprh-resource-title">', '</h1>'); ?>

                    <?php
                    /**
                     * Fires after the resource title.
                     *
                     * @since 1.0.0
                     *
                     * @param WP_Post $post Current post object.
                     */
                    do_action('wprh_after_resource_title', $post);
                    ?>

                    <?php
                    /**
                     * Fires to render resource meta information.
                     *
                     * @since 1.0.0
                     *
                     * @param WP_Post $post Current post object.
                     */
                    do_action('wprh_before_resource_content', $post);
                    ?>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="wprh-resource-thumbnail">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content wprh-resource-content">
                    <?php
                    // Get the single renderer.
                    $renderer = \WPResourceHub\Frontend\SingleRenderer::get_instance();
                    echo $renderer->render($post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>

                <footer class="entry-footer wprh-resource-footer">
                    <?php
                    /**
                     * Fires in the resource footer.
                     *
                     * @since 1.0.0
                     *
                     * @param WP_Post $post Current post object.
                     */
                    do_action('wprh_after_resource_content', $post);
                    ?>

                    <?php
                    // Display taxonomies.
                    $topics = get_the_term_list(get_the_ID(), 'resource_topic', '', ', ', '');
                    $audiences = get_the_term_list(get_the_ID(), 'resource_audience', '', ', ', '');

                    if ($topics || $audiences) :
                    ?>
                        <div class="wprh-resource-taxonomies">
                            <?php if ($topics) : ?>
                                <div class="wprh-resource-topics">
                                    <strong><?php esc_html_e('Topics:', 'wp-resource-hub'); ?></strong>
                                    <?php echo $topics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($audiences) : ?>
                                <div class="wprh-resource-audiences">
                                    <strong><?php esc_html_e('Audience:', 'wp-resource-hub'); ?></strong>
                                    <?php echo $audiences; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </footer>

            </article>

            <?php
            /**
             * Fires after the resource article.
             *
             * @since 1.0.0
             *
             * @param WP_Post $post Current post object.
             */
            do_action('wprh_after_resource_article', $post);
            ?>

        <?php endwhile; ?>

    </main>
</div>

<?php
/**
 * Fires after the resource content wrapper.
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
