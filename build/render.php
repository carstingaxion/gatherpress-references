<?php
/**
 * GatherPress References Block - Frontend Renderer
 *
 * This file handles the server-side rendering of the GatherPress References block.
 * It integrates with the main plugin's singleton architecture for data retrieval
 * and rendering.
 *
 * @package GatherPress_References
 * @since 0.1.0
 */

namespace GatherPress\References;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( Block_Renderer::class ) ) {
	/**
	 * Block Renderer
	 *
	 * Singleton class responsible for rendering the references block on the frontend.
	 * Handles attribute extraction, data retrieval, and HTML generation.
	 *
	 * @since 0.1.0
	 */
	class Block_Renderer {
		/**
		 * Singleton instance
		 *
		 * @var Block_Renderer|null
		 */
		private static ?Block_Renderer $instance = null;

		/**
		 * Config manager instance
		 *
		 * @var Config_Manager
		 */
		private Config_Manager $config_manager;

		/**
		 * Cache manager instance
		 *
		 * @var Cache_Manager
		 */
		private Cache_Manager $cache_manager;

		/**
		 * Query builder instance
		 *
		 * @var Query_Builder
		 */
		private Query_Builder $query_builder;

		/**
		 * Data organizer instance
		 *
		 * @var Data_Organizer
		 */
		private Data_Organizer $data_organizer;

		/**
		 * Constructor
		 *
		 * @since 0.1.0
		 */
		private function __construct() {
			$plugin = Plugin::get_instance();

			$this->config_manager = new Config_Manager();
			$this->cache_manager  = $plugin->get_cache_manager();
			$this->query_builder  = $plugin->get_query_builder();
			$this->data_organizer = $plugin->get_data_organizer();
		}

