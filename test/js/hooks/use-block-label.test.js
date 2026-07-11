/**
 * Tests for useBlockLabel hook.
 *
 * @since 0.1.0
 */

import { renderHook } from '@testing-library/react';
import useBlockLabel from '../../../src/hooks/use-block-label';

describe( 'useBlockLabel', () => {
	const baseProps = {
		refTermId: 0,
		year: 0,
		referenceType: 'all',
		refTerms: [
			{ id: 1, name: 'Hamlet' },
			{ id: 2, name: 'Macbeth' },
		],
		typeLabels: {
			'_gatherpress-client': 'Clients',
			'_gatherpress-festival': 'Festivals',
			'_gatherpress-award': 'Awards',
		},
		isConfigured: true,
		setAttributes: jest.fn(),
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'sets default label when no filters are active', () => {
		renderHook( () => useBlockLabel( baseProps ) );

		expect( baseProps.setAttributes ).toHaveBeenCalledWith( {
			metadata: { name: 'References' },
		} );
	} );

	it( 'includes term name when refTermId is set', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				refTermId: 1,
				setAttributes,
			} )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			metadata: { name: expect.stringContaining( 'Hamlet' ) },
		} );
	} );

	it( 'includes year when year filter is set', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				year: 2024,
				setAttributes,
			} )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			metadata: { name: expect.stringContaining( '2024' ) },
		} );
	} );

	it( 'includes type label when specific type is selected', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				referenceType: '_gatherpress-award',
				setAttributes,
			} )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			metadata: { name: expect.stringContaining( 'Awards' ) },
		} );
	} );

	it( 'combines all active filters in label', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				refTermId: 2,
				year: 2023,
				referenceType: '_gatherpress-festival',
				setAttributes,
			} )
		);

		const call = setAttributes.mock.calls[ 0 ][ 0 ];
		const label = call.metadata.name;

		expect( label ).toContain( 'References' );
		expect( label ).toContain( 'Macbeth' );
		expect( label ).toContain( '2023' );
		expect( label ).toContain( 'Festivals' );
	} );

	it( 'does not update when not configured', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				isConfigured: false,
				setAttributes,
			} )
		);

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'does not call setAttributes again if label has not changed', () => {
		const setAttributes = jest.fn();

		const { rerender } = renderHook(
			( props ) => useBlockLabel( props ),
			{
				initialProps: { ...baseProps, setAttributes },
			}
		);

		const callCount = setAttributes.mock.calls.length;

		// Re-render with same props.
		rerender( { ...baseProps, setAttributes } );

		// Should not have called setAttributes again.
		expect( setAttributes.mock.calls.length ).toBe( callCount );
	} );

	it( 'uses raw type slug when label is not found', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				referenceType: '_unknown-type',
				typeLabels: {},
				setAttributes,
			} )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			metadata: {
				name: expect.stringContaining( '_unknown-type' ),
			},
		} );
	} );

	it( 'uses bullet separator between filter parts', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useBlockLabel( {
				...baseProps,
				refTermId: 1,
				year: 2024,
				setAttributes,
			} )
		);

		const call = setAttributes.mock.calls[ 0 ][ 0 ];
		const label = call.metadata.name;

		// Unicode bullet: \u2022
		expect( label ).toContain( '\u2022' );
	} );
} );
