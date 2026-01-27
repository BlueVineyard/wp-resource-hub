/**
 * WP Resource Hub - Admin JavaScript
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

( function( $ ) {
    'use strict';

    /**
     * Resource Type Selector Handler
     */
    var ResourceTypeSelector = {
        /**
         * Initialize the type selector.
         */
        init: function() {
            this.bindEvents();
            this.initializeState();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            $( document ).on( 'change', 'input[name="wprh_resource_type"]', this.onTypeChange.bind( this ) );
            $( document ).on( 'click', '.wprh-type-button', this.onButtonClick.bind( this ) );
        },

        /**
         * Initialize the current state.
         */
        initializeState: function() {
            var $checked = $( 'input[name="wprh_resource_type"]:checked' );
            if ( $checked.length ) {
                this.showFieldsForType( $checked.val() );
            }
        },

        /**
         * Handle button click.
         *
         * @param {Event} e Click event.
         */
        onButtonClick: function( e ) {
            var $button = $( e.currentTarget );
            var $radio = $button.find( 'input[type="radio"]' );

            // Update visual state.
            $( '.wprh-type-button' ).removeClass( 'selected' );
            $button.addClass( 'selected' );

            // Check the radio.
            $radio.prop( 'checked', true ).trigger( 'change' );
        },

        /**
         * Handle type change.
         *
         * @param {Event} e Change event.
         */
        onTypeChange: function( e ) {
            var type = $( e.target ).val();
            this.showFieldsForType( type );
        },

        /**
         * Show fields for the selected type.
         *
         * @param {string} type Resource type slug.
         */
        showFieldsForType: function( type ) {
            // Hide all type fields.
            $( '.wprh-type-fields' ).hide();

            // Hide "no type" notice.
            $( '.wprh-no-type-notice' ).hide();

            // Show fields for selected type.
            if ( type ) {
                $( '.wprh-type-fields[data-type="' + type + '"]' ).show();
            } else {
                $( '.wprh-no-type-notice' ).show();
            }
        }
    };

    /**
     * Media Upload Handler
     */
    var MediaUpload = {
        /**
         * Initialize media upload.
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            $( document ).on( 'click', '.wprh-upload-button', this.onUploadClick.bind( this ) );
            $( document ).on( 'click', '.wprh-remove-button', this.onRemoveClick.bind( this ) );
        },

        /**
         * Handle upload button click.
         *
         * @param {Event} e Click event.
         */
        onUploadClick: function( e ) {
            e.preventDefault();

            var $button = $( e.currentTarget );
            var targetId = $button.data( 'target' );
            var fileType = $button.data( 'type' ) || '';
            var $input = $( '#' + targetId );
            var $removeButton = $( '.wprh-remove-button[data-target="' + targetId + '"]' );
            var $preview = $( '#' + targetId + '_preview' );
            var $fileInfo = $( '#' + targetId + '_info' );

            // Determine title based on file type.
            var title = wprhAdmin.selectFile;
            if ( fileType === 'application/pdf' ) {
                title = wprhAdmin.selectPdf;
            } else if ( fileType.indexOf( 'image' ) === 0 ) {
                title = wprhAdmin.selectImage;
            }

            // Create media frame.
            var frame = wp.media( {
                title: title,
                button: {
                    text: wprhAdmin.useThis
                },
                multiple: false,
                library: {
                    type: fileType || ''
                }
            } );

            // Handle selection.
            frame.on( 'select', function() {
                var attachment = frame.state().get( 'selection' ).first().toJSON();

                // Set the value.
                $input.val( attachment.id );

                // Show remove button.
                $removeButton.show();

                // Update preview if it's an image.
                if ( $preview.length && attachment.type === 'image' ) {
                    var imgUrl = attachment.sizes && attachment.sizes.thumbnail ?
                        attachment.sizes.thumbnail.url : attachment.url;
                    $preview.html( '<img src="' + imgUrl + '" alt="">' );
                }

                // Update file info.
                if ( $fileInfo.length ) {
                    var icon = 'dashicons-media-default';
                    if ( attachment.type === 'application' && attachment.subtype === 'pdf' ) {
                        icon = 'dashicons-pdf';
                    } else if ( attachment.type === 'image' ) {
                        icon = 'dashicons-format-image';
                    }
                    $fileInfo.html( '<span class="dashicons ' + icon + '"></span> ' + attachment.filename );
                }

                // Trigger custom event.
                $input.trigger( 'wprh:media-selected', [ attachment ] );
            } );

            frame.open();
        },

        /**
         * Handle remove button click.
         *
         * @param {Event} e Click event.
         */
        onRemoveClick: function( e ) {
            e.preventDefault();

            var $button = $( e.currentTarget );
            var targetId = $button.data( 'target' );
            var $input = $( '#' + targetId );
            var $preview = $( '#' + targetId + '_preview' );
            var $fileInfo = $( '#' + targetId + '_info' );

            // Clear the value.
            $input.val( '' );

            // Hide remove button.
            $button.hide();

            // Clear preview.
            if ( $preview.length ) {
                $preview.empty();
            }

            // Clear file info.
            if ( $fileInfo.length ) {
                $fileInfo.empty();
            }

            // Trigger custom event.
            $input.trigger( 'wprh:media-removed' );
        }
    };

    /**
     * Validation Handler
     */
    var Validation = {
        /**
         * Initialize validation.
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            $( '#post' ).on( 'submit', this.onFormSubmit.bind( this ) );
        },

        /**
         * Handle form submission.
         *
         * @param {Event} e Submit event.
         */
        onFormSubmit: function( e ) {
            var $form = $( e.target );
            var type = $( 'input[name="wprh_resource_type"]:checked' ).val();
            var errors = [];

            // Clear previous errors.
            $( '.wprh-validation-error' ).remove();

            // Validate based on type.
            switch ( type ) {
                case 'video':
                    if ( ! $( '#wprh_video_url' ).val() && ! $( '#wprh_video_id' ).val() ) {
                        errors.push( {
                            field: '#wprh_video_url',
                            message: 'Please enter a video URL or video ID.'
                        } );
                    }
                    break;

                case 'pdf':
                    if ( ! $( '#wprh_pdf_file' ).val() ) {
                        errors.push( {
                            field: '#wprh_pdf_file',
                            message: 'Please select a PDF file.'
                        } );
                    }
                    break;

                case 'download':
                    if ( ! $( '#wprh_download_file' ).val() ) {
                        errors.push( {
                            field: '#wprh_download_file',
                            message: 'Please select a download file.'
                        } );
                    }
                    break;

                case 'external-link':
                    if ( ! $( '#wprh_external_url' ).val() ) {
                        errors.push( {
                            field: '#wprh_external_url',
                            message: 'Please enter an external URL.'
                        } );
                    }
                    break;
            }

            // Show errors if any.
            if ( errors.length > 0 ) {
                errors.forEach( function( error ) {
                    var $field = $( error.field );
                    $field.after(
                        '<p class="wprh-validation-error" style="color: #d63638; margin: 5px 0;">' +
                        error.message +
                        '</p>'
                    );
                } );

                // Scroll to first error.
                $( 'html, body' ).animate( {
                    scrollTop: $( errors[ 0 ].field ).offset().top - 100
                }, 300 );

                // Prevent submission.
                e.preventDefault();
                return false;
            }

            return true;
        }
    };

    /**
     * Initialize on document ready.
     */
    $( document ).ready( function() {
        ResourceTypeSelector.init();
        MediaUpload.init();
        Validation.init();
    } );

} )( jQuery );
