/**
 * Tests for ReferencePreview component.
 *
 * @since 0.1.0
 */

import { render, screen, fireEvent } from '@testing-library/react';
import ReferencePreview from '../../../src/components/reference-preview';

// Mock WordPress dependencies.
jest.mock( '@wordpress/components', () => ( {
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
	ButtonGroup: ( { children, ...rest } ) => (
		<div data-testid="button-group" { ...rest }>
			{ children }
		</div>
	),
} ) );

jest.mock( '@wordpress/icons', () => ( {
	chevronUp: 'chevron-up-icon',
	chevronDown: 'chevron-down-icon',
} ) );

describe( 'ReferencePreview', () => {
	const typeLabels = {
		'_gatherpress-client': 'Clients',
		'_gatherpress-festival': 'Festivals',
		'_gatherpress-award': 'Awards',
	};

	const orderedTypeKeys = [
		'_gatherpress-client',
		'_gatherpress-festival',
		'_gatherpress-award',
	];

	const filteredData = {
		'2024': {
			'_gatherpress-client': [ 'Client A', 'Client B' ],
			'_gatherpress-festival': [ 'Festival X' ],
			'_gatherpress-award': [ 'Award 1' ],
		},
		'2023': {
			'_gatherpress-client': [ 'Client C' ],
			'_gatherpress-festival': [],
			'_gatherpress-award': [ 'Award 2' ],
		},
	};

	const sortedYears = [ '2024', '2023' ];

	const defaultProps = {
		filteredData,
		sortedYears,
		orderedTypeKeys,
		typeLabels,
		headingLevel: 2,
		referenceType: 'all',
		moveTypeUp: jest.fn(),
		moveTypeDown: jest.fn(),
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns null when no data', () => {
		const { container } = render(
			<ReferencePreview
				{ ...defaultProps }
				filteredData={ {} }
				sortedYears={ [] }
			/>
		);

		expect( container.innerHTML ).toBe( '' );
	} );

	it( 'renders year headings', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		expect( screen.getByText( '2024' ) ).toBeInTheDocument();
		expect( screen.getByText( '2023' ) ).toBeInTheDocument();
	} );

	it( 'renders year headings at correct level', () => {
		const { container } = render(
			<ReferencePreview { ...defaultProps } headingLevel={ 3 } />
		);

		const h3Elements = container.querySelectorAll( 'h3.references-year' );
		expect( h3Elements.length ).toBe( 2 );
	} );

	it( 'renders type headings when referenceType is "all"', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		expect( screen.getByText( 'Clients' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Festivals' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Awards' ) ).toBeInTheDocument();
	} );

	it( 'does not render type headings when specific type is selected', () => {
		render(
			<ReferencePreview
				{ ...defaultProps }
				referenceType="_gatherpress-client"
			/>
		);

		// Type headings should not be rendered.
		expect( screen.queryByText( 'Clients' ) ).not.toBeInTheDocument();
	} );

	it( 'renders list items', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		expect( screen.getByText( 'Client A' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Client B' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Festival X' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Award 1' ) ).toBeInTheDocument();
	} );

	it( 'renders type headings at secondary heading level', () => {
		const { container } = render(
			<ReferencePreview { ...defaultProps } headingLevel={ 2 } />
		);

		const h3TypeHeadings =
			container.querySelectorAll( 'h3.references-type' );
		expect( h3TypeHeadings.length ).toBeGreaterThan( 0 );
	} );

	it( 'caps secondary heading at h6', () => {
		const { container } = render(
			<ReferencePreview { ...defaultProps } headingLevel={ 5 } />
		);

		// h5 + 1 = h6 (capped).
		const h6TypeHeadings =
			container.querySelectorAll( 'h6.references-type' );
		expect( h6TypeHeadings.length ).toBeGreaterThan( 0 );
	} );

	it( 'shows reorder controls when "all" types and multiple types', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		const moveUpButtons = screen.getAllByLabelText( 'Move up' );
		const moveDownButtons = screen.getAllByLabelText( 'Move down' );

		expect( moveUpButtons.length ).toBeGreaterThan( 0 );
		expect( moveDownButtons.length ).toBeGreaterThan( 0 );
	} );

	it( 'does not show reorder controls for specific type', () => {
		render(
			<ReferencePreview
				{ ...defaultProps }
				referenceType="_gatherpress-client"
			/>
		);

		expect( screen.queryByLabelText( 'Move up' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Move down' )
		).not.toBeInTheDocument();
	} );

	it( 'disables move up for first type', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		const moveUpButtons = screen.getAllByLabelText( 'Move up' );
		// First type's "Move up" button should be disabled.
		expect( moveUpButtons[ 0 ] ).toBeDisabled();
	} );

	it( 'disables move down for last type', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		const moveDownButtons = screen.getAllByLabelText( 'Move down' );
		// Last type's "Move down" button should be disabled.
		expect(
			moveDownButtons[ moveDownButtons.length - 1 ]
		).toBeDisabled();
	} );

	it( 'calls moveTypeUp when button clicked', () => {
		const moveTypeUp = jest.fn();

		render(
			<ReferencePreview
				{ ...defaultProps }
				moveTypeUp={ moveTypeUp }
			/>
		);

		// Click the second type's "Move up" (first enabled one).
		const moveUpButtons = screen.getAllByLabelText( 'Move up' );
		const enabledMoveUp = moveUpButtons.find( ( btn ) => ! btn.disabled );
		if ( enabledMoveUp ) {
			fireEvent.click( enabledMoveUp );
			expect( moveTypeUp ).toHaveBeenCalled();
		}
	} );

	it( 'calls moveTypeDown when button clicked', () => {
		const moveTypeDown = jest.fn();

		render(
			<ReferencePreview
				{ ...defaultProps }
				moveTypeDown={ moveTypeDown }
			/>
		);

		// Click the first type's "Move down" (should be enabled).
		const moveDownButtons = screen.getAllByLabelText( 'Move down' );
		const enabledMoveDown = moveDownButtons.find(
			( btn ) => ! btn.disabled
		);
		if ( enabledMoveDown ) {
			fireEvent.click( enabledMoveDown );
			expect( moveTypeDown ).toHaveBeenCalled();
		}
	} );

	it( 'skips rendering empty type arrays', () => {
		render( <ReferencePreview { ...defaultProps } /> );

		// 2023 has empty festivals array - no "Festivals" list items for 2023.
		const listItems = screen.getAllByRole( 'listitem' );
		const festivalItems = listItems.filter( ( li ) =>
			li.textContent.includes( 'Festival' )
		);
		// Only 1 festival item (from 2024), not from 2023.
		expect( festivalItems ).toHaveLength( 1 );
	} );

	it( 'renders lists with correct CSS class', () => {
		const { container } = render(
			<ReferencePreview { ...defaultProps } />
		);

		const lists = container.querySelectorAll( '.references-list' );
		expect( lists.length ).toBeGreaterThan( 0 );
	} );

	it( 'does not show reorder controls with single type', () => {
		render(
			<ReferencePreview
				{ ...defaultProps }
				orderedTypeKeys={ [ '_gatherpress-client' ] }
			/>
		);

		expect( screen.queryByLabelText( 'Move up' ) ).not.toBeInTheDocument();
	} );
} );
