/**
 * Not Configured Component
 *
 * Displays an error state when the block is not properly configured
 * with a post type that supports gatherpress-references.
 *
 * @since 0.1.0
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Notice } from '@wordpress/components';

/**
 * Not Configured component
 *
 * @param {Object} props                    Component properties.
 * @param {Object} props.blockProps         Block wrapper props from useBlockProps.
 * @param {Array}  props.supportedPostTypes Array of supported post type objects.
 * @return {Element} Error state element.
 */
export default function NotConfigured( { blockProps, supportedPostTypes } ) {
	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Reference Settings',
						'gatherpress-references'
					) }
				>
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'References block requires a post type with gatherpress-references support.',
							'gatherpress-references'
						) }
					</Notice>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'This block requires a post type with gatherpress-references support configured.',
							'gatherpress-references'
						) }
					</p>
					{ supportedPostTypes.length > 0 && (
						<p>
							<strong>
								{ __(
									'Supported post types:',
									'gatherpress-references'
								) }
							</strong>{ ' ' }
							{ supportedPostTypes
								.map(
									( type ) => type.labels?.name || type.name
								)
								.join( ', ' ) }
						</p>
					) }
				</Notice>
			</div>
		</>
	);
}
