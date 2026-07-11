/**
 * Tests for the main Edit component.
 *
 * @since 0.1.0
 */

import { render, screen } from '@testing-library/react';
import Edit from '../../src/edit';

// Mock WordPress dependencies.
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: () => ( {
		className: 'wp-block-gatherpress-references',
	} ),
	InspectorControls: ( { children } ) => (
		<div data-testid="inspector-controls">{ children }</div>
	),
} ) );

jest.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children, title } ) => (
		<div data-testid="panel-body" data-title={ title }>
			{ children }
		</div>
	),
	SelectControl: ( { label, value, options, onChange, help } ) => (
		<div data-testid={ `select-${ label }` }>
			<select
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
			>
				{ options.map( ( opt ) => (
					<option key={ opt.value } value={ opt.value }>
						{ opt.label }
					</option>
				) ) }
			</select>
		</div>
	),
	TextControl: ( { label, value, onChange, ...rest } ) => (
		<div data-testid={ `text-${ label }` }>
			<input
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				{ ...rest }
			/>
		</div>
	),
	ToggleControl: ( { label, checked, onChange } ) => (
		<div data-testid={ `toggle-${ label }` }>
			<input
				type="checkbox"
				checked={ checked }
				onChange={ ( e ) => onChange( e.target.checked ) }
			/>
		</div>
	),
	RangeControl: ( { label, value, onChange, min, max } ) => (
		<div data-testid={ `range-${ label }` }>
			<input
				type="range"
				value={ value }
				onChange={ ( e ) =>
					onChange( parseInt( e.target.value ) )
				}
				min={ min }
				max={ max }
			/>
		</div>
	),
	Notice: ( { children, status } ) => (
		<div data-testid="notice" data-status={ status }>
			{ children }
		</div>
	),
	Button: ( { children, onClick, disabled, label, ...rest } ) => (
		<button
			onClick={ onClick }
			disabled={ disabled }
			aria-label={ label }
			{ ...rest }
		>
			{ children }
		</button>
	),
	ButtonGroup: ( { children } ) => (
		<div data-testid="button-group">{ children }</div>
	),
} ) );

jest.mock( '@wordpress/icons', () => ( {
	chevronUp: 'chevron-up',
	chevronDown: 'chevron-down',
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

import { useSelect } from '@wordpress/data';

describe( 'Edit component', () => {
	const defaultAttributes = {
		postType: '',
		refTermId: 0,
		year: 0,
		referenceType: 'all',
		headingLevel: 2,
		yearSortOrder: 'desc',
		typeOrder: [],
		metadata: { name: 'References' },
	};

	beforeEach( () => {
		// Default mock: no supported post types (not configured).
		useSelect.mockImplementation( ( callback ) => {
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [],
					getPostType: () => null,
					getTaxonomy: () => null,
					getEntityRecords: () => [],
				} ) );
			}
			return [];
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders the block wrapper', () => {
		const { container } = render(
			<Edit
				attributes={ defaultAttributes }
				setAttributes={ jest.fn() }
			/>
		);

		expect(
			container.querySelector(
				'.wp-block-gatherpress-references'
			)
		).toBeInTheDocument();
	} );

	it( 'shows not-configured state when no support detected', () => {
		render(
			<Edit
				attributes={ defaultAttributes }
				setAttributes={ jest.fn() }
			/>
		);

		const notices = screen.getAllByTestId( 'notice' );
		expect( notices.length ).toBeGreaterThan( 0 );
	} );

	it( 'renders preview when properly configured', () => {
		const mockConfig = {
			ref_tax: 'gatherpress-production',
			ref_types: [
				'_gatherpress-client',
				'_gatherpress-festival',
				'_gatherpress-award',
			],
		};

		useSelect.mockImplementation( ( callback ) => {
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [
						{
							slug: 'gatherpress_event',
							name: 'Events',
							labels: { name: 'Events' },
							supports: {
								gatherpress_references: [ mockConfig ],
							},
						},
					],
					getPostType: () => ( {
						slug: 'gatherpress_event',
						supports: {
							gatherpress_references: [ mockConfig ],
						},
					} ),
					getTaxonomy: ( slug ) => {
						const taxMap = {
							'gatherpress-production': {
								slug: 'gatherpress-production',
								name: 'Productions',
								labels: {
									name: 'Productions',
									singular_name: 'Production',
								},
							},
							'_gatherpress-client': {
								slug: '_gatherpress-client',
								name: 'Clients',
								labels: { name: 'Clients' },
							},
							'_gatherpress-festival': {
								slug: '_gatherpress-festival',
								name: 'Festivals',
								labels: { name: 'Festivals' },
							},
							'_gatherpress-award': {
								slug: '_gatherpress-award',
								name: 'Awards',
								labels: { name: 'Awards' },
							},
						};
						return taxMap[ slug ] || null;
					},
					getEntityRecords: () => [
						{ id: 1, name: 'Hamlet' },
					],
				} ) );
			}
			return [];
		} );

		const { container } = render(
			<Edit
				attributes={ {
					...defaultAttributes,
					postType: 'gatherpress_event',
				} }
				setAttributes={ jest.fn() }
			/>
		);

		// Should render year headings from placeholder data.
		const yearHeadings =
			container.querySelectorAll( '.references-year' );
		expect( yearHeadings.length ).toBeGreaterThan( 0 );
	} );

	it( 'renders inspector controls when configured', () => {
		const mockConfig = {
			ref_tax: 'gatherpress-production',
			ref_types: [ '_gatherpress-client' ],
		};

		useSelect.mockImplementation( ( callback ) => {
			if ( typeof callback === 'function' ) {
				return callback( () => ( {
					getPostTypes: () => [
						{
							slug: 'gatherpress_event',
							name: 'Events',
							labels: { name: 'Events' },
							supports: {
								gatherpress_references: [ mockConfig ],
							},
						},
					],
					getPostType: () => ( {
						slug: 'gatherpress_event',
						supports: {
							gatherpress_references: [ mockConfig ],
						},
					} ),
					getTaxonomy: () => ( {
						slug: '_gatherpress-client',
						name: 'Clients',
						labels: { name: 'Clients' },
					} ),
					getEntityRecords: () => [],
				} ) );
			}
			return [];
		} );

		render(
			<Edit
				attributes={ {
					...defaultAttributes,
					postType: 'gatherpress_event',
				} }
				setAttributes={ jest.fn() }
			/>
		);

		expect(
			screen.getByTestId( 'inspector-controls' )
		).toBeInTheDocument();
	} );
} );
