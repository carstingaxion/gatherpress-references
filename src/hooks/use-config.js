/**
 * Configuration Hook
 *
 * Handles post type detection, configuration loading, and taxonomy data retrieval.
 *
 * @since 0.1.0
 */

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo } from '@wordpress/element';

/**
 * Custom hook for block configuration
 *
 * Fetches supported post types, active configuration, taxonomy terms,
 * and type labels from the WordPress data store.
 *
 * @param {Object}   params               Hook parameters.
 * @param {string}   params.postType      Current post type attribute.
 * @param {Function} params.setAttributes Function to update block attributes.
 * @return {Object} Configuration data including supportedPostTypes, activePostType, config, refTaxonomy, refTerms, taxonomies, typeLabels, and isConfigured.
 */
export default function useConfig( { postType, setAttributes } ) {
	/**
	 * Fetch all post types with gatherpress-references support
	 */
	const supportedPostTypes = useSelect( ( select ) => {
		const postTypes = select( 'core' ).getPostTypes( { per_page: -1 } );

		if ( ! postTypes ) {
			return [];
		}

		return postTypes.filter( ( type ) => {
			return type.supports && type.supports.gatherpress-references;
		} );
	}, [] );

	/**
	 * Auto-assign post type on block insertion if only one supported type exists
	 */
	useEffect( () => {
		if ( ! postType && supportedPostTypes.length === 1 ) {
			setAttributes( { postType: supportedPostTypes[ 0 ].slug } );
		}
	}, [ postType, supportedPostTypes, setAttributes ] );

	/**
	 * Determine active post type for configuration lookup
	 */
	const activePostType = useMemo( () => {
		if ( postType ) {
			return postType;
		}

		return supportedPostTypes.length > 0
			? supportedPostTypes[ 0 ].slug
			: null;
	}, [ postType, supportedPostTypes ] );

	/**
	 * Fetch block configuration from the active post type
	 */
	const config = useSelect(
		( select ) => {
			if ( ! activePostType ) {
				return null;
			}

			const postTypeObject =
				select( 'core' ).getPostType( activePostType );

			if ( ! postTypeObject || ! postTypeObject.supports ) {
				return null;
			}

			const referencesSupport =
				postTypeObject.supports.gatherpress-references;

			if ( ! referencesSupport ) {
				return null;
			}

			if (
				Array.isArray( referencesSupport ) &&
				referencesSupport.length > 0
			) {
				return referencesSupport[ 0 ];
			}

			if (
				typeof referencesSupport === 'object' &&
				referencesSupport !== null
			) {
				return referencesSupport;
			}

			return null;
		},
		[ activePostType ]
	);

	/**
	 * Fetch reference taxonomy object and terms
	 */
	const { refTaxonomy, refTerms } = useSelect(
		( select ) => {
			if ( ! config || ! config.ref_tax ) {
				return { refTaxonomy: null, refTerms: [] };
			}

			const taxonomy = select( 'core' ).getTaxonomy( config.ref_tax );
			const terms = select( 'core' ).getEntityRecords(
				'taxonomy',
				config.ref_tax,
				{
					per_page: 99,
				}
			);
			return {
				refTaxonomy: taxonomy || null,
				refTerms: terms || [],
			};
		},
		[ config ]
	);

	/**
	 * Fetch taxonomy objects for reference types
	 */
	const taxonomies = useSelect(
		( select ) => {
			if (
				! config ||
				! config.ref_types ||
				! Array.isArray( config.ref_types )
			) {
				return [];
			}

			return config.ref_types
				.map( ( slug ) => select( 'core' ).getTaxonomy( slug ) )
				.filter( ( tax ) => tax !== null && tax !== undefined );
		},
		[ config ]
	);

	/**
	 * Build type labels mapping
	 */
	const typeLabels = useMemo( () => {
		const labels = {};
		taxonomies.forEach( ( tax ) => {
			labels[ tax.slug ] = tax.labels?.name || tax.name;
		} );
		return labels;
	}, [ taxonomies ] );

	/**
	 * Check if block is properly configured
	 */
	const isConfigured =
		config &&
		typeof config === 'object' &&
		config.ref_tax &&
		config.ref_types &&
		Array.isArray( config.ref_types ) &&
		config.ref_types.length > 0;

	return {
		supportedPostTypes,
		activePostType,
		config,
		refTaxonomy,
		refTerms,
		taxonomies,
		typeLabels,
		isConfigured,
	};
}
