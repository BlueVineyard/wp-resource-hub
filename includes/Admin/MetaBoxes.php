<?php

/**
 * Meta Boxes class.
 *
 * Handles registration and rendering of meta boxes for the resource post type.
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

namespace WPResourceHub\Admin;

use WPResourceHub\PostTypes\ResourcePostType;
use WPResourceHub\Taxonomies\ResourceTypeTax;
use WPResourceHub\Helpers;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Meta Boxes class.
 *
 * @since 1.0.0
 */
class MetaBoxes
{

    /**
     * Singleton instance.
     *
     * @var MetaBoxes|null
     */
    private static $instance = null;

    /**
     * Meta key prefix.
     *
     * @var string
     */
    const META_PREFIX = '_wprh_';

    /**
     * Get the singleton instance.
     *
     * @since 1.0.0
     *
     * @return MetaBoxes
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
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_resource', array($this, 'save_meta'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Register meta boxes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_meta_boxes()
    {
        // Resource Type Selector meta box.
        add_meta_box(
            'wprh_resource_type_selector',
            __('Resource Type', 'wp-resource-hub'),
            array($this, 'render_type_selector'),
            ResourcePostType::get_post_type(),
            'normal',
            'high'
        );

        // Resource Details meta box.
        add_meta_box(
            'wprh_resource_details',
            __('Resource Details', 'wp-resource-hub'),
            array($this, 'render_details_meta_box'),
            ResourcePostType::get_post_type(),
            'normal',
            'high'
        );
    }

    /**
     * Render the resource type selector.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_type_selector($post)
    {
        wp_nonce_field('wprh_save_meta', 'wprh_meta_nonce');

        $current_type = get_post_meta($post->ID, self::META_PREFIX . 'resource_type', true);
        $types        = ResourceTypeTax::get_instance()->get_default_types();

        /**
         * Filter the resource types available in the selector.
         *
         * @since 1.0.0
         *
         * @param array    $types Available resource types.
         * @param \WP_Post $post  Current post object.
         */
        $types = apply_filters('wprh_type_selector_types', $types, $post);
?>
        <div class="wprh-type-selector">
            <p class="description"><?php esc_html_e('Select the type of resource you are creating.', 'wp-resource-hub'); ?>
            </p>
            <div class="wprh-type-buttons">
                <?php foreach ($types as $key => $type) : ?>
                    <label class="wprh-type-button <?php echo $current_type === $type['slug'] ? 'selected' : ''; ?>">
                        <input type="radio" name="wprh_resource_type" value="<?php echo esc_attr($type['slug']); ?>"
                            <?php checked($current_type, $type['slug']); ?> data-type="<?php echo esc_attr($type['slug']); ?>">
                        <span class="dashicons <?php echo esc_attr($type['icon']); ?>"></span>
                        <span class="wprh-type-label"><?php echo esc_html($type['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php
    }

    /**
     * Render the resource details meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_details_meta_box($post)
    {
        $current_type = get_post_meta($post->ID, self::META_PREFIX . 'resource_type', true);
    ?>
        <div class="wprh-details-wrapper">
            <p class="wprh-no-type-notice" style="<?php echo $current_type ? 'display:none;' : ''; ?>">
                <?php esc_html_e('Please select a resource type above to see the available options.', 'wp-resource-hub'); ?>
            </p>

            <?php
            // Video fields.
            $this->render_video_fields($post, $current_type);

            // PDF fields.
            $this->render_pdf_fields($post, $current_type);

            // Download fields.
            $this->render_download_fields($post, $current_type);

            // External Link fields.
            $this->render_external_link_fields($post, $current_type);

            // Internal Content fields.
            $this->render_internal_content_fields($post, $current_type);

            /**
             * Fires after default resource type fields are rendered.
             *
             * Use this hook to add custom fields for custom resource types.
             *
             * @since 1.0.0
             *
             * @param \WP_Post $post         Current post object.
             * @param string   $current_type Current resource type slug.
             */
            do_action('wprh_render_type_fields', $post, $current_type);
            ?>
        </div>
    <?php
    }

