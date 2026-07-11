<?php
/*
Plugin Name: Demo Data Generator for GatherPress References
Description: Generates clients, festivals and awards demo data for GatherPress References plugin.
*/

namespace GatherPress\ReferencesDemodata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GatherPress References Demodata Plugin
 *
 * @since 0.1.0
 */
class Plugin {
	/**
	 * Singleton instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Private constructor to enforce singleton pattern
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Get singleton instance
	 *
	 * Creates instance on first call, returns existing instance on subsequent calls.
	 *
	 * @since 0.1.0
	 * @return Plugin The singleton instance.
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function init_hooks(): void {
		// Admin interface hooks.
		add_action( 'admin_menu', array( $this, 'add_demo_data_menu' ) );
	}

	/**
	 * Add demo data submenu page
	 *
	 * Creates an admin page under GatherPress Events menu for generating test data.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_demo_data_menu(): void {
		add_submenu_page(
			'edit.php?post_type=gatherpress_event',
			__( 'Generate Demo Data', 'gatherpress-references' ),
			__( 'Demo Data', 'gatherpress-references' ),
			'manage_options',
			'gatherpress-references-demo-data',
			array( $this, 'render_demo_data_page' )
		);
	}

	/**
	 * Render demo data admin page
	 *
	 * Displays interface for generating and deleting demo content.
	 * Includes nonce verification for security.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_demo_data_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['generate_demo_data'] ) && check_admin_referer( 'gatherpress_references_demo_data' ) ) {
			$this->generate_demo_data();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Demo data generated successfully!', 'gatherpress-references' ) . '</p></div>';
		}

		if ( isset( $_POST['delete_demo_data'] ) && check_admin_referer( 'gatherpress_references_demo_data' ) ) {
			$this->delete_demo_data();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Demo data deleted successfully!', 'gatherpress-references' ) . '</p></div>';
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'References Demo Data', 'gatherpress-references' ); ?></h1>
			<p><?php esc_html_e( 'Generate sample GatherPress events with reference terms for development and testing.', 'gatherpress-references' ); ?></p>
			
			<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
				<h2><?php esc_html_e( 'Generate Demo Data', 'gatherpress-references' ); ?></h2>
				<p><?php esc_html_e( 'This will create:', 'gatherpress-references' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( '5  reference terms (theater productions)', 'gatherpress-references' ); ?></li>
					<li><?php esc_html_e( '20 GatherPress event posts', 'gatherpress-references' ); ?></li>
					<li><?php esc_html_e( '8 client terms', 'gatherpress-references' ); ?></li>
					<li><?php esc_html_e( '6 festival terms', 'gatherpress-references' ); ?></li>
					<li><?php esc_html_e( '6 award terms', 'gatherpress-references' ); ?></li>
				</ul>
				<form method="post" style="margin-top: 20px;">
					<?php wp_nonce_field( 'gatherpress_references_demo_data' ); ?>
					<button type="submit" name="generate_demo_data" class="button button-primary">
						<?php esc_html_e( 'Generate Demo Data', 'gatherpress-references' ); ?>
					</button>
				</form>
			</div>

			<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
				<h2><?php esc_html_e( 'Delete Demo Data', 'gatherpress-references' ); ?></h2>
				<p><?php esc_html_e( 'This will remove all demo GatherPress events and terms created by this tool.', 'gatherpress-references' ); ?></p>
				<form method="post" style="margin-top: 20px;">
					<?php wp_nonce_field( 'gatherpress_references_demo_data' ); ?>
					<button type="submit" name="delete_demo_data" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete all demo data?', 'gatherpress-references' ); ?>')">
						<?php esc_html_e( 'Delete Demo Data', 'gatherpress-references' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate demo data
	 *
	 * Creates sample events and taxonomy terms for testing.
	 * Uses the first available post type with 'gatherpress-references' support.
	 * Demo data uses the theater production example by default.
	 *
	 * Creates:
	 * - 5 reference taxonomy terms (theater productions)
	 * - 8 client terms
	 * - 6 festival terms
	 * - 6 award terms
	 * - 20 GatherPress event posts with random term assignments
	 *
	 * All demo items are marked with '_demo_data' meta for easy cleanup.
	 * Uses GatherPress's Event class to properly initialize event dates.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function generate_demo_data(): void {
		$gatherpress_references_plugin = \GatherPress\References\Plugin::get_instance();
		$configs                       = $gatherpress_references_plugin->get_all_configs();
		
		if ( empty( $configs ) ) {
			return;
		}
		
		// Use the first configured post type (typically 'gatherpress_event').
		$post_type = array_key_first( $configs );
		$config    = $configs[ $post_type ];
		
		// Sample reference taxonomy term names (theater productions).
		$ref_terms = array( 'Hamlet', 'Romeo and Juliet', 'A Midsummer Night\'s Dream', 'Macbeth', 'The Tempest' );
		
		// Sample client names from major theater cities.
		$clients = array(
			'Royal Theater London',
			'Berlin Staatstheater',
			'Paris National Opera',
			'Vienna Burgtheater',
			'Moscow Art Theatre',
			'Sydney Opera House',
			'New York Broadway Theater',
			'Madrid Teatro Real',
		);
		
		// Sample festival names from renowned international festivals.
		$festivals = array(
			'Edinburgh International Festival',
			'Avignon Festival',
			'Salzburg Festival',
			'Venice Biennale Teatro',
			'Festival d\'Automne à Paris',
			'Berlin Theatertreffen',
		);
		
		// Sample award names.
		$awards = array(
			'Best Director Award',
			'Outstanding Production',
			'Best Ensemble Performance',
			'Critics\' Choice Award',
			'Theatre Excellence Prize',
			'Innovation in Theatre Award',
		);

		// Create reference taxonomy terms and store IDs.
		$ref_term_ids = array();
		if ( ! empty( $config['ref_tax'] ) && taxonomy_exists( $config['ref_tax'] ) ) {
			foreach ( $ref_terms as $ref_term ) {
				$term = wp_insert_term( $ref_term, $config['ref_tax'] );
				if ( ! is_wp_error( $term ) ) {
					$ref_term_ids[] = $term['term_id'];
					// Mark as demo data for cleanup.
					update_term_meta( $term['term_id'], '_demo_data', '1' );
				}
			}
		}

		// Create client terms.
		$client_ids = array();
		if ( in_array( 'gatherpress_client', $config['ref_types'], true ) && taxonomy_exists( 'gatherpress_client' ) ) {
			foreach ( $clients as $client ) {
				$term = wp_insert_term( $client, 'gatherpress_client' );
				if ( ! is_wp_error( $term ) ) {
					$client_ids[] = $term['term_id'];
					update_term_meta( $term['term_id'], '_demo_data', '1' );
				}
			}
		}

		// Create festival terms.
		$festival_ids = array();
		if ( in_array( 'gatherpress_festival', $config['ref_types'], true ) && taxonomy_exists( 'gatherpress_festival' ) ) {
			foreach ( $festivals as $festival ) {
				$term = wp_insert_term( $festival, 'gatherpress_festival' );
				if ( ! is_wp_error( $term ) ) {
					$festival_ids[] = $term['term_id'];
					update_term_meta( $term['term_id'], '_demo_data', '1' );
				}
			}
		}

		// Create award terms.
		$award_ids = array();
		if ( in_array( 'gatherpress_award', $config['ref_types'], true ) && taxonomy_exists( 'gatherpress_award' ) ) {
			foreach ( $awards as $award ) {
				$term = wp_insert_term( $award, 'gatherpress_award' );
				if ( ! is_wp_error( $term ) ) {
					$award_ids[] = $term['term_id'];
					update_term_meta( $term['term_id'], '_demo_data', '1' );
				}
			}
		}

		// Generate 20 GatherPress event posts with realistic data.
		for ( $i = 0; $i < 20; $i++ ) {
			// Generate random date between 2018-2024.
			$year     = wp_rand( 2018, 2024 );
			$month    = wp_rand( 1, 12 );
			$day      = wp_rand( 1, 28 );
			$date     = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			$ref_term = $ref_terms[ array_rand( $ref_terms ) ];

			$event_data = array(
				'post_title'   => $ref_term . ' - Event ' . ( $i + 1 ),
				'post_content' => 'Demo event for ' . $ref_term . '.',
				'post_status'  => 'publish',
				'post_type'    => $post_type,
				'post_date'    => $date . ' 19:00:00',
			);

			$post_id = wp_insert_post( $event_data, true );

			if ( ! is_wp_error( $post_id ) ) {
				// Mark as demo data.
				update_post_meta( $post_id, '_demo_data', '1' );

				// Initialize GatherPress event data using the Event class.
				if ( class_exists( '\GatherPress\Core\Event' ) ) {
					$event = new \GatherPress\Core\Event( $post_id );
					
					// Set event date/time in GatherPress format.
					$datetime_start = $date . ' 19:00:00';
					$datetime_end   = $date . ' 22:00:00';
					
					$event->save_datetimes(
						array(
							'datetime_start' => $datetime_start,
							'datetime_end'   => $datetime_end,
							'timezone'       => 'UTC',
						)
					);
				}

				// Assign reference taxonomy term by term_id.
				if ( ! empty( $ref_term_ids ) ) {
					$selected_ref_term = $ref_term_ids[ array_rand( $ref_term_ids ) ];
					wp_set_object_terms( $post_id, $selected_ref_term, $config['ref_tax'], false );
				}

				// Use randomization to create varied reference patterns.
				$rand = wp_rand( 1, 100 );

				// 60% chance of having client references (1-2 clients).
				if ( $rand < 60 && ! empty( $client_ids ) ) {
					// Randomly select 1 or 2 clients.
					$num_clients      = wp_rand( 1, 2 );
					$selected_clients = array();
					for ( $v = 0; $v < $num_clients; $v++ ) {
						$selected_clients[] = $client_ids[ array_rand( $client_ids ) ];
					}
					// Remove duplicates.
					$selected_clients = array_unique( $selected_clients );
					wp_set_object_terms( $post_id, $selected_clients, 'gatherpress_client', false );
				}

				// 40% chance of festival participation (30-70 range).
				if ( $rand > 30 && $rand < 70 && ! empty( $festival_ids ) ) {
					$selected_festival = $festival_ids[ array_rand( $festival_ids ) ];
					wp_set_object_terms( $post_id, $selected_festival, 'gatherpress_festival', false );
				}

				// 40% chance of award (60-100 range).
				if ( $rand > 60 && ! empty( $award_ids ) ) {
					$selected_award = $award_ids[ array_rand( $award_ids ) ];
					wp_set_object_terms( $post_id, $selected_award, 'gatherpress_award', false );
				}
			}
		}

		// Clear cache after generating demo data.
		$gatherpress_references_plugin->clear_all_caches();
	}

	/**
	 * Delete demo data
	 *
	 * Removes all posts and terms marked with '_demo_data' meta
	 * from all post types with 'gatherpress-references' support.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function delete_demo_data(): void {
		$gatherpress_references_plugin = \GatherPress\References\Plugin::get_instance();
		$post_types                    = get_post_types_by_support( 'gatherpress-references' );
		
		if ( empty( $post_types ) ) {
			return;
		}
		
		// Delete demo posts from all supported post types.
		foreach ( $post_types as $post_type ) {
			$demo_posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => 9999, // Large number to get all, but avoid -1.
					'meta_key'       => '_demo_data', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);

			// Permanently delete demo posts.
			foreach ( $demo_posts as $post ) {
				wp_delete_post( $post->ID, true );
			}
		}

		// Delete demo terms from all configured taxonomies.
		$taxonomies = $gatherpress_references_plugin->get_all_taxonomies();
		
		foreach ( $taxonomies as $taxonomy ) {
			$demo_terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids', // The return type of get_terms() varies depending on the value passed to $args['fields']. See WP_Term_Query::get_terms() for details.
					'meta_key'   => '_demo_data', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);

			if ( ! is_wp_error( $demo_terms ) ) {
				foreach ( $demo_terms as $term_id ) {
					wp_delete_term( $term_id, $taxonomy );
				}
			}
		}

		// Clear cache after cleanup.
		$gatherpress_references_plugin->clear_all_caches();
	}
}


/**
 * Plugin uninstall hook
 *
 * Completely removes all plugin data when WordPress uninstalls the plugin.
 * This function is registered with register_uninstall_hook() and is called
 * only when a user explicitly uninstalls the plugin from the WordPress admin.
 *
 * Removes:
 * - All taxonomy terms and term relationships
 * - All cached transient data
 * - Term meta for demo data markers
 *
 * Note: Does NOT delete:
 * - GatherPress events (they remain but lose term associations)
 * - Taxonomy registrations (these are removed when plugin files are deleted)
 *
 * @since 0.1.0
 * @global \wpdb $wpdb WordPress database abstraction object.
 * @return void
 */
function gatherpress_references_demodata_uninstall(): void {
	global $wpdb;

	if ( ! $wpdb instanceof \wpdb ) {
		return;
	}

	// Get all post types with 'gatherpress-references' support.
	$post_types = get_post_types_by_support( 'gatherpress-references' );
	$taxonomies = array();
	
	foreach ( $post_types as $post_type ) {
		$config = get_all_post_type_supports( $post_type );
		
		if ( isset( $config['gatherpress-references'] ) && is_array( $config['gatherpress-references'] ) ) {
			$ref_config = $config['gatherpress-references'][0];
			
			if ( ! empty( $ref_config['ref_tax'] ) ) {
				$taxonomies[] = $ref_config['ref_tax'];
			}
			if ( ! empty( $ref_config['ref_types'] ) && is_array( $ref_config['ref_types'] ) ) {
				$taxonomies = array_merge( $taxonomies, $ref_config['ref_types'] );
			}
		}
	}
	
	$taxonomies = array_unique( $taxonomies );
	// 1. Remove all terms and term relationships.
	foreach ( $taxonomies as $taxonomy ) {
		// Get all terms for this taxonomy.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_key'   => '_demo_data', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);

		// Delete term and all its relationships.
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}

		// Clean up term taxonomy table (belt and suspenders approach).
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$taxonomy
			)
		);
	}

	// 2. Clear all cached transients.
	// Plugin::get_instance()->clear_all_caches();

	// 3. Clean up orphaned term meta (demo data markers).
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		"DELETE FROM {$wpdb->termmeta} WHERE meta_key = '_demo_data'"
	);
}
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\gatherpress_references_demodata_uninstall' );


// Initialize the singleton instance.
Plugin::get_instance();