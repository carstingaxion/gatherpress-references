# Developer documentation

## Filter Hooks Reference

The plugin provides several filter hooks for customization, documented in [`docs/developer/hooks/Hooks.md`](hooks/Hooks.md)

## GatherPress Integration

The plugin extends GatherPress by registering taxonomies and using post type support for configuration:

```php
// Configure which taxonomies to use via post type support
$config = array(
    'ref_tax'   => 'gatherpress-production',  // The main reference taxonomy
    'ref_types' => array( '_gatherpress-client', '_gatherpress-festival', '_gatherpress-award' ),
);
add_post_type_support( 'gatherpress_event', 'gatherpress_references', $config );
```

## Block Attributes

```json
{
  "refTermId": {
    "type": "number",
    "default": 0
  },
  "year": {
    "type": "number",
    "default": 0
  },
  "referenceType": {
    "type": "string",
    "default": "all",
    "enum": ["all", "ref_client", "ref_festival", "ref_award"]
  },
  "headingLevel": {
    "type": "number",
    "default": 2
  },
  "yearSortOrder": {
    "type": "string",
    "default": "desc",
    "enum": ["asc", "desc"]
  }
}
```

## Caching Strategy

The plugin uses WordPress transients for performance:

```php
// Cache key based on filter parameters
$cache_key = 'gatherpress_refs_' . md5(serialize([
    $ref_term_id,
    $year,
    $type
]));

// Try to get cached data
$cached = get_transient($cache_key);
if (false !== $cached) {
    return $cached;
}

// ... perform query ...

// Cache results for 1 hour
set_transient($cache_key, $references, 3600);
```

**Cache Invalidation:**
Cache is automatically cleared when:
- Event posts are saved or deleted
- Taxonomy terms are edited or deleted
- Demo data is generated or deleted

## Data Organization

Results are organized and cached in a nested structure:

```php
[
    '2024' => [
        '_gatherpress-client' => ['Client 1', 'Client 2'],
        '_gatherpress-festival' => ['Festival 1'],
        '_gatherpress-award' => ['Award 1', 'Award 2']
    ],
    '2023' => [
        '_gatherpress-client' => ['Client 3'],
        '_gatherpress-festival' => [],
        '_gatherpress-award' => ['Award 3']
    ]
]
```


## Performance Considerations

1. **Batch Queries**: Use `get_post_dates()` and `get_post_terms()` to minimize database calls
2. **Field Limitation**: Query only post IDs with `'fields' => 'ids'`
3. **Cache Management**: Automatic transient caching with smart invalidation
4. **No Found Rows**: Set `'no_found_rows' => true` to skip pagination count
5. **Term Cache**: Enable term caching with `'update_post_term_cache' => true`

## Theme.json Integration

The block fully supports WordPress theme.json:

```json
{
    "settings": {
        "blocks": {
            "gatherpress/references": {
                "color": {
                    "palette": [
                        {
                            "name": "Primary",
                            "slug": "primary",
                            "color": "#your-color"
                        }
                    ]
                },
                "typography": {
                    "fontSizes": [
                        {
                            "name": "Year Heading",
                            "slug": "year-heading",
                            "size": "2.5rem"
                        }
                    ]
                },
                "spacing": {
                    "padding": true,
                    "margin": true,
                    "blockGap": true
                }
            }
        }
    }
}
```

## Testing

