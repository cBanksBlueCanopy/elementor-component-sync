# Elementor Component Sync

A WordPress plugin that enables exporting and importing Elementor v4 components from the Elementor Component Library between WordPress sites.

## Features

- **Export Elementor Components**: Export individual components from your Elementor library as JSON files
- **Library Export**: Export multiple components at once as a component library
- **Component Import**: Import components from JSON files exported from other sites
- **Library Import**: Import entire component libraries into your Elementor component library
- **Preview**: Preview component details before importing
- **Direct Library Integration**: Imported components are added directly to your Elementor component library
- **CSS Preservation**: Exports and imports include custom CSS and Elementor CSS
- **Metadata Preservation**: Preserves component settings, categories, tags, and all metadata
- **Category & Tags Support**: Components maintain their categories and tags during import

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Elementor 4.0 or higher

## Installation

### Via WordPress Admin

1. Download the plugin files
2. Zip the folder as `elementor-component-sync.zip`
3. Go to WordPress Admin → Plugins → Add New
4. Click "Upload Plugin" and select the zip file
5. Click "Install Now"
6. Click "Activate Plugin"

### Manual Installation

1. Upload the `elementor-component-sync` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "Elementor Component Sync" and click "Activate"

## Usage

### Exporting Components

1. Go to WordPress Admin → Component Sync
2. Click the "Export" tab
3. Choose export type:
   - **Single Component**: Export one component from your Elementor library
   - **Multiple Components**: Export several components as a library
4. Select the component(s) to export from your Elementor component library
5. Click "Export Component" or "Export Library"
6. A JSON file will be downloaded to your computer

### Importing Components

1. Go to WordPress Admin → Component Sync
2. Click the "Import" tab
3. Click "Select File to Import" or drag and drop a JSON file exported from another site
4. Review the preview (optional) to see which components will be imported
5. Click "Import Component"
6. The component(s) will be imported directly into your Elementor component library
7. Components will be available immediately in your Elementor editor's components panel
8. Navigate to Elementor Library → Components to view and manage imported components

## Plugin Structure

```
elementor-component-sync/
├── elementor-component-sync.php      # Main plugin file
├── includes/
│   ├── class-exporter.php           # Export functionality
│   ├── class-importer.php           # Import functionality
│   └── class-admin-page.php         # Admin interface
├── assets/
│   ├── css/
│   │   └── admin.css                # Admin styles
│   └── js/
│       └── admin.js                 # Admin scripts
├── languages/                        # Translation files
└── README.md                         # This file
```

## How It Works

### Export Process

1. Plugin retrieves post data and Elementor metadata
2. Collects Elementor element data (JSON format)
3. Includes custom CSS and element CSS
4. Packages everything into a JSON file
5. User downloads the file

### Import Process

1. User uploads a JSON file
2. Plugin validates the file structure
3. Creates a new post with imported data
4. Saves Elementor element data
5. Preserves CSS and metadata
6. Clears Elementor cache
7. Post is ready for editing and publishing

## Technical Details

### Data Structure

The exported JSON file contains:

```json
{
  "version": "4.0.0",
  "export_type": "component",
  "exported_at": "2024-01-15 10:30:00",
  "site_url": "https://example.com",
  "component_data": {
    "id": 123,
    "title": "Component Name",
    "slug": "component-name",
    "description": "Component description",
    "status": "publish",
    "categories": ["Header", "Section"],
    "tags": [],
    "template_type": "component",
    "is_component": true
  },
  "elementor_data": [/* Elementor element structure */],
  "elementor_css": "/* Generated CSS */",
  "custom_css": "/* Custom CSS if any */",
  "meta_data": {
    "_elementor_edit_mode": "builder",
    "_elementor_template_type": "component"
  }
}
```

### AJAX Endpoints

- **Export**: `wp-ajax.php?action=ecs_export_component`
- **Import**: `wp-ajax.php?action=ecs_import_component`

Both endpoints require:
- Valid nonce token
- Administrator capabilities

## Important Notes

1. **Library Integration**: Imported components are automatically added to your Elementor component library and available in the Elementor editor.

2. **Component Categories & Tags**: The plugin preserves categories and tags from the exported components. These will be automatically assigned during import if the taxonomies exist.

3. **External Resources**: External images and resources maintain their original URLs. Ensure image URLs are accessible on the destination site.

4. **Dependencies**: Custom fonts, icons, and add-ons referenced in components should be installed on the destination site for full compatibility.

5. **Cache Clearing**: The plugin automatically clears Elementor's CSS cache after import.

6. **Site-Specific Settings**: Some component settings may reference the original site's data (e.g., internal links, post queries). You may need to adjust these in the Elementor editor after import.

## Compatibility

- **Elementor**: Tested with Elementor 4.0+
- **WordPress**: Compatible with WordPress 5.0+
- **PHP**: Compatible with PHP 7.4+
- **Browsers**: Chrome, Firefox, Safari, Edge (latest versions)

## Troubleshooting

### Import fails with "Invalid JSON file"
- Ensure you're using a file exported from this plugin
- Check that the file extension is `.json`
- Verify the file hasn't been corrupted

### Imported component has missing styles
- Check if required Elementor add-ons are installed
- Verify custom fonts/icons are available
- Ensure CSS was properly exported

### Imported component shows broken images
- Check that external image URLs are accessible
- Consider downloading images and uploading to the new site
- Update image URLs in the Elementor editor

### Permission denied error
- Ensure you're logged in as an administrator
- Check that `manage_options` capability is enabled

## Security

- All AJAX requests require valid nonce tokens
- Only administrators can export/import components
- File uploads are validated as JSON
- User input is sanitized throughout

## Performance

- File size depends on component complexity
- Typical component JSON: 50KB - 500KB
- Large libraries may take longer to import
- Consider importing during low-traffic periods

## Limitations

- **Site URL Changes**: If importing to a site with a different URL, you may need to update internal links in the component
- **Custom Code**: Components using custom HTML/JavaScript with site-specific references may need adjustments
- **Dynamic Content**: Components using dynamic content (post queries, user data) may need reconfiguration for the destination site
- **External Dependencies**: Custom fonts, third-party icons, and Elementor add-ons must be installed on the destination site
- **Elementor Version**: Ensure both sites are using compatible Elementor v4 versions for best results

## Support

For issues, feature requests, or contributions:

1. Check the Help tab in the plugin admin page
2. Review this README file
3. Ensure all requirements are met
4. Check Elementor documentation

## License

This plugin is licensed under the GPL v2 or later. See LICENSE file for details.

## Changelog

### Version 1.0.0
- Initial release
- Single component export/import
- Library export/import
- Admin interface with preview
- CSS and metadata preservation

## Contributing

Contributions are welcome! Please ensure:
- Code follows WordPress coding standards
- All functions are properly documented
- Security practices are followed
- Backward compatibility is maintained
