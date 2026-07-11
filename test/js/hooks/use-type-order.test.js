/**
 * Tests for useTypeOrder hook.
 *
 * @since 0.1.0
 */

import { renderHook, act } from '@testing-library/react';
import useTypeOrder from '../../../src/hooks/use-type-order';

describe( 'useTypeOrder', () => {
	const config = {
		ref_tax: 'gatherpress-production',
		ref_types: [
			'_gatherpress-client',
			'_gatherpress-festival',
			'_gatherpress-award',
		],
	};

	it( 'returns config ref_types when no typeOrder is set', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: null,
				setAttributes,
			} )
		);

		expect( result.current.orderedTypeKeys ).toEqual(
			config.ref_types
		);
	} );

	it( 'returns empty array when config is null', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config: null,
				typeOrder: null,
				setAttributes,
			} )
		);

		expect( result.current.orderedTypeKeys ).toEqual( [] );
	} );

	it( 'respects custom typeOrder', () => {
		const setAttributes = jest.fn();
		const customOrder = [
			'_gatherpress-award',
			'_gatherpress-client',
			'_gatherpress-festival',
		];

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: customOrder,
				setAttributes,
			} )
		);

		expect( result.current.orderedTypeKeys ).toEqual( customOrder );
	} );

	it( 'filters out invalid types from typeOrder', () => {
		const setAttributes = jest.fn();
		const invalidOrder = [
			'_gatherpress-award',
			'nonexistent-type',
			'_gatherpress-client',
		];

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: invalidOrder,
				setAttributes,
			} )
		);

		expect( result.current.orderedTypeKeys ).toContain(
			'_gatherpress-award'
		);
		expect( result.current.orderedTypeKeys ).toContain(
			'_gatherpress-client'
		);
		expect( result.current.orderedTypeKeys ).not.toContain(
			'nonexistent-type'
		);
		// Missing types should be appended.
		expect( result.current.orderedTypeKeys ).toContain(
			'_gatherpress-festival'
		);
	} );

	it( 'initializes typeOrder attribute on mount if not set', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: null,
				setAttributes,
			} )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			typeOrder: config.ref_types,
		} );
	} );

	it( 'does not re-initialize typeOrder if already set', () => {
		const setAttributes = jest.fn();

		renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: config.ref_types,
				setAttributes,
			} )
		);

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'moves type up correctly', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: config.ref_types,
				setAttributes,
			} )
		);

		act( () => {
			result.current.moveTypeUp( '_gatherpress-festival' );
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			typeOrder: [
				'_gatherpress-festival',
				'_gatherpress-client',
				'_gatherpress-award',
			],
		} );
	} );

	it( 'does not move first type up', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: config.ref_types,
				setAttributes,
			} )
		);

		act( () => {
			result.current.moveTypeUp( '_gatherpress-client' );
		} );

		// Should not have been called for move (may have been called for init).
		const moveCalls = setAttributes.mock.calls.filter(
			( call ) => call[ 0 ].typeOrder !== undefined
		);
		// The only typeOrder call should NOT be from moveTypeUp since it's the first item.
		const moveUpCall = moveCalls.find( ( call ) => {
			const order = call[ 0 ].typeOrder;
			return order[ 0 ] !== '_gatherpress-client';
		} );
		expect( moveUpCall ).toBeUndefined();
	} );

	it( 'moves type down correctly', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: config.ref_types,
				setAttributes,
			} )
		);

		act( () => {
			result.current.moveTypeDown( '_gatherpress-client' );
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			typeOrder: [
				'_gatherpress-festival',
				'_gatherpress-client',
				'_gatherpress-award',
			],
		} );
	} );

	it( 'does not move last type down', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: config.ref_types,
				setAttributes,
			} )
		);

		act( () => {
			result.current.moveTypeDown( '_gatherpress-award' );
		} );

		const moveCalls = setAttributes.mock.calls.filter( ( call ) => {
			const order = call[ 0 ].typeOrder;
			return (
				order &&
				order[ order.length - 1 ] !== '_gatherpress-award'
			);
		} );
		expect( moveCalls ).toHaveLength( 0 );
	} );

	it( 'appends missing types from config to custom order', () => {
		const setAttributes = jest.fn();

		const { result } = renderHook( () =>
			useTypeOrder( {
				config,
				typeOrder: [ '_gatherpress-award' ],
				setAttributes,
			} )
		);

		expect( result.current.orderedTypeKeys ).toEqual( [
			'_gatherpress-award',
			'_gatherpress-client',
			'_gatherpress-festival',
		] );
	} );
} );
