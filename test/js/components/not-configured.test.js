/**
 * Tests for NotConfigured component.
 *
 * @since 0.1.0
 */

import { render, screen } from '@testing-library/react';
import NotConfigured from '../../../src/components/not-configured';

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
	Notice: ( { children, status } ) => (
		<div data-testid="notice" data-status={ status }>
			{ children }
		</div>
	),
} ) );

describe( 'NotConfigured', () => {
	const defaultProps = {
		blockProps: { className: 'wp-block-gatherpress-references' },
		supportedPostTypes: [],
	};

	it( 'renders warning notices', () => {
		render( <NotConfigured { ...defaultProps } /> );

		const notices = screen.getAllByTestId( 'notice' );
		expect( notices.length ).toBeGreaterThanOrEqual( 1 );

		const warningNotices = notices.filter(
			( n ) => n.getAttribute( 'data-status' ) === 'warning'
		);
		expect( warningNotices.length ).toBeGreaterThan( 0 );
	} );

	it( 'renders inspector controls', () => {
		render( <NotConfigured { ...defaultProps } /> );

		expect(
			screen.getByTestId( 'inspector-controls' )
		).toBeInTheDocument();
	} );

	it( 'shows supported post types when available', () => {
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

		render( <NotConfigured { ...props } /> );

		expect( screen.getByText( /Supported post types/i ) ).toBeInTheDocument();
		expect( screen.getByText( /Events, Posts/i ) ).toBeInTheDocument();
	} );

	it( 'does not show supported post types label when list is empty', () => {
		render( <NotConfigured { ...defaultProps } /> );

		expect(
			screen.queryByText( /Supported post types/i )
		).not.toBeInTheDocument();
	} );

	it( 'applies blockProps to wrapper div', () => {
		const { container } = render(
			<NotConfigured { ...defaultProps } />
		);

		const wrapper = container.querySelector(
			'.wp-block-gatherpress-references'
		);
		expect( wrapper ).toBeInTheDocument();
	} );
} );
