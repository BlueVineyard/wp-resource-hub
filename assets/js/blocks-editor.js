/**
 * WP Resource Hub - Gutenberg Blocks
 *
 * @package WPResourceHub
 * @since   1.2.0
 */

( function( blocks, element, blockEditor, components, i18n ) {
    'use strict';

    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var Placeholder = components.Placeholder;
    var Spinner = components.Spinner;
    var ServerSideRender = wp.serverSideRender;

    /**
     * Resources Grid Block
     */
    blocks.registerBlockType( 'wp-resource-hub/resources-grid', {
        title: __( 'Resources Grid', 'wp-resource-hub' ),
        description: __( 'Display a grid or list of resources with filters.', 'wp-resource-hub' ),
        icon: 'grid-view',
        category: 'wp-resource-hub',
        keywords: [ __( 'resources', 'wp-resource-hub' ), __( 'grid', 'wp-resource-hub' ), __( 'list', 'wp-resource-hub' ) ],
        supports: {
            align: [ 'wide', 'full' ],
            html: false,
        },

        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();

            // Build type options.
            var typeOptions = [ { label: __( 'All Types', 'wp-resource-hub' ), value: '' } ];
            if ( wprhBlocks.types ) {
                wprhBlocks.types.forEach( function( type ) {
                    typeOptions.push( { label: type.name, value: type.slug } );
                } );
            }

            // Build topic options.
            var topicOptions = [ { label: __( 'All Topics', 'wp-resource-hub' ), value: '' } ];
            if ( wprhBlocks.topics ) {
                wprhBlocks.topics.forEach( function( topic ) {
                    topicOptions.push( { label: topic.name, value: topic.slug } );
                } );
            }

            // Build audience options.
            var audienceOptions = [ { label: __( 'All Audiences', 'wp-resource-hub' ), value: '' } ];
            if ( wprhBlocks.audiences ) {
                wprhBlocks.audiences.forEach( function( audience ) {
                    audienceOptions.push( { label: audience.name, value: audience.slug } );
                } );
            }

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __( 'Layout Settings', 'wp-resource-hub' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Layout', 'wp-resource-hub' ),
                            value: attributes.layout,
                            options: [
                                { label: __( 'Grid', 'wp-resource-hub' ), value: 'grid' },
                                { label: __( 'List', 'wp-resource-hub' ), value: 'list' },
                            ],
                            onChange: function( value ) { setAttributes( { layout: value } ); }
                        } ),
                        el( RangeControl, {
                            label: __( 'Columns', 'wp-resource-hub' ),
                            value: attributes.columns,
                            onChange: function( value ) { setAttributes( { columns: value } ); },
                            min: 1,
                            max: 6,
                        } ),
                        el( RangeControl, {
                            label: __( 'Items Per Page', 'wp-resource-hub' ),
                            value: attributes.limit,
                            onChange: function( value ) { setAttributes( { limit: value } ); },
                            min: 1,
                            max: 50,
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: __( 'Filter Content', 'wp-resource-hub' ), initialOpen: false },
                        el( SelectControl, {
                            label: __( 'Resource Type', 'wp-resource-hub' ),
                            value: attributes.type,
                            options: typeOptions,
                            onChange: function( value ) { setAttributes( { type: value } ); }
                        } ),
                        el( SelectControl, {
                            label: __( 'Topic', 'wp-resource-hub' ),
                            value: attributes.topic,
                            options: topicOptions,
                            onChange: function( value ) { setAttributes( { topic: value } ); }
                        } ),
                        el( SelectControl, {
                            label: __( 'Audience', 'wp-resource-hub' ),
                            value: attributes.audience,
                            options: audienceOptions,
                            onChange: function( value ) { setAttributes( { audience: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Featured Only', 'wp-resource-hub' ),
                            checked: attributes.featuredOnly,
                            onChange: function( value ) { setAttributes( { featuredOnly: value } ); }
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: __( 'Order', 'wp-resource-hub' ), initialOpen: false },
                        el( SelectControl, {
                            label: __( 'Order By', 'wp-resource-hub' ),
                            value: attributes.orderby,
                            options: [
                                { label: __( 'Date', 'wp-resource-hub' ), value: 'date' },
                                { label: __( 'Title', 'wp-resource-hub' ), value: 'title' },
                                { label: __( 'Modified', 'wp-resource-hub' ), value: 'modified' },
                                { label: __( 'Menu Order', 'wp-resource-hub' ), value: 'menu_order' },
                                { label: __( 'Random', 'wp-resource-hub' ), value: 'rand' },
                            ],
                            onChange: function( value ) { setAttributes( { orderby: value } ); }
                        } ),
                        el( SelectControl, {
                            label: __( 'Order', 'wp-resource-hub' ),
                            value: attributes.order,
                            options: [
                                { label: __( 'Descending', 'wp-resource-hub' ), value: 'DESC' },
                                { label: __( 'Ascending', 'wp-resource-hub' ), value: 'ASC' },
                            ],
                            onChange: function( value ) { setAttributes( { order: value } ); }
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: __( 'Display Options', 'wp-resource-hub' ), initialOpen: false },
                        el( ToggleControl, {
                            label: __( 'Show Filters', 'wp-resource-hub' ),
                            checked: attributes.showFilters,
                            onChange: function( value ) { setAttributes( { showFilters: value } ); }
                        } ),
                        attributes.showFilters && el( ToggleControl, {
                            label: __( 'Type Filter', 'wp-resource-hub' ),
                            checked: attributes.showTypeFilter,
                            onChange: function( value ) { setAttributes( { showTypeFilter: value } ); }
                        } ),
                        attributes.showFilters && el( ToggleControl, {
                            label: __( 'Topic Filter', 'wp-resource-hub' ),
                            checked: attributes.showTopicFilter,
                            onChange: function( value ) { setAttributes( { showTopicFilter: value } ); }
                        } ),
                        attributes.showFilters && el( ToggleControl, {
                            label: __( 'Audience Filter', 'wp-resource-hub' ),
                            checked: attributes.showAudienceFilter,
                            onChange: function( value ) { setAttributes( { showAudienceFilter: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Search', 'wp-resource-hub' ),
                            checked: attributes.showSearch,
                            onChange: function( value ) { setAttributes( { showSearch: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Pagination', 'wp-resource-hub' ),
                            checked: attributes.showPagination,
                            onChange: function( value ) { setAttributes( { showPagination: value } ); }
                        } )
                    )
                ),
                el( ServerSideRender, {
                    block: 'wp-resource-hub/resources-grid',
                    attributes: attributes,
                } )
            );
        },

        save: function() {
            return null; // Server-side rendered.
        }
    } );

    /**
     * Single Resource Block
     */
    blocks.registerBlockType( 'wp-resource-hub/resource', {
        title: __( 'Resource', 'wp-resource-hub' ),
        description: __( 'Display a single resource.', 'wp-resource-hub' ),
        icon: 'media-document',
        category: 'wp-resource-hub',
        keywords: [ __( 'resource', 'wp-resource-hub' ), __( 'single', 'wp-resource-hub' ) ],
        supports: {
            html: false,
        },

        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();

            // Build resource options.
            var resourceOptions = [ { label: wprhBlocks.i18n.selectResource, value: 0 } ];
            if ( wprhBlocks.resources ) {
                wprhBlocks.resources.forEach( function( resource ) {
                    resourceOptions.push( { label: resource.title, value: resource.id } );
                } );
            }

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __( 'Resource Settings', 'wp-resource-hub' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Select Resource', 'wp-resource-hub' ),
                            value: attributes.resourceId,
                            options: resourceOptions,
                            onChange: function( value ) { setAttributes( { resourceId: parseInt( value, 10 ) } ); }
                        } ),
                        el( SelectControl, {
                            label: __( 'Display Mode', 'wp-resource-hub' ),
                            value: attributes.display,
                            options: [
                                { label: __( 'Card', 'wp-resource-hub' ), value: 'card' },
                                { label: __( 'Full', 'wp-resource-hub' ), value: 'full' },
                                { label: __( 'Embed', 'wp-resource-hub' ), value: 'embed' },
                                { label: __( 'Link', 'wp-resource-hub' ), value: 'link' },
                            ],
                            onChange: function( value ) { setAttributes( { display: value } ); }
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: __( 'Display Options', 'wp-resource-hub' ), initialOpen: false },
                        el( ToggleControl, {
                            label: __( 'Show Title', 'wp-resource-hub' ),
                            checked: attributes.showTitle,
                            onChange: function( value ) { setAttributes( { showTitle: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Meta', 'wp-resource-hub' ),
                            checked: attributes.showMeta,
                            onChange: function( value ) { setAttributes( { showMeta: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Image', 'wp-resource-hub' ),
                            checked: attributes.showImage,
                            onChange: function( value ) { setAttributes( { showImage: value } ); }
                        } )
                    )
                ),
                attributes.resourceId ? el( ServerSideRender, {
                    block: 'wp-resource-hub/resource',
                    attributes: attributes,
                } ) : el(
                    Placeholder,
                    {
                        icon: 'media-document',
                        label: __( 'Resource', 'wp-resource-hub' ),
                        instructions: __( 'Select a resource to display.', 'wp-resource-hub' ),
                    },
                    el( SelectControl, {
                        value: attributes.resourceId,
                        options: resourceOptions,
                        onChange: function( value ) { setAttributes( { resourceId: parseInt( value, 10 ) } ); }
                    } )
                )
            );
        },

        save: function() {
            return null;
        }
    } );

    /**
     * Collection Block
     */
    blocks.registerBlockType( 'wp-resource-hub/collection', {
        title: __( 'Resource Collection', 'wp-resource-hub' ),
        description: __( 'Display a resource collection.', 'wp-resource-hub' ),
        icon: 'playlist-video',
        category: 'wp-resource-hub',
        keywords: [ __( 'collection', 'wp-resource-hub' ), __( 'playlist', 'wp-resource-hub' ) ],
        supports: {
            align: [ 'wide', 'full' ],
            html: false,
        },

        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();

            // Build collection options.
            var collectionOptions = [ { label: wprhBlocks.i18n.selectCollection, value: 0 } ];
            if ( wprhBlocks.collections ) {
                wprhBlocks.collections.forEach( function( collection ) {
                    collectionOptions.push( { label: collection.title, value: collection.id } );
                } );
            }

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __( 'Collection Settings', 'wp-resource-hub' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Select Collection', 'wp-resource-hub' ),
                            value: attributes.collectionId,
                            options: collectionOptions,
                            onChange: function( value ) { setAttributes( { collectionId: parseInt( value, 10 ) } ); }
                        } ),
                        el( SelectControl, {
                            label: __( 'Layout', 'wp-resource-hub' ),
                            value: attributes.layout,
                            options: [
                                { label: __( 'Use Collection Setting', 'wp-resource-hub' ), value: '' },
                                { label: __( 'List', 'wp-resource-hub' ), value: 'list' },
                                { label: __( 'Grid', 'wp-resource-hub' ), value: 'grid' },
                                { label: __( 'Playlist', 'wp-resource-hub' ), value: 'playlist' },
                            ],
                            onChange: function( value ) { setAttributes( { layout: value } ); }
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: __( 'Display Options', 'wp-resource-hub' ), initialOpen: false },
                        el( ToggleControl, {
                            label: __( 'Show Title', 'wp-resource-hub' ),
                            checked: attributes.showTitle,
                            onChange: function( value ) { setAttributes( { showTitle: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Description', 'wp-resource-hub' ),
                            checked: attributes.showDescription,
                            onChange: function( value ) { setAttributes( { showDescription: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Count', 'wp-resource-hub' ),
                            checked: attributes.showCount,
                            onChange: function( value ) { setAttributes( { showCount: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Progress', 'wp-resource-hub' ),
                            checked: attributes.showProgress,
                            onChange: function( value ) { setAttributes( { showProgress: value } ); }
                        } )
                    )
                ),
                attributes.collectionId ? el( ServerSideRender, {
                    block: 'wp-resource-hub/collection',
                    attributes: attributes,
                } ) : el(
                    Placeholder,
                    {
                        icon: 'playlist-video',
                        label: __( 'Resource Collection', 'wp-resource-hub' ),
                        instructions: __( 'Select a collection to display.', 'wp-resource-hub' ),
                    },
                    el( SelectControl, {
                        value: attributes.collectionId,
                        options: collectionOptions,
                        onChange: function( value ) { setAttributes( { collectionId: parseInt( value, 10 ) } ); }
                    } )
                )
            );
        },

        save: function() {
            return null;
        }
    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);
