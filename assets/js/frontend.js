/**
 * WP Resource Hub - Frontend JavaScript
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

( function( $ ) {
    'use strict';

    /**
     * Table of Contents Handler
     */
    var TableOfContents = {
        /**
         * Initialize TOC functionality.
         */
        init: function() {
            this.$toc = $( '.wprh-toc' );

            if ( ! this.$toc.length ) {
                return;
            }

            this.bindEvents();
            this.initSmoothScroll();
            this.initActiveTracking();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            // Toggle TOC on mobile.
            this.$toc.find( '.wprh-toc-title' ).on( 'click', function() {
                $( this ).parent().toggleClass( 'wprh-toc-collapsed' );
            } );
        },

        /**
         * Initialize smooth scrolling for TOC links.
         */
        initSmoothScroll: function() {
            this.$toc.find( 'a' ).on( 'click', function( e ) {
                var targetId = $( this ).attr( 'href' );

                if ( targetId && targetId.charAt( 0 ) === '#' ) {
                    var $target = $( targetId );

                    if ( $target.length ) {
                        e.preventDefault();

                        $( 'html, body' ).animate( {
                            scrollTop: $target.offset().top - 80
                        }, 300 );

                        // Update URL hash.
                        if ( history.pushState ) {
                            history.pushState( null, null, targetId );
                        }
                    }
                }
            } );
        },

        /**
         * Initialize active heading tracking.
         */
        initActiveTracking: function() {
            var self = this;
            var $headings = $( '.wprh-content-body h2, .wprh-content-body h3, .wprh-content-body h4' ).filter( '[id]' );

            if ( ! $headings.length ) {
                return;
            }

            $( window ).on( 'scroll', function() {
                var scrollTop = $( window ).scrollTop();
                var activeId = '';

                $headings.each( function() {
                    var $heading = $( this );
                    var headingTop = $heading.offset().top - 100;

                    if ( scrollTop >= headingTop ) {
                        activeId = $heading.attr( 'id' );
                    }
                } );

                // Update active class.
                self.$toc.find( 'a' ).removeClass( 'wprh-toc-active' );

                if ( activeId ) {
                    self.$toc.find( 'a[href="#' + activeId + '"]' ).addClass( 'wprh-toc-active' );
                }
            } );
        }
    };

    /**
     * Video Handler
     */
    var VideoHandler = {
        /**
         * Initialize video functionality.
         */
        init: function() {
            this.initLazyLoading();
        },

        /**
         * Initialize lazy loading for video iframes.
         */
        initLazyLoading: function() {
            var $videos = $( '.wprh-video-wrapper iframe[data-src]' );

            if ( ! $videos.length ) {
                return;
            }

            // Use Intersection Observer if available.
            if ( 'IntersectionObserver' in window ) {
                var observer = new IntersectionObserver( function( entries ) {
                    entries.forEach( function( entry ) {
                        if ( entry.isIntersecting ) {
                            var $iframe = $( entry.target );
                            $iframe.attr( 'src', $iframe.data( 'src' ) );
                            observer.unobserve( entry.target );
                        }
                    } );
                }, {
                    rootMargin: '200px'
                } );

                $videos.each( function() {
                    observer.observe( this );
                } );
            } else {
                // Fallback: load all videos immediately.
                $videos.each( function() {
                    var $iframe = $( this );
                    $iframe.attr( 'src', $iframe.data( 'src' ) );
                } );
            }
        }
    };

    /**
     * External Link Handler
     */
    var ExternalLinkHandler = {
        /**
         * Initialize external link functionality.
         */
        init: function() {
            this.addExternalIndicators();
        },

        /**
         * Add indicators to external links in content.
         */
        addExternalIndicators: function() {
            $( '.wprh-content-body a[target="_blank"]' ).each( function() {
                var $link = $( this );

                // Skip if already has indicator.
                if ( $link.find( '.wprh-external-indicator' ).length ) {
                    return;
                }

                // Add visual indicator.
                $link.append( ' <span class="wprh-external-indicator" aria-hidden="true">↗</span>' );

                // Add screen reader text.
                $link.append( '<span class="screen-reader-text">(opens in new tab)</span>' );
            } );
        }
    };

    /**
     * Copy Link Handler
     */
    var CopyLinkHandler = {
        /**
         * Initialize copy link functionality.
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            $( document ).on( 'click', '.wprh-copy-link', this.onCopyClick.bind( this ) );
        },

        /**
         * Handle copy button click.
         *
         * @param {Event} e Click event.
         */
        onCopyClick: function( e ) {
            e.preventDefault();

            var $button = $( e.currentTarget );
            var url = $button.data( 'url' ) || window.location.href;

            this.copyToClipboard( url ).then( function() {
                var originalText = $button.text();
                $button.text( 'Copied!' );

                setTimeout( function() {
                    $button.text( originalText );
                }, 2000 );
            } );
        },

        /**
         * Copy text to clipboard.
         *
         * @param {string} text Text to copy.
         * @return {Promise}
         */
        copyToClipboard: function( text ) {
            if ( navigator.clipboard && navigator.clipboard.writeText ) {
                return navigator.clipboard.writeText( text );
            }

            // Fallback for older browsers.
            return new Promise( function( resolve, reject ) {
                var textarea = document.createElement( 'textarea' );
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild( textarea );
                textarea.select();

                try {
                    document.execCommand( 'copy' );
                    resolve();
                } catch ( err ) {
                    reject( err );
                }

                document.body.removeChild( textarea );
            } );
        }
    };

    /**
     * Reading Progress Handler
     */
    var ReadingProgress = {
        /**
         * Initialize reading progress functionality.
         */
        init: function() {
            this.$content = $( '.wprh-resource-internal-content .wprh-content-body' );

            if ( ! this.$content.length ) {
                return;
            }

            this.createProgressBar();
            this.bindEvents();
        },

        /**
         * Create progress bar element.
         */
        createProgressBar: function() {
            this.$progressBar = $( '<div class="wprh-reading-progress"><div class="wprh-reading-progress-bar"></div></div>' );
            $( 'body' ).prepend( this.$progressBar );
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            var self = this;

            $( window ).on( 'scroll', function() {
                self.updateProgress();
            } );
        },

        /**
         * Update progress bar.
         */
        updateProgress: function() {
            var contentTop = this.$content.offset().top;
            var contentHeight = this.$content.outerHeight();
            var windowHeight = $( window ).height();
            var scrollTop = $( window ).scrollTop();

            var scrollableHeight = contentHeight - windowHeight;
            var scrolledInContent = scrollTop - contentTop + windowHeight;

            var progress = Math.max( 0, Math.min( 100, ( scrolledInContent / scrollableHeight ) * 100 ) );

            this.$progressBar.find( '.wprh-reading-progress-bar' ).css( 'width', progress + '%' );
        }
    };

    /**
     * Initialize all modules on document ready.
     */
    $( document ).ready( function() {
        TableOfContents.init();
        VideoHandler.init();
        ExternalLinkHandler.init();
        CopyLinkHandler.init();

        // Only initialize reading progress on internal content resources.
        if ( $( 'body' ).hasClass( 'wprh-resource-type-internal-content' ) ) {
            ReadingProgress.init();
        }
    } );

} )( jQuery );
