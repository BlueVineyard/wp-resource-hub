/**
 * WP Resource Hub - Collection Admin JavaScript
 *
 * @package WPResourceHub
 * @since   1.1.0
 */

( function( $ ) {
    'use strict';

    var CollectionAdmin = {

        /**
         * Search timeout ID.
         */
        searchTimeout: null,

        /**
         * Currently excluded IDs.
         */
        excludedIds: [],

        /**
         * Initialize the collection admin.
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.initSortable();
            this.updateExcludedIds();
        },

        /**
         * Cache DOM elements.
         */
        cacheElements: function() {
            this.$searchInput = $( '#wprh-resource-search' );
            this.$searchResults = $( '#wprh-search-results' );
            this.$resourcesList = $( '#wprh-collection-resources' );
            this.$noResourcesMessage = this.$resourcesList.find( '.wprh-no-resources-message' );
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            this.$searchInput.on( 'input', this.onSearchInput.bind( this ) );
            this.$searchInput.on( 'focus', this.onSearchFocus.bind( this ) );
            this.$searchResults.on( 'click', '.wprh-search-result-item', this.onResultClick.bind( this ) );
            this.$resourcesList.on( 'click', '.wprh-remove-resource', this.onRemoveClick.bind( this ) );

            // Close results on click outside.
            $( document ).on( 'click', function( e ) {
                if ( ! $( e.target ).closest( '.wprh-collection-search' ).length ) {
                    this.$searchResults.hide();
                }
            }.bind( this ) );
        },

        /**
         * Initialize sortable.
         */
        initSortable: function() {
            this.$resourcesList.sortable( {
                items: '.wprh-collection-resource-item',
                handle: '.wprh-resource-drag-handle',
                placeholder: 'wprh-collection-resource-item ui-sortable-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.9,
                update: function() {
                    this.updateExcludedIds();
                }.bind( this )
            } );
        },

        /**
         * Handle search input.
         */
        onSearchInput: function() {
            var searchTerm = this.$searchInput.val().trim();

            clearTimeout( this.searchTimeout );

            if ( searchTerm.length < 2 ) {
                this.$searchResults.hide();
                return;
            }

            this.searchTimeout = setTimeout( function() {
                this.performSearch( searchTerm );
            }.bind( this ), 300 );
        },

        /**
         * Handle search focus.
         */
        onSearchFocus: function() {
            var searchTerm = this.$searchInput.val().trim();
            if ( searchTerm.length >= 2 ) {
                this.$searchResults.show();
            }
        },

        /**
         * Perform AJAX search.
         */
        performSearch: function( searchTerm ) {
            this.$searchResults.html( '<div class="wprh-search-loading">' + wprhCollection.i18n.searching + '</div>' ).show();

            $.ajax( {
                url: wprhCollection.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wprh_search_resources',
                    nonce: wprhCollection.nonce,
                    search: searchTerm,
                    exclude: this.excludedIds
                },
                success: function( response ) {
                    if ( response.success && response.data.resources.length ) {
                        this.renderSearchResults( response.data.resources );
                    } else {
                        this.$searchResults.html( '<div class="wprh-search-no-results">' + wprhCollection.i18n.noResults + '</div>' );
                    }
                }.bind( this ),
                error: function() {
                    this.$searchResults.hide();
                }.bind( this )
            } );
        },

        /**
         * Render search results.
         */
        renderSearchResults: function( resources ) {
            var html = '';

            resources.forEach( function( resource ) {
                html += '<div class="wprh-search-result-item" data-id="' + resource.id + '" data-title="' + this.escapeHtml( resource.title ) + '" data-type="' + resource.type_slug + '" data-type-name="' + this.escapeHtml( resource.type ) + '" data-icon="' + resource.type_icon + '">';
                html += '<span class="wprh-resource-type-icon dashicons ' + resource.type_icon + '"></span>';
                html += '<span class="wprh-resource-title">' + this.escapeHtml( resource.title ) + '</span>';
                if ( resource.type ) {
                    html += '<span class="wprh-resource-type-label">' + this.escapeHtml( resource.type ) + '</span>';
                }
                html += '</div>';
            }.bind( this ) );

            this.$searchResults.html( html );
        },

        /**
         * Handle result click.
         */
        onResultClick: function( e ) {
            var $item = $( e.currentTarget );
            var resourceId = $item.data( 'id' );
            var resourceTitle = $item.data( 'title' );
            var resourceType = $item.data( 'type' );
            var resourceTypeName = $item.data( 'type-name' );
            var resourceIcon = $item.data( 'icon' );

            this.addResource( resourceId, resourceTitle, resourceType, resourceTypeName, resourceIcon );

            // Clear search.
            this.$searchInput.val( '' );
            this.$searchResults.hide();
        },

        /**
         * Add a resource to the list.
         */
        addResource: function( id, title, type, typeName, icon ) {
            // Hide no resources message.
            this.$noResourcesMessage.hide();

            var html = '<div class="wprh-collection-resource-item" data-resource-id="' + id + '" data-type="' + type + '">';
            html += '<span class="wprh-resource-drag-handle dashicons dashicons-menu"></span>';
            html += '<span class="wprh-resource-type-icon dashicons ' + icon + '"></span>';
            html += '<span class="wprh-resource-title">' + this.escapeHtml( title ) + '</span>';
            if ( typeName ) {
                html += '<span class="wprh-resource-type-label">' + this.escapeHtml( typeName ) + '</span>';
            }
            html += '<button type="button" class="wprh-remove-resource button-link" data-resource-id="' + id + '">';
            html += '<span class="dashicons dashicons-no-alt"></span>';
            html += '<span class="screen-reader-text">Remove</span>';
            html += '</button>';
            html += '<input type="hidden" name="wprh_collection_resources[]" value="' + id + '">';
            html += '</div>';

            this.$resourcesList.append( html );
            this.updateExcludedIds();

            // Refresh sortable.
            this.$resourcesList.sortable( 'refresh' );
        },

        /**
         * Handle remove click.
         */
        onRemoveClick: function( e ) {
            e.preventDefault();

            var $button = $( e.currentTarget );
            var $item = $button.closest( '.wprh-collection-resource-item' );

            $item.fadeOut( 200, function() {
                $item.remove();
                this.updateExcludedIds();

                // Show no resources message if empty.
                if ( ! this.$resourcesList.find( '.wprh-collection-resource-item' ).length ) {
                    this.$noResourcesMessage.show();
                }
            }.bind( this ) );
        },

        /**
         * Update excluded IDs array.
         */
        updateExcludedIds: function() {
            this.excludedIds = [];
            this.$resourcesList.find( '.wprh-collection-resource-item' ).each( function() {
                this.excludedIds.push( parseInt( $( arguments[1] ).data( 'resource-id' ), 10 ) );
            }.bind( this ) );
        },

        /**
         * Escape HTML special characters.
         */
        escapeHtml: function( text ) {
            var div = document.createElement( 'div' );
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $( document ).ready( function() {
        if ( $( '#wprh-collection-resources' ).length ) {
            CollectionAdmin.init();
        }
    } );

} )( jQuery );
