<?php
/**
 * Integration tests for taxonomy registration.
 *
 * @package GatherPress_References
 */

namespace GatherPress\References\Tests\Integration;

use GatherPress\References\Config_Manager;
use GatherPress\References\Taxonomy_Manager;
use WP_UnitTestCase;

/**
 * Class TaxonomyRegistrationTest
 *
 * Tests that taxonomies are registered correctly within WordPress.
 *
 * @since 0.1.0
 */
class TaxonomyRegistrationTest extends WP_UnitTestCase {

	/**
	 * Test that the _gatherpress_play taxonomy exists
	 * after the plugin is loaded (if GatherPress event type is registered).
	 */
	public function test_production_taxonomy_exists() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$this->assertTrue( taxonomy_exists( '_gatherpress_play' ) );
	}

	/**
	 * Test that client taxonomy exists after the plugin is loaded.
	 */
	public function test_client_taxonomy_exists() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$this->assertTrue( taxonomy_exists( 'gatherpress_client' ) );
	}

	/**
	 * Test that festival taxonomy exists after the plugin is loaded.
	 */
	public function test_festival_taxonomy_exists() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$this->assertTrue( taxonomy_exists( 'gatherpress_festival' ) );
	}

	/**
	 * Test that award taxonomy exists after the plugin is loaded.
	 */
	public function test_award_taxonomy_exists() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$this->assertTrue( taxonomy_exists( 'gatherpress_award' ) );
	}

	/**
	 * Test that taxonomies are REST-enabled.
	 */
	public function test_taxonomies_are_rest_enabled() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$taxonomies = array( 'gatherpress_client', 'gatherpress_festival', 'gatherpress_award' );

		foreach ( $taxonomies as $taxonomy_slug ) {
			$taxonomy = get_taxonomy( $taxonomy_slug );
			if ( $taxonomy ) {
				$this->assertTrue( $taxonomy->show_in_rest, "Taxonomy {$taxonomy_slug} should be REST-enabled." );
			}
		}
	}

	/**
	 * Test that config manager detects post type support.
	 */
	public function test_config_manager_detects_support() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$config_manager = new Config_Manager();
		$config         = $config_manager->get_config( 'gatherpress_event' );

		$this->assertNotNull( $config );
		$this->assertArrayHasKey( 'ref_tax', $config );
		$this->assertArrayHasKey( 'ref_types', $config );
		$this->assertEquals( '_gatherpress_play', $config['ref_tax'] );
		$this->assertContains( 'gatherpress_client', $config['ref_types'] );
		$this->assertContains( 'gatherpress_festival', $config['ref_types'] );
		$this->assertContains( 'gatherpress_award', $config['ref_types'] );
	}
}
