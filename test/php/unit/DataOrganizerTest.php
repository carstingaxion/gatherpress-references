<?php
/**
 * Unit tests for Data_Organizer class.
 *
 * @package GatherPress_References
 */

namespace GatherPress\References\Tests\Unit;

use GatherPress\References\Data_Organizer;
use GatherPress\References\Config_Manager;
use WP_UnitTestCase;

/**
 * Class DataOrganizerTest
 *
 * Tests the Data_Organizer class.
 *
 * @since 0.1.0
 */
class DataOrganizerTest extends WP_UnitTestCase {

	/**
	 * Data organizer instance.
	 *
	 * @var Data_Organizer
	 */
	private Data_Organizer $data_organizer;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();
		$this->data_organizer = new Data_Organizer( new Config_Manager() );
	}

	/**
	 * Test sort_years with descending order.
	 */
	public function test_sort_years_descending() {
		$references = array(
			'2022' => array( 'gatherpress_client' => array( 'Client A' ) ),
			'2024' => array( 'gatherpress_client' => array( 'Client B' ) ),
			'2023' => array( 'gatherpress_client' => array( 'Client C' ) ),
		);

		$result = $this->data_organizer->sort_years( $references, 'desc' );
		$years  = array_keys( $result );

		$this->assertEquals( array( '2024', '2023', '2022' ), $years );
	}

	/**
	 * Test sort_years with ascending order.
	 */
	public function test_sort_years_ascending() {
		$references = array(
			'2024' => array( 'gatherpress_client' => array( 'Client B' ) ),
			'2022' => array( 'gatherpress_client' => array( 'Client A' ) ),
			'2023' => array( 'gatherpress_client' => array( 'Client C' ) ),
		);

		$result = $this->data_organizer->sort_years( $references, 'asc' );
		$years  = array_keys( $result );

		$this->assertEquals( array( '2022', '2023', '2024' ), $years );
	}

	/**
	 * Test sort_years with empty array.
	 */
	public function test_sort_years_empty_array() {
		$result = $this->data_organizer->sort_years( array(), 'desc' );

		$this->assertEmpty( $result );
	}

	/**
	 * Test sort_years preserves data integrity.
	 */
	public function test_sort_years_preserves_data() {
		$references = array(
			'2023' => array(
				'gatherpress_client'   => array( 'Client A', 'Client B' ),
				'gatherpress_festival' => array( 'Festival X' ),
			),
			'2024' => array(
				'gatherpress_award' => array( 'Award 1' ),
			),
		);

		$result = $this->data_organizer->sort_years( $references, 'desc' );

		$this->assertArrayHasKey( '2024', $result );
		$this->assertArrayHasKey( '2023', $result );
		$this->assertEquals( array( 'Award 1' ), $result['2024']['gatherpress_award'] );
		$this->assertEquals( array( 'Client A', 'Client B' ), $result['2023']['gatherpress_client'] );
	}

	/**
	 * Test sort_years with single year.
	 */
	public function test_sort_years_single_year() {
		$references = array(
			'2024' => array( 'gatherpress_client' => array( 'Client A' ) ),
		);

		$result = $this->data_organizer->sort_years( $references, 'desc' );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( '2024', $result );
	}

	/**
	 * Test sort_years maintains all taxonomy data.
	 */
	public function test_sort_years_maintains_all_taxonomy_data() {
		$references = array(
			'2022' => array(
				'gatherpress_client'   => array( 'Client A' ),
				'gatherpress_festival' => array( 'Festival B' ),
				'gatherpress_award'    => array( 'Award C' ),
			),
		);

		$result = $this->data_organizer->sort_years( $references, 'asc' );

		$this->assertCount( 3, $result['2022'] );
		$this->assertArrayHasKey( 'gatherpress_client', $result['2022'] );
		$this->assertArrayHasKey( 'gatherpress_festival', $result['2022'] );
		$this->assertArrayHasKey( 'gatherpress_award', $result['2022'] );
	}
}
