/**
 * Tests for placeholder data utilities.
 *
 * @since 0.1.0
 */

import {
	getPlaceholderData,
	filterPlaceholderData,
	getSortedYears,
} from '../../../src/utils/placeholder-data';

describe( 'getPlaceholderData', () => {
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

	const config = {
		ref_tax: 'gatherpress-production',
		ref_types: orderedTypeKeys,
	};

	it( 'returns empty object when not configured', () => {
		const result = getPlaceholderData( {
			isConfigured: false,
			config: null,
			year: 0,
			orderedTypeKeys,
			typeLabels,
		} );

		expect( result ).toEqual( {} );
	} );

	it( 'returns empty object when config has no ref_types', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config: { ref_tax: 'test' },
			year: 0,
			orderedTypeKeys,
			typeLabels,
		} );

		expect( result ).toEqual( {} );
	} );

	it( 'returns single year when year filter is set', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config,
			year: 2024,
			orderedTypeKeys,
			typeLabels,
		} );

		const years = Object.keys( result );
		expect( years ).toHaveLength( 1 );
		expect( years[ 0 ] ).toBe( '2024' );
	} );

	it( 'returns two years when year filter is 0', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config,
			year: 0,
			orderedTypeKeys,
			typeLabels,
		} );

		const years = Object.keys( result );
		expect( years ).toHaveLength( 2 );
	} );

	it( 'generates example items for each type', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config,
			year: 2024,
			orderedTypeKeys,
			typeLabels,
		} );

		const yearData = result[ '2024' ];
		expect( yearData ).toBeDefined();
		expect( yearData[ '_gatherpress-client' ] ).toHaveLength( 2 );
		expect( yearData[ '_gatherpress-festival' ] ).toHaveLength( 2 );
		expect( yearData[ '_gatherpress-award' ] ).toHaveLength( 2 );
	} );

	it( 'uses type labels in example item names', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config,
			year: 2024,
			orderedTypeKeys,
			typeLabels,
		} );

		const clientItems = result[ '2024' ][ '_gatherpress-client' ];
		expect( clientItems[ 0 ] ).toContain( 'Clients' );
	} );

	it( 'sorts example items alphabetically', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config,
			year: 2024,
			orderedTypeKeys,
			typeLabels,
		} );

		const items = result[ '2024' ][ '_gatherpress-client' ];
		const sorted = [ ...items ].sort();
		expect( items ).toEqual( sorted );
	} );

	it( 'handles single type in orderedTypeKeys', () => {
		const result = getPlaceholderData( {
			isConfigured: true,
			config,
			year: 2024,
			orderedTypeKeys: [ '_gatherpress-client' ],
			typeLabels,
		} );

		const yearData = result[ '2024' ];
		expect( Object.keys( yearData ) ).toHaveLength( 1 );
		expect( yearData[ '_gatherpress-client' ] ).toBeDefined();
	} );
} );

describe( 'filterPlaceholderData', () => {
	const sampleData = {
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

	it( 'returns all data when referenceType is "all"', () => {
		const result = filterPlaceholderData( sampleData, 'all' );
		expect( result ).toEqual( sampleData );
	} );

	it( 'filters to specific type', () => {
		const result = filterPlaceholderData(
			sampleData,
			'_gatherpress-client'
		);

		expect( Object.keys( result ) ).toHaveLength( 2 );
		expect( result[ '2024' ] ).toEqual( {
			'_gatherpress-client': [ 'Client A', 'Client B' ],
		} );
		expect( result[ '2023' ] ).toEqual( {
			'_gatherpress-client': [ 'Client C' ],
		} );
	} );

	it( 'removes years with empty arrays for filtered type', () => {
		const result = filterPlaceholderData(
			sampleData,
			'_gatherpress-festival'
		);

		expect( Object.keys( result ) ).toHaveLength( 1 );
		expect( result[ '2024' ] ).toBeDefined();
		expect( result[ '2023' ] ).toBeUndefined();
	} );

	it( 'returns empty object for non-existent type', () => {
		const result = filterPlaceholderData(
			sampleData,
			'_nonexistent-type'
		);
		expect( result ).toEqual( {} );
	} );

	it( 'handles empty input data', () => {
		const result = filterPlaceholderData( {}, 'all' );
		expect( result ).toEqual( {} );
	} );

	it( 'handles empty input with specific type filter', () => {
		const result = filterPlaceholderData(
			{},
			'_gatherpress-client'
		);
		expect( result ).toEqual( {} );
	} );
} );

describe( 'getSortedYears', () => {
	const filteredData = {
		'2022 ': { '_gatherpress-client': [ 'A' ] },
		'2024 ': { '_gatherpress-client': [ 'B' ] },
		'2023 ': { '_gatherpress-client': [ 'C' ] },
	};

	it( 'returns unsorted keys when year filter is set', () => {
		const singleYearData = {
			2024: { '_gatherpress-client': [ 'A' ] },
		};
		const result = getSortedYears( singleYearData, 2024, 'desc' );
		expect( result ).toHaveLength( 1 );
	} );

	it( 'sorts descending by default', () => {
		const result = getSortedYears( filteredData, 0, 'desc' );
		const numericYears = result.map( ( y ) => parseInt( y ) );

		expect( numericYears[ 0 ] ).toBeGreaterThan( numericYears[ 1 ] );
		expect( numericYears[ 1 ] ).toBeGreaterThan( numericYears[ 2 ] );
	} );

	it( 'sorts ascending when specified', () => {
		const result = getSortedYears( filteredData, 0, 'asc' );
		const numericYears = result.map( ( y ) => parseInt( y ) );

		expect( numericYears[ 0 ] ).toBeLessThan( numericYears[ 1 ] );
		expect( numericYears[ 1 ] ).toBeLessThan( numericYears[ 2 ] );
	} );

	it( 'handles empty data', () => {
		const result = getSortedYears( {}, 0, 'desc' );
		expect( result ).toEqual( [] );
	} );

	it( 'handles single year', () => {
		const singleData = {
			'2024 ': { '_gatherpress-client': [ 'A' ] },
		};
		const result = getSortedYears( singleData, 0, 'desc' );
		expect( result ).toHaveLength( 1 );
	} );
} );
