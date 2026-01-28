# Technical Context: WP Resource Hub

## Technology Stack

### Core Technologies

- **PHP**: 7.4+ (OOP, namespaces, singleton pattern)
- **WordPress**: 5.8+ (CPT, taxonomies, hooks, REST API)
- **JavaScript**: ES5/ES6 (jQuery, Vanilla JS for blocks)
- **CSS**: Custom stylesheets for admin and frontend
- **SQL**: MySQL via $wpdb (custom stats table)

### WordPress APIs Used

- **Custom Post Types API**: register_post_type()
- **Taxonomy API**: register_taxonomy()
- **Settings API**: register_setting(), add_settings_section()
- **Meta Box API**: add_meta_box()
- **Rewrite API**: add_rewrite_rule() for download tracking
- **Template API**: locate_template(), get_template_part()
- **REST API**: register_rest_field()
- **Block Editor API**: register_block_type()
- **Options API**: get_option(), update_option()
- **Transients API**: get_transient(), set_transient()
- **Database API**: $wpdb->prepare(), $wpdb->insert()

## Development Environment Setup

### Requirements

```
PHP 7.4 or higher
WordPress 5.8 or higher
MySQL 5.6 or higher
Modern browser (for block editor)
```

### Installation

1. Clone repository to `wp-content/plugins/wp-resource-hub/`
2. Activate plugin via WordPress admin
3. Plugin auto-creates stats table on activation
4. Default terms created for taxonomies
5. Rewrite rules flushed automatically

### File Structure

```
wp-resource-hub/
├── wp-resource-hub.php          # Main plugin file (bootstrap)
├── includes/
│   ├── Autoloader.php           # PSR-4 autoloader
│   ├── Plugin.php               # Core orchestrator class
│   ├── Helpers.php              # Utility functions
│   ├── PostTypes/
│   │   ├── ResourcePostType.php
│   │   └── CollectionPostType.php
│   ├── Taxonomies/
│   │   ├── ResourceTypeTax.php
│   │   ├── ResourceTopicTax.php
│   │   └── ResourceAudienceTax.php
│   ├── Admin/
│   │   ├── ResourceAdminUI.php
│   │   ├── MetaBoxes.php
│   │   ├── CollectionMetaBoxes.php
│   │   ├── SettingsPage.php
│   │   ├── ListTableEnhancements.php
│   │   ├── BulkActions.php
│   │   └── ImportExportPage.php
│   ├── Frontend/
│   │   ├── TemplateLoader.php
│   │   └── SingleRenderer.php
│   ├── Shortcodes/
│   │   ├── ResourcesShortcode.php
│   │   ├── ResourceShortcode.php
│   │   └── CollectionShortcode.php
│   ├── Blocks/
│   │   └── BlocksManager.php
│   ├── Hooks/
│   │   ├── Actions.php
│   │   └── Filters.php
│   ├── Stats/
│   │   ├── StatsManager.php
│   │   └── DownloadTracker.php
│   ├── AccessControl/
│   │   └── AccessManager.php
│   └── ImportExport/
│       ├── Importer.php
│       └── Exporter.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── collection-admin.css
│   │   ├── frontend.css
│   │   └── blocks-editor.css
│   └── js/
│       ├── admin.js
│       ├── collection-admin.js
│       ├── frontend.js
│       └── blocks-editor.js
├── templates/
│   ├── single-resource.php
│   ├── single-resource-internal-content.php
│   └── archive-resource.php
└── languages/
    └── wp-resource-hub.pot
```

## Key Dependencies

### External Dependencies

**None** - Plugin is self-contained, uses only WordPress core APIs

### WordPress Core Dependencies

- WordPress 5.8+ (for block editor support)
- PHP 7.4+ (for typed properties, arrow functions)
- MySQL 5.6+ (for InnoDB, proper indexing)

### Optional Integrations

- Compatible with popular themes (uses WordPress template hierarchy)
- Works with caching plugins (proper transient usage)
- Compatible with security plugins (follows WP security best practices)

## Configuration

### Constants Defined

```php
WPRH_VERSION           // Plugin version (1.0.0)
WPRH_PLUGIN_FILE       // Full path to main plugin file
WPRH_PLUGIN_DIR        // Plugin directory path
WPRH_PLUGIN_URL        // Plugin directory URL
WPRH_PLUGIN_BASENAME   // Plugin basename
WPRH_MIN_PHP_VERSION   // Minimum PHP version (7.4)
WPRH_MIN_WP_VERSION    // Minimum WordPress version (5.8)
```

### Settings Options

Stored as WordPress options:

**General Settings** (`wprh_general_settings`)

- `default_type`: Default resource type for new resources
- `default_ordering`: Default sort order (date/title/menu_order)
- `items_per_page`: Resources per page on archives

**Frontend Settings** (`wprh_frontend_settings`)

- `default_layout`: Default display layout (grid/list)
- `enable_filters`: Show taxonomy filters on archive
- `internal_enable_toc`: Auto-generate TOC for internal content
- `internal_enable_reading_time`: Show reading time estimate
- `internal_show_author`: Display author info