    /**
     * Render video type fields.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post         Current post object.
     * @param string   $current_type Current resource type slug.
     * @return void
     */
    private function render_video_fields($post, $current_type)
    {
        $video_provider  = get_post_meta($post->ID, self::META_PREFIX . 'video_provider', true);
        $video_url       = get_post_meta($post->ID, self::META_PREFIX . 'video_url', true);
        $video_id        = get_post_meta($post->ID, self::META_PREFIX . 'video_id', true);
        $video_duration  = get_post_meta($post->ID, self::META_PREFIX . 'video_duration', true);
        $video_thumbnail = get_post_meta($post->ID, self::META_PREFIX . 'video_thumbnail', true);
        $display         = 'video' === $current_type ? '' : 'display:none;';
    ?>
        <div class="wprh-type-fields wprh-video-fields" data-type="video" style="<?php echo esc_attr($display); ?>">
            <h4><?php esc_html_e('Video Settings', 'wp-resource-hub'); ?></h4>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wprh_video_provider"><?php esc_html_e('Video Provider', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <select name="wprh_video_provider" id="wprh_video_provider" class="regular-text">
                            <option value=""><?php esc_html_e('— Select Provider —', 'wp-resource-hub'); ?></option>
                            <option value="youtube" <?php selected($video_provider, 'youtube'); ?>>
                                <?php esc_html_e('YouTube', 'wp-resource-hub'); ?></option>
                            <option value="vimeo" <?php selected($video_provider, 'vimeo'); ?>>
                                <?php esc_html_e('Vimeo', 'wp-resource-hub'); ?></option>
                            <option value="local" <?php selected($video_provider, 'local'); ?>>
                                <?php esc_html_e('Local/Self-hosted', 'wp-resource-hub'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_video_url"><?php esc_html_e('Video URL', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="url" name="wprh_video_url" id="wprh_video_url" value="<?php echo esc_url($video_url); ?>"
                            class="large-text" placeholder="https://www.youtube.com/watch?v=xxxxx">
                        <p class="description"><?php esc_html_e('Enter the full URL of the video.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_video_id"><?php esc_html_e('Video ID', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wprh_video_id" id="wprh_video_id" value="<?php echo esc_attr($video_id); ?>"
                            class="regular-text" placeholder="dQw4w9WgXcQ">
                        <p class="description">
                            <?php esc_html_e('Optional. The video ID will be auto-extracted from the URL if left empty.', 'wp-resource-hub'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_video_duration"><?php esc_html_e('Duration', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wprh_video_duration" id="wprh_video_duration"
                            value="<?php echo esc_attr($video_duration); ?>" class="small-text" placeholder="10:30">
                        <p class="description">
                            <?php esc_html_e('Optional. Video duration in MM:SS or HH:MM:SS format.', 'wp-resource-hub'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_video_thumbnail"><?php esc_html_e('Custom Thumbnail', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <div class="wprh-media-upload">
                            <input type="hidden" name="wprh_video_thumbnail" id="wprh_video_thumbnail"
                                value="<?php echo esc_attr($video_thumbnail); ?>">
                            <button type="button" class="button wprh-upload-button" data-target="wprh_video_thumbnail">
                                <?php esc_html_e('Select Image', 'wp-resource-hub'); ?>
                            </button>
                            <button type="button" class="button wprh-remove-button" data-target="wprh_video_thumbnail"
                                style="<?php echo $video_thumbnail ? '' : 'display:none;'; ?>">
                                <?php esc_html_e('Remove', 'wp-resource-hub'); ?>
                            </button>
                            <div class="wprh-media-preview" id="wprh_video_thumbnail_preview">
                                <?php if ($video_thumbnail) : ?>
                                    <?php echo wp_get_attachment_image($video_thumbnail, 'thumbnail'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Optional. Override the default video thumbnail.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Render PDF type fields.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post         Current post object.
     * @param string   $current_type Current resource type slug.
     * @return void
     */
    private function render_pdf_fields($post, $current_type)
    {
        $pdf_file       = get_post_meta($post->ID, self::META_PREFIX . 'pdf_file', true);
        $pdf_file_size  = get_post_meta($post->ID, self::META_PREFIX . 'pdf_file_size', true);
        $pdf_page_count = get_post_meta($post->ID, self::META_PREFIX . 'pdf_page_count', true);
        $pdf_viewer_mode = get_post_meta($post->ID, self::META_PREFIX . 'pdf_viewer_mode', true);
        $display        = 'pdf' === $current_type ? '' : 'display:none;';
    ?>
        <div class="wprh-type-fields wprh-pdf-fields" data-type="pdf" style="<?php echo esc_attr($display); ?>">
            <h4><?php esc_html_e('PDF Settings', 'wp-resource-hub'); ?></h4>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wprh_pdf_file"><?php esc_html_e('PDF File', 'wp-resource-hub'); ?> <span
                                class="required">*</span></label>
                    </th>
                    <td>
                        <div class="wprh-media-upload">
                            <input type="hidden" name="wprh_pdf_file" id="wprh_pdf_file"
                                value="<?php echo esc_attr($pdf_file); ?>" data-required="true">
                            <button type="button" class="button wprh-upload-button" data-target="wprh_pdf_file"
                                data-type="application/pdf">
                                <?php esc_html_e('Select PDF', 'wp-resource-hub'); ?>
                            </button>
                            <button type="button" class="button wprh-remove-button" data-target="wprh_pdf_file"
                                style="<?php echo $pdf_file ? '' : 'display:none;'; ?>">
                                <?php esc_html_e('Remove', 'wp-resource-hub'); ?>
                            </button>
                            <div class="wprh-file-info" id="wprh_pdf_file_info">
                                <?php
                                if ($pdf_file) {
                                    $filename = basename(get_attached_file($pdf_file));
                                    echo '<span class="dashicons dashicons-pdf"></span> ' . esc_html($filename);
                                }
                                ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_pdf_file_size"><?php esc_html_e('File Size', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wprh_pdf_file_size" id="wprh_pdf_file_size"
                            value="<?php echo esc_attr($pdf_file_size); ?>" class="small-text" placeholder="2.5 MB">
                        <p class="description">
                            <?php esc_html_e('Optional. Will be auto-calculated if left empty.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_pdf_page_count"><?php esc_html_e('Page Count', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="wprh_pdf_page_count" id="wprh_pdf_page_count"
                            value="<?php echo esc_attr($pdf_page_count); ?>" class="small-text" min="1">
                        <p class="description">
                            <?php esc_html_e('Optional. Number of pages in the PDF.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_pdf_viewer_mode"><?php esc_html_e('Viewer Mode', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <select name="wprh_pdf_viewer_mode" id="wprh_pdf_viewer_mode" class="regular-text">
                            <option value="embedded" <?php selected($pdf_viewer_mode, 'embedded'); ?>>
                                <?php esc_html_e('Embedded Viewer', 'wp-resource-hub'); ?></option>
                            <option value="download" <?php selected($pdf_viewer_mode, 'download'); ?>>
                                <?php esc_html_e('Direct Download', 'wp-resource-hub'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Render download type fields.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post         Current post object.
     * @param string   $current_type Current resource type slug.
     * @return void
     */
    private function render_download_fields($post, $current_type)
    {
        $download_file      = get_post_meta($post->ID, self::META_PREFIX . 'download_file', true);
        $download_file_size = get_post_meta($post->ID, self::META_PREFIX . 'download_file_size', true);
        $download_version   = get_post_meta($post->ID, self::META_PREFIX . 'download_version', true);
        $display            = 'download' === $current_type ? '' : 'display:none;';
    ?>
        <div class="wprh-type-fields wprh-download-fields" data-type="download" style="<?php echo esc_attr($display); ?>">
            <h4><?php esc_html_e('Download Settings', 'wp-resource-hub'); ?></h4>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wprh_download_file"><?php esc_html_e('Download File', 'wp-resource-hub'); ?> <span
                                class="required">*</span></label>
                    </th>
                    <td>
                        <div class="wprh-media-upload">
                            <input type="hidden" name="wprh_download_file" id="wprh_download_file"
                                value="<?php echo esc_attr($download_file); ?>" data-required="true">
                            <button type="button" class="button wprh-upload-button" data-target="wprh_download_file">
                                <?php esc_html_e('Select File', 'wp-resource-hub'); ?>
                            </button>
                            <button type="button" class="button wprh-remove-button" data-target="wprh_download_file"
                                style="<?php echo $download_file ? '' : 'display:none;'; ?>">
                                <?php esc_html_e('Remove', 'wp-resource-hub'); ?>
                            </button>
                            <div class="wprh-file-info" id="wprh_download_file_info">
                                <?php
                                if ($download_file) {
                                    $filename = basename(get_attached_file($download_file));
                                    echo '<span class="dashicons dashicons-download"></span> ' . esc_html($filename);
                                }
                                ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_download_file_size"><?php esc_html_e('File Size', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wprh_download_file_size" id="wprh_download_file_size"
                            value="<?php echo esc_attr($download_file_size); ?>" class="small-text" placeholder="15 MB">
                        <p class="description">
                            <?php esc_html_e('Optional. Will be auto-calculated if left empty.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_download_version"><?php esc_html_e('Version', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wprh_download_version" id="wprh_download_version"
                            value="<?php echo esc_attr($download_version); ?>" class="small-text" placeholder="1.0.0">
                        <p class="description">
                            <?php esc_html_e('Optional. Version number for this download.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Render external link type fields.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post         Current post object.
     * @param string   $current_type Current resource type slug.
     * @return void
     */
    private function render_external_link_fields($post, $current_type)
    {
        $external_url  = get_post_meta($post->ID, self::META_PREFIX . 'external_url', true);
        $open_new_tab  = get_post_meta($post->ID, self::META_PREFIX . 'open_new_tab', true);
        $display       = 'external-link' === $current_type ? '' : 'display:none;';
    ?>
        <div class="wprh-type-fields wprh-external-link-fields" data-type="external-link"
            style="<?php echo esc_attr($display); ?>">
            <h4><?php esc_html_e('External Link Settings', 'wp-resource-hub'); ?></h4>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wprh_external_url"><?php esc_html_e('External URL', 'wp-resource-hub'); ?> <span
                                class="required">*</span></label>
                    </th>
                    <td>
                        <input type="url" name="wprh_external_url" id="wprh_external_url"
                            value="<?php echo esc_url($external_url); ?>" class="large-text"
                            placeholder="https://example.com/resource" data-required="true">
                        <p class="description">
                            <?php esc_html_e('Enter the full URL of the external resource.', 'wp-resource-hub'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Open in New Tab', 'wp-resource-hub'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wprh_open_new_tab" id="wprh_open_new_tab" value="1"
                                <?php checked($open_new_tab, '1'); ?>>
                            <?php esc_html_e('Open link in a new browser tab', 'wp-resource-hub'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Render internal content type fields.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post         Current post object.
     * @param string   $current_type Current resource type slug.
     * @return void
     */
    private function render_internal_content_fields($post, $current_type)
    {
        $summary                = get_post_meta($post->ID, self::META_PREFIX . 'summary', true);
        $reading_time           = get_post_meta($post->ID, self::META_PREFIX . 'reading_time', true);
        $show_toc               = get_post_meta($post->ID, self::META_PREFIX . 'show_toc', true);
        $show_related           = get_post_meta($post->ID, self::META_PREFIX . 'show_related', true);
        $display                = 'internal-content' === $current_type ? '' : 'display:none;';
    ?>
        <div class="wprh-type-fields wprh-internal-content-fields" data-type="internal-content"
            style="<?php echo esc_attr($display); ?>">
            <h4><?php esc_html_e('Internal Content Settings', 'wp-resource-hub'); ?></h4>
            <p class="description">
                <?php esc_html_e('The main content will be displayed using the standard WordPress editor above.', 'wp-resource-hub'); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wprh_summary"><?php esc_html_e('Summary', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <textarea name="wprh_summary" id="wprh_summary" rows="3" class="large-text"
                            placeholder="<?php esc_attr_e('Brief summary of this resource...', 'wp-resource-hub'); ?>"><?php echo esc_textarea($summary); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Optional. A short excerpt override for listings and previews.', 'wp-resource-hub'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wprh_reading_time"><?php esc_html_e('Reading Time', 'wp-resource-hub'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="wprh_reading_time" id="wprh_reading_time"
                            value="<?php echo esc_attr($reading_time); ?>" class="small-text" min="1" placeholder="5">
                        <span><?php esc_html_e('minutes', 'wp-resource-hub'); ?></span>
                        <p class="description">
                            <?php esc_html_e('Optional. Manual reading time override. Will be auto-calculated if left empty.', 'wp-resource-hub'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Display Options', 'wp-resource-hub'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="wprh_show_toc" id="wprh_show_toc" value="1"
                                    <?php checked($show_toc, '1'); ?>>
                                <?php esc_html_e('Show Table of Contents', 'wp-resource-hub'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="wprh_show_related" id="wprh_show_related" value="1"
                                    <?php checked($show_related, '1'); ?>>
                                <?php esc_html_e('Show Related Resources', 'wp-resource-hub'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
<?php
    }

    /**
     * Save post meta.
     *
     * @since 1.0.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an update.
     * @return void
     */
    public function save_meta($post_id, $post, $update)
    {
        // Verify nonce.
        if (! isset($_POST['wprh_meta_nonce']) || ! wp_verify_nonce($_POST['wprh_meta_nonce'], 'wprh_save_meta')) {
            return;
        }

        // Check autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions.
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save resource type.
        if (isset($_POST['wprh_resource_type'])) {
            $resource_type = sanitize_text_field($_POST['wprh_resource_type']);
            update_post_meta($post_id, self::META_PREFIX . 'resource_type', $resource_type);

            // Also set the taxonomy term.
            wp_set_object_terms($post_id, $resource_type, ResourceTypeTax::get_taxonomy());
        }

        // Save video fields.
        $this->save_video_meta($post_id);

        // Save PDF fields.
        $this->save_pdf_meta($post_id);

        // Save download fields.
        $this->save_download_meta($post_id);

        // Save external link fields.
        $this->save_external_link_meta($post_id);

        // Save internal content fields.
        $this->save_internal_content_meta($post_id);

        /**
         * Fires after resource meta is saved.
         *
         * @since 1.0.0
         *
         * @param int      $post_id Post ID.
         * @param \WP_Post $post    Post object.
         */
        do_action('wprh_save_meta', $post_id, $post);
    }

    /**
     * Save video meta fields.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function save_video_meta($post_id)
    {
        $fields = array(
            'video_provider'  => 'sanitize_text_field',
            'video_url'       => 'esc_url_raw',
            'video_id'        => 'sanitize_text_field',
            'video_duration'  => 'sanitize_text_field',
            'video_thumbnail' => 'absint',
        );

        foreach ($fields as $field => $sanitize_callback) {
            $key = 'wprh_' . $field;
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize_callback, $_POST[$key]);
                update_post_meta($post_id, self::META_PREFIX . $field, $value);
            }
        }

        // Auto-extract video ID and provider if URL provided.
        $video_url = get_post_meta($post_id, self::META_PREFIX . 'video_url', true);
        $video_id  = get_post_meta($post_id, self::META_PREFIX . 'video_id', true);
        $provider  = get_post_meta($post_id, self::META_PREFIX . 'video_provider', true);

        if ($video_url) {
            // Auto-detect provider if not set.
            if (! $provider) {
                if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                    $provider = 'youtube';
                    update_post_meta($post_id, self::META_PREFIX . 'video_provider', $provider);
                } elseif (strpos($video_url, 'vimeo.com') !== false) {
                    $provider = 'vimeo';
                    update_post_meta($post_id, self::META_PREFIX . 'video_provider', $provider);
                }
            }

            // Auto-extract video ID if not provided.
            if (! $video_id) {
                $extracted_id = Helpers::extract_video_id($video_url, $provider);
                if ($extracted_id) {
                    update_post_meta($post_id, self::META_PREFIX . 'video_id', $extracted_id);
                }
            }
        }
    }

    /**
     * Save PDF meta fields.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function save_pdf_meta($post_id)
    {
        $fields = array(
            'pdf_file'        => 'absint',
            'pdf_file_size'   => 'sanitize_text_field',
            'pdf_page_count'  => 'absint',
            'pdf_viewer_mode' => 'sanitize_text_field',
        );

        foreach ($fields as $field => $sanitize_callback) {
            $key = 'wprh_' . $field;
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize_callback, $_POST[$key]);
                update_post_meta($post_id, self::META_PREFIX . $field, $value);
            }
        }

        // Auto-calculate file size if file provided but no size.
        $pdf_file = get_post_meta($post_id, self::META_PREFIX . 'pdf_file', true);
        $pdf_size = get_post_meta($post_id, self::META_PREFIX . 'pdf_file_size', true);

        if ($pdf_file && ! $pdf_size) {
            $file_path = get_attached_file($pdf_file);
            if ($file_path && file_exists($file_path)) {
                $size = filesize($file_path);
                update_post_meta($post_id, self::META_PREFIX . 'pdf_file_size', size_format($size));
            }
        }
    }

    /**
     * Save download meta fields.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function save_download_meta($post_id)
    {
        $fields = array(
            'download_file'      => 'absint',
            'download_file_size' => 'sanitize_text_field',
            'download_version'   => 'sanitize_text_field',
        );

        foreach ($fields as $field => $sanitize_callback) {
            $key = 'wprh_' . $field;
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize_callback, $_POST[$key]);
                update_post_meta($post_id, self::META_PREFIX . $field, $value);
            }
        }

        // Auto-calculate file size if file provided but no size.
        $download_file = get_post_meta($post_id, self::META_PREFIX . 'download_file', true);
        $download_size = get_post_meta($post_id, self::META_PREFIX . 'download_file_size', true);

        if ($download_file && ! $download_size) {
            $file_path = get_attached_file($download_file);
            if ($file_path && file_exists($file_path)) {
                $size = filesize($file_path);
                update_post_meta($post_id, self::META_PREFIX . 'download_file_size', size_format($size));
            }
        }
    }

    /**
     * Save external link meta fields.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function save_external_link_meta($post_id)
    {
        // External URL.
        if (isset($_POST['wprh_external_url'])) {
            $url = esc_url_raw($_POST['wprh_external_url']);
            update_post_meta($post_id, self::META_PREFIX . 'external_url', $url);
        }

        // Open in new tab checkbox.
        $open_new_tab = isset($_POST['wprh_open_new_tab']) ? '1' : '0';
        update_post_meta($post_id, self::META_PREFIX . 'open_new_tab', $open_new_tab);
    }

    /**
     * Save internal content meta fields.
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private function save_internal_content_meta($post_id)
    {
        // Summary.
        if (isset($_POST['wprh_summary'])) {
            $summary = sanitize_textarea_field($_POST['wprh_summary']);
            update_post_meta($post_id, self::META_PREFIX . 'summary', $summary);
        }

        // Reading time.
        if (isset($_POST['wprh_reading_time'])) {
            $reading_time = absint($_POST['wprh_reading_time']);
            update_post_meta($post_id, self::META_PREFIX . 'reading_time', $reading_time);
        }

        // Show TOC checkbox.
        $show_toc = isset($_POST['wprh_show_toc']) ? '1' : '0';
        update_post_meta($post_id, self::META_PREFIX . 'show_toc', $show_toc);

        // Show related checkbox.
        $show_related = isset($_POST['wprh_show_related']) ? '1' : '0';
        update_post_meta($post_id, self::META_PREFIX . 'show_related', $show_related);

        // Auto-calculate reading time if content exists but no reading time set.
        $reading_time = get_post_meta($post_id, self::META_PREFIX . 'reading_time', true);
        if (! $reading_time) {
            $post    = get_post($post_id);
            $content = $post->post_content;
            if ($content) {
                $calculated_time = Helpers::calculate_reading_time($content);
                update_post_meta($post_id, self::META_PREFIX . 'reading_time', $calculated_time);
            }
        }
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_scripts($hook)
    {
        global $post_type;

        if (! in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }

        if (ResourcePostType::get_post_type() !== $post_type) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'wprh-admin',
            WPRH_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WPRH_VERSION
        );

        wp_enqueue_script(
            'wprh-admin',
            WPRH_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            WPRH_VERSION,
            true
        );

        wp_localize_script(
            'wprh-admin',
            'wprhAdmin',
            array(
                'selectImage' => __('Select Image', 'wp-resource-hub'),
                'selectFile'  => __('Select File', 'wp-resource-hub'),
                'selectPdf'   => __('Select PDF', 'wp-resource-hub'),
                'useThis'     => __('Use this', 'wp-resource-hub'),
            )
        );
    }

    /**
     * Get meta value with prefix.
     *
     * @since 1.0.0
     *
     * @param int    $post_id Post ID.
     * @param string $key     Meta key without prefix.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get_meta($post_id, $key, $default = '')
    {
        $value = get_post_meta($post_id, self::META_PREFIX . $key, true);
        return $value !== '' ? $value : $default;
    }

    /**
     * Get the meta prefix.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_meta_prefix()
    {
        return self::META_PREFIX;
    }
}