		/**
		 * Get singleton instance
		 *
		 * @since 0.1.0
		 * @return Block_Renderer Instance.
		 */
		public static function get_instance(): Block_Renderer {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Render the block
		 *
		 * @since 0.1.0
		 * @param array{postType?: string, refTermId?: int, year?: int, referenceType?: string, headingLevel?: int, yearSortOrder?: string, typeOrder?: array<string>} $attributes Block attributes.
		 * @return string Rendered block HTML.
		 */
		public function render( array $attributes ): string {
			// Extract and sanitize attributes.
			$render_data = $this->prepare_render_data( $attributes );

			if ( ! $render_data ) {
				return '';
			}
			// Get references data.
			$references = $this->get_references_data(
				$render_data['post_type'],
				$render_data['ref_term_id'],
				$render_data['year'],
				$render_data['type']
			);

			if ( empty( $references ) ) {
				return '';
			}

			// Sort years.
			$references = $this->data_organizer->sort_years( $references, $render_data['year_sort'] );

			// Apply custom type order if specified.
			if ( ! empty( $render_data['type_order'] ) ) {
				$references = $this->apply_type_order( $references, $render_data['type_order'] );
			}

			// Generate HTML.
			return $this->generate_html(
				$references,
				$render_data['type'],
				$render_data['type_labels'],
				$render_data['heading_level'],
				$render_data['secondary_heading_level']
			);
		}

		/**
		 * Prepare render data from attributes
		 *
		 * @since 0.1.0
		 * @param array{postType?: string, refTermId?: int, year?: int, referenceType?: string, headingLevel?: int, yearSortOrder?: string, typeOrder?: array<string>} $attributes Block attributes.
		 * @return ?array{post_type: string, ref_term_id: int, year: int, type: string, heading_level: int, secondary_heading_level: int, year_sort: string, type_order: array<string>, type_labels: array<string, string>}
		 */
		private function prepare_render_data( array $attributes ): ?array {
			$sanitized = $this->sanitize_attributes( $attributes );

			// Validate post type and get configuration.
			$config = $this->get_validated_config( $sanitized['post_type'] );
			if ( ! $config ) {
				return null;
			}

			// Normalize heading levels.
			$heading_level           = max( 1, min( 6, $sanitized['heading_level'] ) );
			$secondary_heading_level = min( $heading_level + 1, 6 );

			// Validate sort order.
			$year_sort = in_array( $sanitized['year_sort'], array( 'asc', 'desc' ), true )
				? $sanitized['year_sort']
				: 'desc';

			// Auto-detect reference term from archive.
			$ref_term_id = $this->resolve_ref_term_id( $sanitized['ref_term_id'], $config['ref_tax'] );

			return array(
				'post_type'               => $sanitized['post_type'],
				'ref_term_id'             => $ref_term_id,
				'year'                    => $sanitized['year'],
				'type'                    => $sanitized['type'],
				'heading_level'           => $heading_level,
				'secondary_heading_level' => $secondary_heading_level,
				'year_sort'               => $year_sort,
				'type_order'              => $sanitized['type_order'],
				'type_labels'             => $this->get_type_labels( $config['ref_types'] ),
			);
		}

		/**
		 * Sanitize block attributes
		 *
		 * @since 0.1.0
		 * @param array{postType?: string, refTermId?: int, year?: int, referenceType?: string, headingLevel?: int, yearSortOrder?: string, typeOrder?: array<string>} $attributes Block attributes.
		 * @return array{post_type: string, ref_term_id: int, year: int, type: string, heading_level: int, year_sort: string, type_order: array<string>}
		 */
		private function sanitize_attributes( array $attributes ): array {
			return array(
				'post_type'     => isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : '',
				'ref_term_id'   => isset( $attributes['refTermId'] ) ? intval( $attributes['refTermId'] ) : 0,
				'year'          => isset( $attributes['year'] ) ? intval( $attributes['year'] ) : 0,
				'type'          => isset( $attributes['referenceType'] ) ? sanitize_text_field( $attributes['referenceType'] ) : 'all',
				'heading_level' => isset( $attributes['headingLevel'] ) ? intval( $attributes['headingLevel'] ) : 2,
				'year_sort'     => isset( $attributes['yearSortOrder'] ) ? sanitize_text_field( $attributes['yearSortOrder'] ) : 'desc',
				'type_order'    => isset( $attributes['typeOrder'] ) ? array_map( 'sanitize_text_field', $attributes['typeOrder'] ) : array(),
			);
		}

		/**
		 * Validate post type and return its configuration
		 *
		 * @since 0.1.0
		 * @param string $post_type Post type slug.
		 * @return ?array{ref_tax: string, ref_types: array<int, string>} Configuration or null.
		 */
		private function get_validated_config( string $post_type ): ?array {
			if ( empty( $post_type ) || ! post_type_supports( $post_type, 'gatherpress-references' ) ) {
				return null;
			}

			return $this->config_manager->get_config( $post_type );
		}

		/**
		 * Resolve reference term ID, auto-detecting from archive context
		 *
		 * @since 0.1.0
		 * @param int    $ref_term_id Current reference term ID.
		 * @param string $ref_tax     Reference taxonomy slug.
		 * @return int Resolved reference term ID.
		 */
		private function resolve_ref_term_id( int $ref_term_id, string $ref_tax ): int {
			if ( $ref_term_id > 0 || ! is_tax( $ref_tax ) ) {
				return $ref_term_id;
			}

			$term = get_queried_object();

			return ( $term instanceof \WP_Term ) ? $term->term_id : 0;
		}

		/**
		 * Get type labels for taxonomies
		 *
		 * @since 0.1.0
		 * @param array<int, string> $taxonomies Taxonomy slugs.
		 * @return array<string, string> Type labels.
		 */
		private function get_type_labels( array $taxonomies ): array {
			$type_labels = array();

			foreach ( $taxonomies as $taxonomy_slug ) {
				$taxonomy = get_taxonomy( $taxonomy_slug );
				if ( $taxonomy ) {
					$_label                        = $taxonomy->labels->name ?? $taxonomy->name;
					$type_labels[ $taxonomy_slug ] = is_string( $_label ) ? $_label : $taxonomy_slug;
				}
			}

			/**
			 * Customize the human-readable labels for each reference type displayed in headings.
			 *
			 * @since 0.1.0
			 *
			 * @param array<string, string> $labels Array of taxonomy slug => label pairs.
			 * @return array<string, string> Filtered type labels.
			 *
			 * @example
			 * Override existing label
			 * ```php
			 * add_filter( 'gatherpress_references_type_labels', function( $labels ) {
			 *  $labels['_gatherpress-award'] = __( 'Prizes & Honours', 'textdomain' );
			 *  return $labels;
			 * } );
			 * ```
			 *
			 * @example
			 * Locale-specific labels
			 * ```php
			 * add_filter( 'gatherpress_references_type_labels', function( $labels ) {
			 *     $locale = get_locale();
			 *
			 *     if ( $locale === 'de_DE' ) {
			 *         $labels['_gatherpress-client'] = 'Gastspiele & Kunden';
			 *         $labels['_gatherpress-festival'] = 'Festivals';
			 *         $labels['_gatherpress-award'] = 'Auszeichnungen';
			 *     }
			 *
			 *     return $labels;
			 * } );
			 * ```
			 */
			return apply_filters( 'gatherpress_references_type_labels', $type_labels );
		}

		/**
		 * Get references data (from cache or query)
		 *
		 * @since 0.1.0
		 * @param string $post_type   Post type slug.
		 * @param int    $ref_term_id Reference term ID.
		 * @param int    $year        Year filter.
		 * @param string $type        Type filter.
		 * @return array<string, array<string, array<int, string>>> References data.
		 */
		private function get_references_data( string $post_type, int $ref_term_id, int $year, string $type ): array {
			// Try cache first.
			$cache_key  = $this->cache_manager->get_cache_key( $post_type, $ref_term_id, $year, $type );
			$references = $this->cache_manager->get( $cache_key );

			if ( false !== $references ) {
				return $references;
			}

			// Build and execute query.
			$args  = $this->query_builder->build_args( $post_type, $ref_term_id, $year, $type );
			$query = new \WP_Query( $args );

			// Organize results.
			$references = $this->data_organizer->organize_results( $post_type, $query, $type );

			// Cache if we have data.
			if ( ! empty( $references ) ) {
				$this->cache_manager->set( $cache_key, $references );
			}

			return $references;
		}

		/**
		 * Apply custom type order to references data
		 *
		 * @since 0.1.0
		 * @param array<string, array<string, array<int, string>>> $references References data.
		 * @param array<string>                                    $type_order Custom type order.
		 * @return array<string, array<string, array<int, string>>> Reordered references data.
		 */
		private function apply_type_order( array $references, array $type_order ): array {
			if ( empty( $type_order ) ) {
				return $references;
			}

			$reordered = array();

			foreach ( $references as $year => $types ) {
				$reordered_types = array();

				// First, add types in the specified order.
				foreach ( $type_order as $type_slug ) {
					if ( isset( $types[ $type_slug ] ) ) {
						$reordered_types[ $type_slug ] = $types[ $type_slug ];
					}
				}

				// Then, add any remaining types that weren't in the order.
				foreach ( $types as $type_slug => $items ) {
					if ( ! isset( $reordered_types[ $type_slug ] ) ) {
						$reordered_types[ $type_slug ] = $items;
					}
				}

				$reordered[ $year ] = $reordered_types;
			}

			return $reordered;
		}

		/**
		 * Generate HTML output
		 *
		 * @since 0.1.0
		 * @param array<string, array<string, array<int, string>>> $references            References data.
		 * @param string                                           $type                  Type filter.
		 * @param array<string, string>                            $type_labels           Type labels.
		 * @param int                                              $heading_level         Primary heading level.
		 * @param int                                              $secondary_heading_level Secondary heading level.
		 * @return string HTML output.
		 */
		private function generate_html(
			array $references,
			string $type,
			array $type_labels,
			int $heading_level,
			int $secondary_heading_level
		): string {
			$show_type_headings = ( $type === 'all' );

			ob_start();
			?>
			<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is escaped internally. ?>>
				<?php foreach ( $references as $ref_year => $types ) { ?>
					<h<?php echo esc_attr( (string) $heading_level ); ?> class="wp-block-heading references-year"><?php echo esc_html( $ref_year ); ?></h<?php echo esc_attr( (string) $heading_level ); ?>>

					<?php
					$this->render_type_sections(
						$types,
						$type_labels,
						$show_type_headings,
						$secondary_heading_level
					);
					?>
				<?php } ?>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		/**
		 * Render type sections for a single year
		 *
		 * @since 0.1.0
		 * @param array<string, array<int, string>> $types                   Types with items.
		 * @param array<string, string>             $type_labels             Type labels.
		 * @param bool                              $show_type_headings      Whether to show type headings.
		 * @param int                               $secondary_heading_level Heading level for type headings.
		 * @return void
		 */
		private function render_type_sections(
			array $types,
			array $type_labels,
			bool $show_type_headings,
			int $secondary_heading_level
		): void {
			foreach ( $types as $ref_type => $items ) {
				if ( empty( $items ) ) {
					continue;
				}

				if ( $show_type_headings ) {
					?>
					<h<?php echo esc_attr( (string) $secondary_heading_level ); ?> class="wp-block-heading references-type"><?php echo esc_html( $type_labels[ $ref_type ] ); ?></h<?php echo esc_attr( (string) $secondary_heading_level ); ?>>
					<?php
				}
				?>
				<ul class="wp-block-list references-list">
					<?php foreach ( $items as $item ) { ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php } ?>
				</ul>
				<?php
			}
		}
	}
}

/**
 * Extract and sanitize block attributes.
 *
 * @var array{
 *   postType?: string,
 *   refTermId?: int,
 *   year?: int,
 *   referenceType?: string,
 *   headingLevel?: int,
 *   yearSortOrder?: string,
 *   typeOrder?: string[],
 * } $attributes
 */
$gatherpress_references_renderer = Block_Renderer::get_instance();
echo $gatherpress_references_renderer->render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped within the render method.