GatherPress References uses [PHPUnit](https://phpunit.de/) with the [WordPress PHPUnit test framework](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/) for automated testing. Tests are split into two suites:

- **Unit tests** — Fast, isolated tests that do NOT load WordPress. Test individual class logic.
- **Integration tests** — Tests that run inside the full WordPress environment via [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).

### Prerequisites

1. **Docker** — Required for `wp-env`. Install from [docker.com](https://www.docker.com/get-started/).
2. **Node.js** — Required for `wp-env` and build tooling.
3. **Composer** — Required for PHPUnit polyfills.

### Setup

```bash
# 1. Install Node dependencies (includes wp-env via @wordpress/scripts)
npm install

# 2. Install Composer dependencies (PHPUnit polyfills)
composer install

# 3. Start the wp-env environment
npx wp-env start
```

### Running Tests

#### Unit Tests

Unit tests run without WordPress and are fast. They test individual classes in isolation with stubbed WordPress functions.

```bash
npm run test:php:unit
```

#### Integration Tests

Integration tests require `wp-env` to be running. They execute inside the WordPress test container with the full WordPress environment and GatherPress plugin loaded.

```bash
# Start wp-env first (if not already running)
npx wp-env start

# Run integration tests
npm run test:php:integration
```

#### Run All PHP Tests (Unit + Integration)

```bash
npm run test:php
```

> **Note:** A single `phpunit.xml.dist` defines both suites. All tests extend `WP_UnitTestCase` and run within the full WordPress test environment. The shared `test/php/bootstrap.php` loads WordPress, GatherPress, and the plugin for both suites.

### Test Structure

```
test/
└── php/
    ├── bootstrap.php               # Shared bootstrap (auto-detects suite)
    ├── unit/
    │   ├── CacheManagerTest.php    # Cache key generation, retrieval
    │   ├── ConfigManagerTest.php   # Configuration validation
    │   └── DataOrganizerTest.php   # Year sorting, data organization
    └── integration/
        ├── BlockRendererTest.php       # Block rendering with real WordPress
        ├── CacheIntegrationTest.php    # Transient caching in WordPress
        ├── PluginActivationTest.php    # Plugin initialization, singletons
        ├── QueryBuilderTest.php        # WP_Query argument building
        └── TaxonomyRegistrationTest.php # Taxonomy registration verification
```

### Configuration Files

| File                 | Purpose                                                |
|----------------------|--------------------------------------------------------|
| `phpunit.xml.dist`   | Single PHPUnit config with `unit` and `integration` suites |
| `composer.json`      | Composer deps (PHPUnit polyfills)                      |
| `.wp-env.json`       | wp-env environment configuration                       |

### Writing New Tests

All tests extend `WP_UnitTestCase` and have full access to the WordPress environment. The distinction between unit and integration suites is organizational — unit tests focus on isolated class behavior, while integration tests verify cross-component interactions.

#### Unit Tests

```php
namespace GatherPress\References\Tests\Unit;

use WP_UnitTestCase;

class MyClassTest extends WP_UnitTestCase {
    public function test_something() {
        // WordPress API is available.
        $this->assertTrue( true );
    }
}
```

#### Integration Tests

```php
namespace GatherPress\References\Tests\Integration;

use WP_UnitTestCase;

class MyIntegrationTest extends WP_UnitTestCase {
    public function test_something_with_wordpress() {
        // Full WordPress API is available.
        $post_id = self::factory()->post->create();
        $this->assertGreaterThan( 0, $post_id );
    }
}
```

## JavaScript Tests (Jest)

The block's editor-side JavaScript is tested with [Jest](https://jestjs.io/) via `@wordpress/scripts`. Tests cover hooks, components, and utility functions.

### Running JavaScript Tests

```bash
# Run all JS tests
npm run test:js

# Run in watch mode (re-runs on file changes)
npm run test:js:watch

# Run with coverage report
npm run test:js:coverage
```

### JavaScript Test Structure

```
test/
└── js/
    ├── __mocks__/
    │   └── style-mock.js                 # Mock for CSS/SCSS imports
    ├── edit.test.js                      # Integration test for main Edit component
    ├── utils/
    │   └── placeholder-data.test.js      # Pure function tests
    ├── hooks/
    │   ├── use-config.test.js            # Config loading hook
    │   ├── use-type-order.test.js        # Type ordering logic
    │   └── use-block-label.test.js       # Dynamic label generation
    └── components/
        ├── not-configured.test.js        # Error state rendering
        ├── reference-inspector.test.js   # Sidebar controls
        └── reference-preview.test.js     # Preview output & interactions
```

### Writing New JavaScript Tests

Tests live under `test/js/` mirroring the `src/` structure, importing source files via relative paths (e.g., `../../../src/hooks/use-config`). WordPress dependencies are mocked at the module level:

```js
jest.mock( '@wordpress/data', () => ( {
    useSelect: jest.fn(),
} ) );
```

For component tests, use `@testing-library/react` (included with `@wordpress/scripts`):

```js
import { render, screen } from '@testing-library/react';
import MyComponent from '../../../src/components/my-component';

it( 'renders correctly', () => {
    render( <MyComponent /> );
    expect( screen.getByText( 'Hello' ) ).toBeInTheDocument();
} );
```

---

### Troubleshooting

#### "WordPress test library not found"

Ensure `wp-env` is running:

```bash
npx wp-env start
```

#### "Composer autoloader not found"

Run Composer install:

```bash
composer install
```

#### Tests failing due to GatherPress not loaded

The integration bootstrap loads GatherPress from the `wp-env` plugins directory. Ensure `.wp-env.json` includes GatherPress and that the environment has been started:

```bash
npx wp-env start
npx wp-env run tests-cli wp plugin list
```

#### Resetting the test environment

```bash
npx wp-env destroy
npx wp-env start
```
