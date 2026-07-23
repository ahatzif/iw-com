( function( wp ) {
    const { addFilter } = wp.hooks;
    const { createElement, Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody, TextControl } = wp.components;
    const { __ } = wp.i18n;

    // Add new attribute to the group block


    addFilter( 'blocks.registerBlockType', 'iw-theme-gutenberg-core-blocks/add-group-heading-attribute', function ( settings, name ) {
        if ( name !== 'core/group' ) {
            return settings;
        }

        settings.attributes = Object.assign( settings.attributes, {
            groupHeading: {
                type: 'string',
                default: '',
            },
        } );

        return settings;
    } );

    // Create a higher-order component to add controls


    addFilter( 'editor.BlockEdit', 'iw-theme-gutenberg-core-blocks/with-group-heading-control', wp.compose.createHigherOrderComponent( ( BlockEdit ) => {
        return ( props ) => {
            if ( props.name !== 'core/group' ) {
                return createElement( BlockEdit, props );
            }

            const { groupHeading } = props.attributes;
            const { setAttributes } = props;

            return createElement(
                Fragment,
                null,
                createElement( BlockEdit, props ),
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: __( 'Group Heading', 'iw-theme-gutenberg-core-blocks' ), initialOpen: true },
                        createElement( TextControl, {
                            label: __( 'Heading Text', 'iw-theme-gutenberg-core-blocks' ),
                            value: groupHeading,
                            onChange: ( value ) => setAttributes( { groupHeading: value } ),
                        } )
                    )
                )
            );
        };
    }, 'withGroupHeadingControl' ) );

    // Modify the save output of the group block


    addFilter( 'blocks.getSaveElement', 'iw-theme-gutenberg-core-blocks/modify-group-block-save', function ( element, blockType, attributes ) {
        if ( blockType.name !== 'core/group' ) {
            return element;
        }

        const { groupHeading } = attributes;

        if ( groupHeading ) {
            return createElement(
                Fragment,
                null,
                createElement( 'h2', null, groupHeading ),
                element
            );
        }

        return element;
    } );

} )( window.wp );
