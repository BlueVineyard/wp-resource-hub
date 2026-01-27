<?php
/**
 * Single Resource Template - Internal Content Type.
 *
 * This template is used specifically for Internal Content type resources.
 * It can be overridden by copying it to:
 * yourtheme/wp-resource-hub/single-resource-internal-content.php
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPResourceHub\Admin\MetaBoxes;
use WPResourceHub\Helpers;

get_header();

/**
 * Fires before the resource content wrapper.
 *
 * @since 1.0.0
 */
do_action( 'wprh_before_main_content' );
?>

<div id="primary" class="content-area wprh-content-area wprh-internal-content">
    <main id="main" class="site-main wprh-site-main">

        <?php while ( have_posts() ) : ?>
            <?php the_post(); ?>

            <?php
            // Get meta values.
            $show_toc     = MetaBoxes::get_meta( get_the_ID(), 'show_toc' );
            $reading_time = MetaBoxes::get_meta( get_the_ID(), 'reading_time' );
            $show_related = MetaBoxes::get_meta( get_the_ID(), 'show_related' );
            $summary      = MetaBoxes::get_meta( get_the_ID(), 'summary' );

            // Prepare content with TOC if enabled.
            $content  = get_the_content();
            $toc_html = '';

            if ( $show_toc && ! empty( $content ) ) {
                $toc_data = Helpers::generate_toc( $content );
                $toc_html = $toc_data['toc'];
                $content  = $toc_data['content'];
            }
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'wprh-single-resource wprh-article-layout' ); ?>>

                <?php
                /**
                 * Fires before the resource header.
                 *
                 * @since 1.0.0
                 *
                 * @param WP_Post $post Current post object.
                 */
                do_action( 'wprh_before_resource_header', $post );
                ?>

                <header class="entry-header wprh-resource-header wprh-article-header">
                    <?php the_title( '<h1 class="entry-title wprh-resource-title">', '</h1>' ); ?>

                    <div class="wprh-article-meta">
                        <?php if ( $reading_time ) : ?>
                            <span class="wprh-reading-time">
                                <span class="dashicons dashicons-clock"></span>
                                <?php
                                /* translators: %d: Number of minutes */
                                printf( esc_html( _n( '%d min read', '%d min read', $reading_time, 'wp-resource-hub' ) ), esc_html( $reading_time ) );
                                ?>
                            </span>
                        <?php endif; ?>

                        <span class="wprh-publish-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html( get_the_date() ); ?>
                        </span>

                        <?php
                        /**
                         * Fires after resource meta items in header.
                         *
                         * @since 1.0.0
                         *
                         * @param WP_Post $post Current post object.
                         */
                        do_action( 'wprh_internal_content_meta', $post );
                        ?>
                    </div>

                    <?php if ( $summary ) : ?>
                        <div class="wprh-article-summary">
                            <?php echo wp_kses_post( $summary ); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="wprh-resource-thumbnail wprh-article-featured-image">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <div class="wprh-article-container">
                    <?php if ( $toc_html ) : ?>
                        <aside class="wprh-article-sidebar">
                            <?php echo $toc_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </aside>
                    <?php endif; ?>

                    <div class="entry-content wprh-resource-content wprh-article-content">
                        <?php
                        // Apply content filters and output.
                        echo wp_kses_post( apply_filters( 'the_content', $content ) );
                        ?>
                    </div>
                </div>

                <footer class="entry-footer wprh-resource-footer wprh-article-footer">
                    <?php
                    // Display taxonomies.
                    $topics    = get_the_term_list( get_the_ID(), 'resource_topic', '', ', ', '' );
                    $audiences = get_the_term_list( get_the_ID(), 'resource_audience', '', ', ', '' );

                    if ( $topics || $audiences ) :
                        ?>
                        <div class="wprh-resource-taxonomies">
                            <?php if ( $topics ) : ?>
                                <div class="wprh-resource-topics">
                                    <strong><?php esc_html_e( 'Topics:', 'wp-resource-hub' ); ?></strong>
                                    <?php echo $topics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $audiences ) : ?>
                                <div class="wprh-resource-audiences">
                                    <strong><?php esc_html_e( 'Audience:', 'wp-resource-hub' ); ?></strong>
                                    <?php echo $audiences; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    /**
                     * Fires in the resource footer.
                     *
                     * @since 1.0.0
                     *
                     * @param WP_Post $post Current post object.
                     */
                    do_action( 'wprh_after_resource_content', $post );
                    ?>
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
            do_action( 'wprh_after_resource_article', $post );
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
do_action( 'wprh_after_main_content' );

/**
 * Fires to render the sidebar.
 *
 * @since 1.0.0
 */
do_action( 'wprh_sidebar' );

get_footer();