**Slug Settings**

- `wprh_resource_slug`: Custom slug for resource CPT (default: 'resource')

### Custom Database Table Schema

```sql
CREATE TABLE {prefix}_wprh_stats (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  resource_id bigint(20) UNSIGNED NOT NULL,
  stat_type varchar(20) NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  ip_address varchar(100) DEFAULT NULL,
  user_agent varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY resource_id (resource_id),
  KEY stat_type (stat_type),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Asset Management

### CSS Files

- **admin.css**: Admin list tables, settings pages
- **collection-admin.css**: Collection edit screen styles
- **frontend.css**: Public-facing resource displays
- **blocks-editor.css**: Block editor styles

### JavaScript Files

- **admin.js**: Admin meta box interactions, type switching
- **collection-admin.js**: Collection resource management (drag-drop, search)
- **frontend.js**: Frontend interactions, AJAX filtering
- **blocks-editor.js**: Block editor components, preview handling

### Asset Enqueuing

```php
// Admin assets - only on resource edit screens
add_action('admin_enqueue_scripts', 'enqueue_admin_assets');

// Frontend assets - only on resource pages
add_action('wp_enqueue_scripts', 'enqueue_frontend_assets');

// Block editor assets - only in editor context
add_action('enqueue_block_editor_assets', 'enqueue_block_assets');
```

## Testing Considerations

### Manual Testing Checklist

- [ ] Create each resource type successfully
- [ ] Collections display resources correctly
- [ ] Statistics track views and downloads
- [ ] Access control restricts content appropriately
- [ ] Import/export maintains data integrity
- [ ] Shortcodes render properly
- [ ] Blocks work in editor and frontend
- [ ] Templates respect theme styles
- [ ] Bulk actions work without errors
- [ ] Settings save correctly

### Known Browser Requirements

- Modern browsers (Chrome, Firefox, Safari, Edge)
- JavaScript enabled for admin features
- Cookie support for statistics tracking

## Deployment

### Plugin Activation Process

1. Version compatibility check (PHP 7.4+, WP 5.8+)
2. Autoloader registration
3. CPT and taxonomy registration
4. Stats table creation (if not exists)
5. Default taxonomy terms creation
6. Rewrite rules flush
7. Version number stored in options
8. Redirect to settings page (if single activation)

### Plugin Deactivation

1. Rewrite rules flush
2. No data deletion (safe deactivation)

### Uninstall Considerations

Plugin does not include uninstall.php, so data persists after deletion.
Future enhancement: Add uninstall.php to optionally remove:

- Custom post types data
- Taxonomy terms
- Options
- Stats table

## Performance Optimization

### Current Optimizations

1. **Lazy loading**: Admin components only load when is_admin()
2. **Selective asset loading**: CSS/JS only on relevant pages
3. **Database indexes**: Stats table indexed on query fields
4. **Singleton pattern**: Single instance per class
5. **WordPress object caching**: Uses wp_cache when available

### Potential Improvements

1. **Transient caching**: Cache expensive queries (top resources, stats summaries)
2. **AJAX pagination**: For large collections
3. **Image optimization**: Lazy load thumbnails
4. **Minification**: Minify CSS/JS for production
5. **CDN support**: For video thumbnails

## Security Implementation

### Input Validation

- All POST data sanitized via sanitize_text_field(), sanitize_url(), etc.
- Nonce verification on all form submissions
- Capability checks before any admin operations

### Output Escaping

- esc_html() for text content
- esc_attr() for HTML attributes
- esc_url() for URLs
- wp_kses_post() for rich content

### SQL Security

- All queries use $wpdb->prepare()
- No direct SQL concatenation
- Parameterized queries for user input

### File Security

- Direct access prevention in all PHP files
- File uploads through WordPress media library
- File serving through PHP with access checks
- No direct file path exposure

## Internationalization (i18n)

### Text Domain

`wp-resource-hub`

### Translation Functions Used

- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `_x()` - Translated string with context
- `_n()` - Singular/plural translations
- `esc_html__()` - Escaped translated string
- `esc_attr__()` - Escaped attribute translation

### Translation Files

- POT file: `languages/wp-resource-hub.pot`
- Ready for translation via:
  - WordPress.org GlotPress
  - Poedit
  - Loco Translate plugin

## Coding Standards

### WordPress Coding Standards

- Follows WordPress PHP Coding Standards
- PSR-4 autoloading structure
- Proper inline documentation (DocBlocks)
- Consistent naming conventions

### Code Organization Principles

- Single Responsibility: Each class has one purpose
- DRY (Don't Repeat Yourself): Helpers for common tasks
- Separation of Concerns: Admin/Frontend/Core separated
- Extensibility: Hooks and filters throughout

## Version Control

### Current Version

1.0.0 (stored in WPRH_VERSION constant and plugin header)

### Version Tracking

- Version stored in wp_options as 'wprh_version'
- Checked on plugin load for future migrations
- Updated on activation
