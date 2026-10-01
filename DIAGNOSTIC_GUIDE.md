# Elementor Component Sync - Diagnostic Guide

If the plugin isn't recognizing your Elementor v4 components, use this guide to diagnose the issue.

## Step 1: Access the Diagnostic Tab

1. Go to **WordPress Admin → Component Sync**
2. Click the **"Diagnostic"** tab
3. Review the information displayed

## What to Look For

### Section 1: Available Components
This shows what the plugin has detected as importable components.

- **If components are listed**: Great! You can use the Export tab
- **If nothing is listed**: Continue to Section 2

### Section 2: Elementor Library Items
This shows all items in the `elementor_library` post type.

**What to check:**
- Are any items listed? If yes, look at the "Template Type" column
- What values do you see in the Template Type column?
  - `component` = Component (standard)
  - `kit` = Global Kit
  - `block` = Reusable Block
  - `section` = Section
  - blank/empty = No type set

### Section 3: All Posts with Elementor Data
This shows ALL pages, posts, and library items that have Elementor content.

**What to look for:**
- Look at the Post Type column
- Common post types you might see:
  - `page` = Regular WordPress page
  - `post` = Blog post
  - `elementor_library` = Elementor library item

## Common Scenarios & Solutions

### Scenario 1: Components Listed Under "Available Components"
**Status:** ✓ Working! You can use the plugin normally.

### Scenario 2: No Items in "Elementor Library Items"
**Issue:** Elementor library post type doesn't exist or has no items.

**Solutions:**
1. Make sure Elementor is properly activated
2. Go to Elementor Dashboard → Settings → Disable Advanced Features, then re-enable
3. Check that you've actually created components in Elementor

### Scenario 3: Items in "Elementor Library" but No "Template Type" Value
**Issue:** Components aren't properly labeled as components.

**What this means:**
- Your library items exist but aren't tagged as components
- They might be pages/posts with Elementor data

**Solution:**
- Edit each library item in Elementor
- Save it again with Elementor
- This should update the template type

### Scenario 4: Many Items in "All Posts with Elementor Data" But Nothing in "Elementor Library"
**Issue:** Your Elementor content is stored as pages/posts, not in the library.

**What this means:**
- You built Elementor content on regular pages/posts
- Not using Elementor's component library feature

**Solution:**
- The plugin can still export these, but you'll be exporting pages, not components
- Or, create actual components in Elementor Library

## How Elementor v4 Stores Components

### Library Post Type
Elementor uses the `elementor_library` post type for:
- Components
- Global Kits
- Blocks
- Templates

### Component Detection
The plugin looks for:
1. Post type = `elementor_library`
2. Has `_elementor_data` meta (the actual design data)
3. `_elementor_template_type` meta = `component`

### Where Data is Stored
- **Post content:** `wp_posts` table
- **Element data:** `wp_postmeta` table with key `_elementor_data`
- **Template type:** `wp_postmeta` table with key `_elementor_template_type`

## Data Structure in Database

### If storing components correctly:
```
wp_posts:
- ID: 123
- post_type: elementor_library
- post_title: "My Button Component"
- post_status: publish

wp_postmeta:
- post_id: 123, meta_key: _elementor_template_type, meta_value: component
- post_id: 123, meta_key: _elementor_data, meta_value: {...JSON...}
```

### If storing in pages:
```
wp_posts:
- ID: 456
- post_type: page
- post_title: "My Page with Elementor"
- post_status: publish

wp_postmeta:
- post_id: 456, meta_key: _elementor_data, meta_value: {...JSON...}
- post_id: 456, meta_key: _elementor_template_type, meta_value: (empty)
```

## Troubleshooting by Post Type

### Post Type: `elementor_library`
- These are in Elementor's library
- Check the Template Type value
- If Template Type = `component`, they should appear in Available Components

### Post Type: `page` or `post`
- These are regular WordPress content
- They have Elementor designs but aren't components
- The plugin can export them as a fallback
- Consider saving them as library items in Elementor

## What to Report

If you need help, share:
1. A screenshot of the Diagnostic tab
2. How many items appear in each section
3. The Template Type values you see
4. Whether you see `elementor_library` post types

## Next Steps

Once you've identified your components:

### If Components Found:
1. Go to the **Export** tab
2. Select your component
3. Click **Export Component**

### If Components Not Found:
1. Create a component in Elementor:
   - Go to Elementor Library
   - Click "Add New"
   - Choose "Component"
   - Design it
   - Save/Publish
2. Return to Component Sync → Diagnostic
3. Check if it now appears

### If Still Not Found:
1. Check the "All Posts with Elementor Data" section
2. Copy the IDs of items you want to export
3. Manually update them to component type:

```php
// In WordPress admin, run via plugin or theme:
update_post_meta( 123, '_elementor_template_type', 'component' );
```

## Advanced: Manual Database Check

If you have database access, check:

```sql
-- Find all elementor_library items
SELECT ID, post_title, post_status 
FROM wp_posts 
WHERE post_type = 'elementor_library' 
LIMIT 20;

-- Check template types
SELECT pm.post_id, pm.meta_value, p.post_title
FROM wp_postmeta pm
JOIN wp_posts p ON p.ID = pm.post_id
WHERE pm.meta_key = '_elementor_template_type'
LIMIT 20;

-- Find all posts with Elementor data
SELECT ID, post_title, post_type
FROM wp_posts
WHERE ID IN (
  SELECT post_id FROM wp_postmeta 
  WHERE meta_key = '_elementor_data'
)
LIMIT 50;
```

## Support

If you still can't identify your components:
1. Keep the Diagnostic information
2. Screenshot the tables
3. Note your Elementor version
4. This will help identify the storage method Elementor v4 is using on your site
