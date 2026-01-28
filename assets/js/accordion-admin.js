/**
 * WP Resource Hub - Accordion Builder Admin JavaScript
 *
 * @package WPResourceHub
 * @since   1.3.0
 */

( function( $ ) {
    'use strict';

    var AccordionBuilder = {

        /**
         * Search timeout ID.
         */
        searchTimeout: null,

        /**
         * Target container for resource insertion.
         */
        insertTarget: null,

        /**
         * Initialize the accordion builder.
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.initSortable();
        },

        /**
         * Cache DOM elements.
         */
        cacheElements: function() {
            this.$wrapper       = $( '.wprh-accordion-builder-wrapper' );
            this.$itemsList      = $( '#wprh-accordion-builder-items' );
            this.$structureInput = $( '#wprh-accordion-structure-data' );
            this.$searchPanel    = $( '#wprh-accordion-resource-search' );
            this.$searchInput    = $( '#wprh-accordion-search-input' );
            this.$searchResults  = $( '#wprh-accordion-search-results' );
            this.$noItemsMessage = this.$itemsList.find( '.wprh-no-items-message' );
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            // Top-level add buttons.
            this.$wrapper.on( 'click', '.wprh-add-heading', this.onAddHeading.bind( this ) );
            this.$wrapper.on( 'click', '.wprh-add-resource-btn', this.onAddResource.bind( this ) );
            this.$wrapper.on( 'click', '.wprh-add-nested', this.onAddNested.bind( this ) );

            // Child add buttons.
            this.$wrapper.on( 'click', '.wprh-add-child-heading', this.onAddChildHeading.bind( this ) );
            this.$wrapper.on( 'click', '.wprh-add-child-resource', this.onAddChildResource.bind( this ) );
            this.$wrapper.on( 'click', '.wprh-add-child-accordion', this.onAddChildAccordion.bind( this ) );

            // Remove button.
            this.$wrapper.on( 'click', '.wprh-builder-remove', this.onRemoveItem.bind( this ) );

            // Toggle nested accordion.
            this.$wrapper.on( 'click', '.wprh-builder-toggle', this.onToggleAccordion.bind( this ) );

            // Input changes trigger serialization.
            this.$wrapper.on( 'input', '.wprh-builder-heading-input', this.serialize.bind( this ) );

            // Search.
            this.$searchInput.on( 'input', this.onSearchInput.bind( this ) );
            this.$wrapper.on( 'click', '.wprh-accordion-search-cancel', this.onSearchCancel.bind( this ) );
            this.$searchResults.on( 'click', '.wprh-search-result-item', this.onSearchResultClick.bind( this ) );

            // Close search results on outside click.
            $( document ).on( 'click', function( e ) {
                if ( ! $( e.target ).closest( '.wprh-accordion-search-inner' ).length ) {
                    this.$searchResults.hide();
                }
            }.bind( this ) );
        },

        /**
         * Initialize sortable on all item lists.
         */
        initSortable: function() {
            var self = this;

            this.$itemsList.sortable( {
                items: '> .wprh-builder-item',
                handle: '.wprh-builder-drag-handle',
                placeholder: 'wprh-builder-item ui-sortable-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.9,
                update: function() {
                    self.serialize();
                }
            } );

            // Init sortable on all nested children lists.
            this.$wrapper.find( '.wprh-builder-children-list' ).each( function() {
                self.initChildSortable( $( this ) );
            } );
        },

        /**
         * Initialize sortable on a children list.
         */
        initChildSortable: function( $list ) {
            var self = this;
            $list.sortable( {
                items: '> .wprh-builder-item',
                handle: '.wprh-builder-drag-handle',
                placeholder: 'wprh-builder-item ui-sortable-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.9,
                connectWith: '.wprh-builder-children-list',
                update: function() {
                    self.serialize();
                }
            } );
        },

        /**
         * Generate a unique ID.
         */
        generateId: function() {
            return 'item-' + Date.now() + '-' + Math.random().toString( 36 ).substr( 2, 9 );
        },

        /**
         * Build heading HTML.
         */
        buildHeadingHtml: function( id ) {
            return '<div class="wprh-builder-item wprh-builder-heading" data-type="heading" data-id="' + id + '">' +
                '<span class="wprh-builder-drag-handle dashicons dashicons-menu"></span>' +
                '<span class="wprh-builder-item-icon dashicons dashicons-heading"></span>' +
                '<input type="text" class="wprh-builder-heading-input" value="" placeholder="' + wprhAccordion.i18n.headingPlaceholder + '">' +
                '<button type="button" class="wprh-builder-remove button-link"><span class="dashicons dashicons-no-alt"></span></button>' +
                '</div>';
        },

        /**
         * Build resource HTML.
         */
        buildResourceHtml: function( id, resourceId, title, typeName, typeIcon ) {
            var html = '<div class="wprh-builder-item wprh-builder-resource" data-type="resource" data-id="' + id + '" data-resource-id="' + resourceId + '">';
            html += '<span class="wprh-builder-drag-handle dashicons dashicons-menu"></span>';
            html += '<span class="wprh-builder-item-icon dashicons ' + typeIcon + '"></span>';
            html += '<span class="wprh-builder-resource-title">' + this.escapeHtml( title ) + '</span>';
            if ( typeName ) {
                html += '<span class="wprh-builder-type-label">' + this.escapeHtml( typeName ) + '</span>';
            }
            html += '<button type="button" class="wprh-builder-remove button-link"><span class="dashicons dashicons-no-alt"></span></button>';
            html += '</div>';
            return html;
        },

        /**
         * Build nested accordion HTML.
         */
        buildAccordionHtml: function( id ) {
            return '<div class="wprh-builder-item wprh-builder-accordion" data-type="accordion" data-id="' + id + '">' +
                '<div class="wprh-builder-accordion-header">' +
                '<span class="wprh-builder-drag-handle dashicons dashicons-menu"></span>' +
                '<span class="wprh-builder-item-icon dashicons dashicons-editor-justify"></span>' +
                '<input type="text" class="wprh-builder-heading-input" value="" placeholder="' + wprhAccordion.i18n.accordionPlaceholder + '">' +
                '<button type="button" class="wprh-builder-toggle button-link"><span class="dashicons dashicons-arrow-down-alt2"></span></button>' +
                '<button type="button" class="wprh-builder-remove button-link"><span class="dashicons dashicons-no-alt"></span></button>' +
                '</div>' +
                '<div class="wprh-builder-accordion-children">' +
                '<div class="wprh-builder-children-toolbar">' +
                '<button type="button" class="button button-small wprh-add-child-heading" data-type="heading"><span class="dashicons dashicons-heading"></span> Heading</button>' +
                '<button type="button" class="button button-small wprh-add-child-resource" data-type="resource"><span class="dashicons dashicons-media-default"></span> Resource</button>' +
                '<button type="button" class="button button-small wprh-add-child-accordion" data-type="accordion"><span class="dashicons dashicons-editor-justify"></span> Nested</button>' +
                '</div>' +
                '<div class="wprh-builder-children-list"></div>' +
                '</div>' +
                '</div>';
        },

        // --- Event Handlers ---

        onAddHeading: function( e ) {
            e.preventDefault();
            this.$noItemsMessage.hide();
            var id = this.generateId();
            this.$itemsList.append( this.buildHeadingHtml( id ) );
            this.$itemsList.find( '[data-id="' + id + '"] .wprh-builder-heading-input' ).focus();
            this.serialize();
        },

        onAddResource: function( e ) {
            e.preventDefault();
            this.insertTarget = this.$itemsList;
            this.$searchPanel.show();
            this.$searchInput.val( '' ).focus();
        },

        onAddNested: function( e ) {
            e.preventDefault();
            this.$noItemsMessage.hide();
            var id = this.generateId();
            var $el = $( this.buildAccordionHtml( id ) );
            this.$itemsList.append( $el );
            this.initChildSortable( $el.find( '.wprh-builder-children-list' ) );
            $el.find( '.wprh-builder-heading-input' ).focus();
            this.serialize();
        },

        onAddChildHeading: function( e ) {
            e.preventDefault();
            var $childrenList = $( e.currentTarget ).closest( '.wprh-builder-accordion-children' ).find( '.wprh-builder-children-list' ).first();
            var id = this.generateId();
            $childrenList.append( this.buildHeadingHtml( id ) );
            $childrenList.find( '[data-id="' + id + '"] .wprh-builder-heading-input' ).focus();
            this.serialize();
        },

        onAddChildResource: function( e ) {
            e.preventDefault();
            var $childrenList = $( e.currentTarget ).closest( '.wprh-builder-accordion-children' ).find( '.wprh-builder-children-list' ).first();
            this.insertTarget = $childrenList;
            this.$searchPanel.show();
            this.$searchInput.val( '' ).focus();
        },

        onAddChildAccordion: function( e ) {
            e.preventDefault();
            var $childrenList = $( e.currentTarget ).closest( '.wprh-builder-accordion-children' ).find( '.wprh-builder-children-list' ).first();
            var id = this.generateId();
            var $el = $( this.buildAccordionHtml( id ) );
            $childrenList.append( $el );
            this.initChildSortable( $el.find( '.wprh-builder-children-list' ) );
            $el.find( '.wprh-builder-heading-input' ).focus();
            this.serialize();
        },

        onRemoveItem: function( e ) {
            e.preventDefault();
            var $item = $( e.currentTarget ).closest( '.wprh-builder-item' );
            $item.fadeOut( 200, function() {
                $item.remove();
                this.serialize();
                // Show no items message if empty.
                if ( ! this.$itemsList.find( '.wprh-builder-item' ).length ) {
                    this.$noItemsMessage.show();
                }
            }.bind( this ) );
        },

        onToggleAccordion: function( e ) {
            e.preventDefault();
            var $accordion = $( e.currentTarget ).closest( '.wprh-builder-accordion' );
            $accordion.toggleClass( 'is-collapsed' );
        },

        // --- Search ---

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

        performSearch: function( searchTerm ) {
            this.$searchResults.html( '<div class="wprh-search-loading">' + wprhAccordion.i18n.searching + '</div>' ).show();

            $.ajax( {
                url: wprhAccordion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wprh_search_accordion_resources',
                    nonce: wprhAccordion.nonce,
                    search: searchTerm,
                    exclude: []
                },
                success: function( response ) {
                    if ( response.success && response.data.resources.length ) {
                        this.renderSearchResults( response.data.resources );
                    } else {
                        this.$searchResults.html( '<div class="wprh-search-no-results">' + wprhAccordion.i18n.noResults + '</div>' );
                    }
                }.bind( this ),
                error: function() {
                    this.$searchResults.hide();
                }.bind( this )
            } );
        },

        renderSearchResults: function( resources ) {
            var html = '';
            resources.forEach( function( resource ) {
                html += '<div class="wprh-search-result-item" data-id="' + resource.id + '" data-title="' + this.escapeHtml( resource.title ) + '" data-type-name="' + this.escapeHtml( resource.type ) + '" data-icon="' + resource.type_icon + '">';
                html += '<span class="wprh-resource-type-icon dashicons ' + resource.type_icon + '"></span>';
                html += '<span class="wprh-resource-title">' + this.escapeHtml( resource.title ) + '</span>';
                if ( resource.type ) {
                    html += '<span class="wprh-resource-type-label">' + this.escapeHtml( resource.type ) + '</span>';
                }
                html += '</div>';
            }.bind( this ) );
            this.$searchResults.html( html );
        },

        onSearchResultClick: function( e ) {
            var $item        = $( e.currentTarget );
            var resourceId   = $item.data( 'id' );
            var resourceTitle = $item.data( 'title' );
            var typeName     = $item.data( 'type-name' );
            var typeIcon     = $item.data( 'icon' );
            var id           = this.generateId();

            this.$noItemsMessage.hide();

            if ( this.insertTarget && this.insertTarget.length ) {
                this.insertTarget.append( this.buildResourceHtml( id, resourceId, resourceTitle, typeName, typeIcon ) );
            }

            this.$searchPanel.hide();
            this.$searchResults.hide();
            this.$searchInput.val( '' );
            this.insertTarget = null;
            this.serialize();
        },

        onSearchCancel: function( e ) {
            e.preventDefault();
            this.$searchPanel.hide();
            this.$searchResults.hide();
            this.$searchInput.val( '' );
            this.insertTarget = null;
        },

        // --- Serialization ---

        /**
         * Serialize the builder structure to JSON and update the hidden input.
         */
        serialize: function() {
            var structure = this.serializeItems( this.$itemsList );
            this.$structureInput.val( JSON.stringify( structure ) );
        },

        /**
         * Serialize items within a container.
         */
        serializeItems: function( $container ) {
            var self  = this;
            var items = [];

            $container.children( '.wprh-builder-item' ).each( function() {
                var $el  = $( this );
                var type = $el.data( 'type' );
                var id   = $el.data( 'id' );

                var item = { type: type, id: id };

                switch ( type ) {
                    case 'heading':
                        item.title = $el.find( '.wprh-builder-heading-input' ).first().val() || '';
                        break;

                    case 'resource':
                        item.resource_id = parseInt( $el.data( 'resource-id' ), 10 ) || 0;
                        break;

                    case 'accordion':
                        item.title = $el.find( '> .wprh-builder-accordion-header .wprh-builder-heading-input' ).val() || '';
                        var $childrenList = $el.find( '> .wprh-builder-accordion-children > .wprh-builder-children-list' );
                        item.children = self.serializeItems( $childrenList );
                        break;
                }

                items.push( item );
            } );

            return items;
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
        if ( $( '#wprh-accordion-builder-items' ).length ) {
            AccordionBuilder.init();
        }
    } );

} )( jQuery );
