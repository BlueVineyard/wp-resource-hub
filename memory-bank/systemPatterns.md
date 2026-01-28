# System Patterns: WP Resource Hub

## Architecture Overview

The plugin follows a modular, object-oriented architecture with clear separation of concerns. It uses WordPress best practices including singleton pattern, hooks/filters, and namespacing.

```
wp-resource-hub/
├── wp-resource-hub.php       # Bootstrap file
├── includes/                 # All PHP classes
│   ├── Plugin.php           # Core orchestrator
│   ├── Autoloader.php       # PSR-4 autoloader
│   ├── Helpers.php          # Utility functions
│   ├── PostTypes/           # CPT registration
│   ├── Taxonomies/          # Taxonomy registration
│   ├── Admin/               # Backend UI
│   ├── Frontend/            # Public-facing features
│   ├── Shortcodes/          # Shortcode handlers
│   ├── Blocks/              # Gutenberg blocks
│   ├── Hooks/               # Actions & filters
│   ├── Stats/               # Analytics tracking
│   ├── AccessControl/       # Permission system
│   └── ImportExport/        # Data portability
├── assets/                  # CSS/JS files
├── templates/               # Frontend templates
└── languages/               # Translation files
```

## Core Design Patterns

### 1. Singleton Pattern

All major classes use singleton pattern to ensure single instance:

```php
class ClassName {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize
    }
}
```

**Rationale**: Prevents duplicate initialization, provides global access point

### 2. Plugin Class as Orchestrator

`Plugin.php` serves as central coordinator that initializes all components in proper order:

1. Load text domain
2. Initialize hooks/filters first
3. Register post types and taxonomies
4. Initialize admin components (if is_admin)
5. Initialize frontend components
6. Initialize specialized features (stats, access control, etc.)
7. Register activation/deactivation hooks

### 3. Dependency Injection via WordPress Hooks

Components communicate via WordPress action/filter hooks rather than direct coupling:

```php
// Component registers action
do_action('wprh_resource_viewed', $resource_id);

// Another component listens
add_action('wprh_resource_viewed', [$this, 'track_view']);
```

**Benefit**: Loose coupling, extensibility, follows WordPress patterns

### 4. Namespace Organization

All classes use `WPResourceHub` namespace with subnamespaces:

- `WPResourceHub\PostTypes\*`
- `WPResourceHub\Admin\*`
- `WPResourceHub\Frontend\*`
- etc.

### 5. Autoloading (PSR-4 Compatible)

Custom autoloader maps namespaces to file paths:

```php
WPResourceHub\PostTypes\ResourcePostType
→ includes/PostTypes/ResourcePostType.php
```

## Component Relationships

### Core Components

**Plugin** ↔ Central orchestrator
├─ **ResourcePostType** - Registers 'resource' CPT
├─ **CollectionPostType** - Registers 'collection' CPT
├─ **Taxonomies** (ResourceTypeTax, ResourceTopicTax, ResourceAudienceTax)
├─ **Admin Components** (only loaded if is_admin())
│ ├─ ResourceAdminUI - Menu structure
│ ├─ MetaBoxes - Resource editing fields
│ ├─ CollectionMetaBoxes - Collection editing
│ ├─ SettingsPage - Plugin settings
│ ├─ ListTableEnhancements - List table columns/filters
│ ├─ BulkActions - Batch operations
│ └─ ImportExportPage - Data transfer UI
├─ **Frontend Components**
│ ├─ TemplateLoader - Template system
│ └─ SingleRenderer - Type-specific rendering
├─ **Shortcodes** (ResourcesShortcode, ResourceShortcode, CollectionShortcode)
├─ **BlocksManager** - Gutenberg blocks
├─ **Hooks** (Actions, Filters) - WordPress integration
├─ **StatsManager** - Analytics engine
├─ **DownloadTracker** - Secure download handling
├─ **AccessManager** - Permission system
└─ **Import/Export** (Importer, Exporter) - Data portability

### Key Interactions

1. **Resource Creation Flow**

   ```
   Admin saves post
   → MetaBoxes::save_meta()
   → Validates and saves meta fields
   → Triggers 'wprh_resource_saved' action
   → StatsManager initializes tracking
   ```

2. **Resource Display Flow**

   ```
   User views resource
   → TemplateLoader::template_loader()
   → Loads single-resource.php template
   → SingleRenderer::render()
   → Type-specific rendering method
   → StatsManager::track_view()
   → Hooks/Actions::render_resource_meta()
   ```

