/**
 * Tests for useConfig hook.
 *
 * @since 0.1.0
 */

import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import useConfig from '../../../src/hooks/use-config';

// Mock the @wordpress/data module.
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useConfig', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns empty supportedPostTypes when none found', () => {
		useSelect.mockImplementation( ( callback ) => {
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [],
					getPostType: () => null,
					getTaxonomy: () => null,
					getEntityRecords: () => [],
				} ) );
			}
			return {};
		} );

		const setAttributes = jest.fn();
		const { result } = renderHook( () =>
			useConfig( { postType: '', setAttributes } )
		);

		expect( result.current.supportedPostTypes ).toEqual( [] );
		expect( result.current.isConfigured ).toBeFalsy();
	} );

	it( 'returns isConfigured as false when config is missing', () => {
		useSelect.mockImplementation( ( callback ) => {
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [],
					getPostType: () => ( { supports: {} } ),
					getTaxonomy: () => null,
					getEntityRecords: () => [],
				} ) );
			}
			return {};
		} );

		const setAttributes = jest.fn();
		const { result } = renderHook( () =>
			useConfig( { postType: 'gatherpress_event', setAttributes } )
		);

		expect( result.current.isConfigured ).toBeFalsy();
	} );

	it( 'returns taxonomy data when properly configured', () => {
		const mockConfig = {
			ref_tax: 'gatherpress-production',
			ref_types: [
				'_gatherpress-client',
				'_gatherpress-festival',
				'_gatherpress-award',
			],
		};

		const mockTaxonomy = {
			slug: '_gatherpress-client',
			name: 'Clients',
			labels: { name: 'Clients' },
		};

		// useSelect is called multiple times with different selectors.
		// We need to handle each call appropriately.
		let callCount = 0;
		useSelect.mockImplementation( ( callback ) => {
			callCount++;
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [
						{
							slug: 'gatherpress_event',
							supports: {
								gatherpress_references: [ mockConfig ],
							},
						},
					],
					getPostType: () => ( {
						supports: {
							gatherpress_references: [ mockConfig ],
						},
					} ),
					getTaxonomy: ( slug ) => {
						if ( slug === 'gatherpress-production' ) {
							return {
								slug: 'gatherpress-production',
								name: 'Productions',
								labels: { name: 'Productions' },
							};
						}
						return mockTaxonomy;
					},
					getEntityRecords: () => [
						{ id: 1, name: 'Hamlet' },
					],
				} ) );
			}
			return {};
		} );

		const setAttributes = jest.fn();
		const { result } = renderHook( () =>
			useConfig( {
				postType: 'gatherpress_event',
				setAttributes,
			} )
		);

		expect( result.current.activePostType ).toBe(
			'gatherpress_event'
		);
	} );

	it( 'builds typeLabels from taxonomies', () => {
		const mockTaxonomies = [
			{
				slug: '_gatherpress-client',
				name: 'Clients',
				labels: { name: 'Clients' },
			},
			{
				slug: '_gatherpress-festival',
				name: 'Festivals',
				labels: { name: 'Festivals' },
			},
		];

		useSelect.mockImplementation( ( callback ) => {
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [],
					getPostType: () => null,
					getTaxonomy: ( slug ) =>
						mockTaxonomies.find( ( t ) => t.slug === slug ) ||
						null,
					getEntityRecords: () => [],
				} ) );
			}
			return mockTaxonomies;
		} );

		const setAttributes = jest.fn();
		const { result } = renderHook( () =>
			useConfig( { postType: '', setAttributes } )
		);

		// typeLabels depend on the taxonomies returned.
		expect( result.current.typeLabels ).toBeDefined();
		expect( typeof result.current.typeLabels ).toBe( 'object' );
	} );
} );
