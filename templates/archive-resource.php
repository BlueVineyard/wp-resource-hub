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
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPResourceHub\Taxonomies\ResourceTypeTax;

get_header();

/**
 * Fires before the resource archive content wrapper.
 *
 * @since 1.0.0
 */
do_action( 'wprh_before_main_content' );
?>

<div id="primary" class="content-area wprh-content-area wprh-archive">
    <main id="main" class="site-main wprh-site-main">

        <?php if ( have_posts() ) : ?>

            <header class="page-header wprh-archive-header">
                <?php
                if ( is_post_type_archive() ) {
                    ?>
                    <h1 class="page-title wprh-archive-title">
                        <?php post_type_archive_title(); ?>
                    </h1>
                    <?php
                    $archive_description = get_the_post_type_description();
                    if ( $archive_description ) {
                        ?>
                        <div class="archive-description wprh-archive-description">
                            <?php echo wp_kses_post( $archive_description ); ?>
                        </div>
                        <?php
                    }
                } elseif ( is_tax() ) {
                    $term = get_queried_object();
                    ?>
                    <h1 class="page-title wprh-archive-title">
                        <?php single_term_title(); ?>
                    </h1>
                    <?php
                    if ( ! empty( $term->description ) ) {
                        ?>
                        <div class="archive-description wprh-archive-description">
                            <?php echo wp_kses_post( $term->description ); ?>
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
                do_action( 'wprh_archive_header' );
                ?>
            </header>

            <?php
            /**
             * Fires before the resource grid.
             *
             * @since 1.0.0
             */
            do_action( 'wprh_before_resource_loop' );
            ?>

            <div class="wprh-resource-grid">
                <?php while ( have_posts() ) : ?>
                    <?php the_post(); ?>

                    <?php
                    $resource_type = ResourceTypeTax::get_resource_type( get_the_ID() );
                    $type_slug     = $resource_type ? $resource_type->slug : '';
                    $type_icon     = $resource_type ? ResourceTypeTax::get_type_icon( $resource_type ) : 'dashicons-media-default';
                    ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'wprh-resource-card' ); ?>>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="wprh-card-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium_large' ); ?>
                                </a>
                                <?php if ( $resource_type ) : ?>
                                    <span class="wprh-card-type-badge wprh-type-<?php echo esc_attr( $type_slug ); ?>">
                                        <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                                        <?php echo esc_html( $resource_type->name ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <div class="wprh-card-thumbnail wprh-card-no-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <span class="dashicons <?php echo esc_attr( $type_icon ); ?>"></span>
                                </a>
                                <?php if ( $resource_type ) : ?>
                                    <span class="wprh-card-type-badge wprh-type-<?php echo esc_attr( $type_slug ); ?>">
                                        <?php echo esc_html( $resource_type->name ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="wprh-card-content">
                            <header class="wprh-card-header">
                                <?php the_title( '<h2 class="wprh-card-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
                            </header>

                            <div class="wprh-card-excerpt">
                                <?php the_excerpt(); ?>
                            </div>

                            <footer class="wprh-card-footer">
                                <?php
                                $topics = get_the_term_list( get_the_ID(), 'resource_topic', '<span class="wprh-card-topics">', ', ', '</span>' );
                                if ( $topics ) {
                                    echo $topics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                }
                                ?>

                                <a href="<?php the_permalink(); ?>" class="wprh-card-link">
                                    <?php esc_html_e( 'View Resource', 'wp-resource-hub' ); ?>
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </a>
                            </footer>
                        </div>

                    </article>

                <?php endwhile; ?>
            </div>

            <?php
            /**
             * Fires after the resource grid.
             *
             * @since 1.0.0
             */
            do_action( 'wprh_after_resource_loop' );
            ?>

            <?php
            // Pagination.
            the_posts_pagination(
                array(
                    'mid_size'  => 2,
                    'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . esc_html__( 'Previous', 'wp-resource-hub' ),
                    'next_text' => esc_html__( 'Next', 'wp-resource-hub' ) . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                )
            );
            ?>

        <?php else : ?>

            <div class="wprh-no-resources">
                <p><?php esc_html_e( 'No resources found.', 'wp-resource-hub' ); ?></p>
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
do_action( 'wprh_after_main_content' );

/**
 * Fires to render the sidebar.
 *
 * @since 1.0.0
 */
do_action( 'wprh_sidebar' );

get_footer();