3. **Download Tracking Flow**

   ```
   User clicks download link
   → Custom rewrite rule: /resource-download/{id}
   → DownloadTracker::handle_download()
   → AccessManager::can_access() checks permission
   → StatsManager::record_download()
   → File served with proper headers
   ```

4. **Collection Display Flow**
   ```
   Shortcode/Block renders
   → CollectionShortcode::render()
   → Gets collection resources
   → Loops through resource IDs
   → Renders each via render_resource_item()
   → Applies layout (grid/list)
   ```

## Data Model

### Custom Post Types

**Resource (resource)**

- Post meta: Type-specific fields stored with `_wprh_` prefix
- Taxonomies: resource_type, resource_topic, resource_audience
- Meta fields vary by type (see Meta Fields Schema below)

**Collection (collection)**

- Post meta: `_wprh_resources` (serialized array of resource IDs)
- Post meta: `_wprh_layout` (grid/list)
- No taxonomies

### Meta Fields Schema

**Video Type**

- `_wprh_video_provider` (youtube/vimeo/local)
- `_wprh_video_url` (original URL)
- `_wprh_video_id` (extracted ID)
- `_wprh_video_duration` (optional)

**PDF Type**

- `_wprh_pdf_file` (attachment ID)
- `_wprh_pdf_allow_download` (yes/no)

**Download Type**

- `_wprh_download_file` (attachment ID)
- `_wprh_download_button_text` (custom text)

**External Link Type**

- `_wprh_external_url` (URL)
- `_wprh_external_open_new_tab` (yes/no)

**Internal Content Type**

- `_wprh_enable_toc` (yes/no)
- `_wprh_reading_time` (auto-calculated minutes)

**All Types**

- `_wprh_access_level` (public/logged_in/roles)
- `_wprh_allowed_roles` (serialized array)
- `_wprh_featured` (yes/no)

### Database Tables

**Custom Table: wp_wprh_stats**

```sql
id (bigint)
resource_id (bigint)
stat_type (varchar) - 'view' or 'download'
user_id (bigint) - 0 for guests
ip_address (varchar)
user_agent (varchar)
created_at (datetime)

INDEX: resource_id, stat_type, created_at
```

## Template System

### Template Hierarchy

Plugin looks for templates in this order:

1. **Theme override**: `{theme}/wp-resource-hub/{template}.php`
2. **Plugin default**: `{plugin}/templates/{template}.php`

Available templates:

- `single-resource.php` - Single resource display
- `single-resource-internal-content.php` - Internal content variant
- `archive-resource.php` - Resource archive listing

### Template Functions

Templates have access to:

- Standard WordPress template tags
- `$post` object with resource data
- Resource-specific helpers via `Helpers` class

## Extension Points

### Action Hooks

```php
'wprh_activated'              // After plugin activation
'wprh_loaded'                 // After plugin initialized
'wprh_register_taxonomies'    // Add custom taxonomies
'wprh_resource_saved'         // After resource saved
```

### Filter Hooks

```php
'wprh_post_type_args'         // Modify CPT registration
'wprh_resource_type_args'     // Modify taxonomy args
'wprh_template_path'          // Override template location
'wprh_default_resource_types' // Add/modify resource types
'wprh_access_levels'          // Custom access levels
```

## Security Measures

1. **Nonce Verification**: All form submissions verify nonces
2. **Capability Checks**: Admin actions check `edit_posts` or similar
3. **Data Sanitization**: All inputs sanitized before saving
4. **Output Escaping**: All outputs escaped (esc_html, esc_attr, esc_url)
5. **Direct Access Prevention**: `defined('ABSPATH')` check in all files
6. **Download Protection**: Files served through PHP with access checks
7. **SQL Injection Prevention**: Uses $wpdb->prepare() for queries

## Performance Considerations

1. **Lazy Loading**: Admin components only loaded when `is_admin()`
2. **Singleton Pattern**: Single instance prevents re-initialization
3. **Conditional Asset Loading**: JS/CSS only loaded when needed
4. **Database Indexes**: Stats table indexed on key fields
5. **Query Optimization**: Uses WordPress query caching
6. **Transient Caching**: For expensive operations (future enhancement)

## WordPress Integration

### REST API

- Custom post types registered with `show_in_rest => true`
- Custom REST fields added for resource metadata
- Enables Gutenberg block editor support

### Gutenberg Blocks

- Custom block category: "Resources"
- Three blocks: Resources Grid, Single Resource, Collection
- Server-side rendering for dynamic content
- Editor preview with live data

### i18n (Internationalization)

- Text domain: `wp-resource-hub`
- All strings wrapped in translation functions
- POT file generated: `languages/wp-resource-hub.pot`
- Ready for translation via WordPress.org or custom tools
