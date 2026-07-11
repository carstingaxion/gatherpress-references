/**
 * Tests for ReferenceInspector component.
 *
 * @since 0.1.0
 */

import { render, screen, fireEvent } from '@testing-library/react';
import ReferenceInspector from '../../../src/components/reference-inspector';

// Mock WordPress dependencies.
jest.mock( '@wordpress/block-editor', () => ( {
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
			<label>{ label }</label>
			<select
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				data-help={ help }
			>
				{ options.map( ( opt ) => (
					<option key={ opt.value } value={ opt.value }>
						{ opt.label }
					</option>
				) ) }
			</select>
		</div>
	),
	TextControl: ( {
		label,
		value,
		onChange,
		type,
		min,
		max,
		placeholder,
		help,
	} ) => (
		<div data-testid={ `text-${ label }` }>
			<label>{ label }</label>
			<input
				type={ type || 'text' }
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				min={ min }
				max={ max }
				placeholder={ placeholder }
				data-help={ help }
			/>
		</div>
	),
	ToggleControl: ( { label, checked, onChange, help } ) => (
		<div data-testid={ `toggle-${ label }` }>
			<label>{ label }</label>
			<input
				type="checkbox"
				checked={ checked }
				onChange={ ( e ) => onChange( e.target.checked ) }
				data-help={ help }
			/>
		</div>
	),
	RangeControl: ( { label, value, onChange, min, max, help } ) => (
		<div data-testid={ `range-${ label }` }>
			<label>{ label }</label>
			<input
				type="range"
				value={ value }
				onChange={ ( e ) =>
					onChange( parseInt( e.target.value ) )
				}
				min={ min }
				max={ max }
				data-help={ help }
			/>
		</div>
	),
} ) );

describe( 'ReferenceInspector', () => {
	const defaultAttributes = {
		postType: 'gatherpress_event',
		refTermId: 0,
		year: 0,
		referenceType: 'all',
		headingLevel: 2,
		yearSortOrder: 'desc',
	};

	const defaultProps = {
		attributes: defaultAttributes,
		setAttributes: jest.fn(),
		supportedPostTypes: [
			{
				slug: 'gatherpress_event',
				name: 'Events',
				labels: { name: 'Events' },
			},
		],
		refTaxonomy: {
			slug: 'gatherpress-production',
			labels: { singular_name: 'Production' },
		},
		refTerms: [
			{ id: 1, name: 'Hamlet' },
			{ id: 2, name: 'Macbeth' },
		],
		taxonomies: [
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
			{
				slug: '_gatherpress-award',
				name: 'Awards',
				labels: { name: 'Awards' },
			},
		],
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders inspector controls', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect(
			screen.getByTestId( 'inspector-controls' )
		).toBeInTheDocument();
	} );

	it( 'renders Reference Settings panel', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		const panel = screen.getByTestId( 'panel-body' );
		expect( panel ).toHaveAttribute(
			'data-title',
			'Reference Settings'
		);
	} );

	it( 'does not show post type selector with single supported type', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect(
			screen.queryByTestId( 'select-Post Type' )
		).not.toBeInTheDocument();
	} );

	it( 'shows post type selector with multiple supported types', () => {
		const props = {
			...defaultProps,
			supportedPostTypes: [
				{
					slug: 'gatherpress_event',
					name: 'Events',
					labels: { name: 'Events' },
				},
				{
					slug: 'post',
					name: 'Posts',
					labels: { name: 'Posts' },
				},
			],
		};

		render( <ReferenceInspector { ...props } /> );

		expect(
			screen.getByTestId( 'select-Post Type' )
		).toBeInTheDocument();
	} );

	it( 'renders reference term selector with taxonomy label', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect(
			screen.getByTestId( 'select-Production' )
		).toBeInTheDocument();
	} );

	it( 'renders year input', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect( screen.getByTestId( 'text-Year' ) ).toBeInTheDocument();
	} );

	it( 'renders reference type selector', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect(
			screen.getByTestId( 'select-Reference Type' )
		).toBeInTheDocument();
	} );

	it( 'renders heading level control', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect(
			screen.getByTestId( 'range-Year Heading Level' )
		).toBeInTheDocument();
	} );

	it( 'shows year sort toggle when year is 0', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		// yearSortOrder label depends on current value.
		expect(
			screen.getByTestId( 'toggle-Sort Years Newest First' )
		).toBeInTheDocument();
	} );

	it( 'hides year sort toggle when year is set', () => {
		const props = {
			...defaultProps,
			attributes: { ...defaultAttributes, year: 2024 },
		};

		render( <ReferenceInspector { ...props } /> );

		expect(
			screen.queryByTestId( 'toggle-Sort Years Newest First' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'toggle-Sort Years Oldest First' )
		).not.toBeInTheDocument();
	} );

	it( 'calls setAttributes when reference type changes', () => {
		const setAttributes = jest.fn();
		const props = { ...defaultProps, setAttributes };

		render( <ReferenceInspector { ...props } /> );

		const select = screen
			.getByTestId( 'select-Reference Type' )
			.querySelector( 'select' );
		fireEvent.change( select, {
			target: { value: '_gatherpress-award' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			referenceType: '_gatherpress-award',
		} );
	} );

	it( 'calls setAttributes with parsed year value', () => {
		const setAttributes = jest.fn();
		const props = { ...defaultProps, setAttributes };

		render( <ReferenceInspector { ...props } /> );

		const input = screen
			.getByTestId( 'text-Year' )
			.querySelector( 'input' );
		fireEvent.change( input, { target: { value: '2024' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { year: 2024 } );
	} );

	it( 'calls setAttributes with 0 for invalid year', () => {
		const setAttributes = jest.fn();
		const props = { ...defaultProps, setAttributes };

		render( <ReferenceInspector { ...props } /> );

		const input = screen
			.getByTestId( 'text-Year' )
			.querySelector( 'input' );
		fireEvent.change( input, { target: { value: '' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { year: 0 } );
	} );

	it( 'shows "Oldest First" label when sort is ascending', () => {
		const props = {
			...defaultProps,
			attributes: { ...defaultAttributes, yearSortOrder: 'asc' },
		};

		render( <ReferenceInspector { ...props } /> );

		expect(
			screen.getByTestId( 'toggle-Sort Years Oldest First' )
		).toBeInTheDocument();
	} );

	it( 'uses fallback label when refTaxonomy has no singular_name', () => {
		const props = {
			...defaultProps,
			refTaxonomy: null,
		};

		render( <ReferenceInspector { ...props } /> );

		expect(
			screen.getByTestId( 'select-Reference Term' )
		).toBeInTheDocument();
	} );

	it( 'includes ref terms as options', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect( screen.getByText( 'Hamlet' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Macbeth' ) ).toBeInTheDocument();
	} );

	it( 'includes "All (or auto-detect)" as first ref term option', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect(
			screen.getByText( 'All (or auto-detect)' )
		).toBeInTheDocument();
	} );

	it( 'includes "All Types" as first reference type option', () => {
		render( <ReferenceInspector { ...defaultProps } /> );

		expect( screen.getByText( 'All Types' ) ).toBeInTheDocument();
	} );
} );
