# Product Context: WP Resource Hub

## Why This Exists

Organizations and content creators struggle with managing diverse content types in WordPress. The default post/page system doesn't accommodate videos, PDFs, downloads, and external links in a unified, organized manner. This plugin solves that by creating a specialized content management system within WordPress.

## Problems It Solves

### 1. Content Organization Chaos

**Problem**: Mixed content types scattered across posts, pages, and media library
**Solution**: Centralized resource hub with specialized handling for each content type

### 2. Limited Access Control

**Problem**: WordPress lacks granular content access control out of the box
**Solution**: Per-resource access levels (public, logged-in users, specific roles)

### 3. No Engagement Analytics

**Problem**: Can't track how users interact with downloads and resources
**Solution**: Built-in statistics tracking for views and downloads

### 4. Poor Resource Discovery

**Problem**: Users can't easily find related content
**Solution**: Collections, taxonomies (type, topic, audience), and filtering

### 5. Inflexible Display Options

**Problem**: Limited ways to present resources on the frontend
**Solution**: Multiple display methods (templates, shortcodes, blocks)

## How It Should Work

### For Administrators

1. **Creating Resources**
   - Select resource type first (video, PDF, download, external link, internal content)
   - Fill type-specific fields that appear dynamically
   - Categorize with taxonomies (type, topic, audience)
   - Set access control level
   - Publish and track engagement

2. **Organizing Content**
   - Create collections to group related resources
   - Use bulk actions for efficient management
   - Filter/sort by type, topic, audience
   - Export data for backup or migration

3. **Monitoring Engagement**
   - View statistics on dashboard widget
   - See views/downloads per resource in list table
   - Access detailed stats reports

### For Content Viewers

1. **Discovering Resources**
   - Browse resource archives with filters
   - View by type, topic, or audience
   - Search and discover related content

2. **Consuming Content**
   - Videos embedded directly in page
   - PDFs viewable or downloadable
   - Downloads tracked with secure URLs
   - External links open appropriately
   - Internal content with TOC and reading time

3. **Access-Appropriate Experience**
   - Public content available to all
   - Gated content prompts login
   - Clear messaging about access requirements

## User Experience Goals

### Clarity

- Clear visual distinction between resource types (icons, colors)
- Obvious calls-to-action (watch, download, read, visit)
- Transparent access requirements

### Efficiency

- Fast loading with minimal database queries
- Cached statistics calculations
- Optimized asset loading

### Flexibility

- Theme-agnostic design
- Template override capability
- Extensible through WordPress hooks
- Works with block editor and classic editor

### Accessibility

- Semantic HTML structure
- ARIA labels for screen readers
- Keyboard navigation support
- Color contrast compliance

## Key User Journeys

### Journey 1: Admin Creates Video Resource

1. Click "Add New" in Resources menu
2. Enter title
3. Select "Video" type - interface updates to show video fields
4. Paste YouTube URL - thumbnail auto-generates
5. Add description, categorize with taxonomies
6. Set access to "Logged-in users"
7. Publish - resource appears in library

### Journey 2: User Finds and Views Resource

1. Navigate to resource archive page
2. Filter by "Video" type and "Tutorial" topic
3. Click resource card
4. Watch embedded video
5. View is tracked in statistics
6. See related resources in sidebar

### Journey 3: Admin Creates Collection

1. Create new collection
2. Search and select resources to include
3. Set display layout (grid/list)
4. Publish collection
5. Embed in page via shortcode or block
6. Collection displays on frontend with styled layout

## Success Metrics

- Time to create new resource < 2 minutes
- Page load time < 1 second for resource display
- Zero errors in statistics tracking
- 100% theme compatibility
- Positive user feedback on organization/discoverability
