# Installation Guide - Elementor Component Sync

## Quick Setup (5 minutes)

### Step 1: Prepare the Plugin Files

1. Download all plugin files from this package
2. Create a folder named `elementor-component-sync`
3. Place all files inside this folder according to the structure below:

```
elementor-component-sync/
├── elementor-component-sync.php
├── includes/
│   ├── class-exporter.php
│   ├── class-importer.php
│   └── class-admin-page.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── README.md
└── INSTALLATION.md
```

### Step 2: Upload to Your Server

**Option A: Using WordPress Admin (Recommended)**

1. Go to **WordPress Admin Dashboard**
2. Navigate to **Plugins → Add New**
3. Click **"Upload Plugin"** button
4. Select the `elementor-component-sync.zip` file
5. Click **"Install Now"**
6. Click **"Activate Plugin"**

**Option B: Using FTP/SFTP**

1. Connect to your server via FTP
2. Navigate to `/wp-content/plugins/`
3. Upload the `elementor-component-sync` folder
4. Go to WordPress Admin Dashboard
5. Navigate to **Plugins**
6. Find "Elementor Component Sync"
7. Click **"Activate"**

**Option C: Using SSH/Command Line**

```bash
# Navigate to plugins directory
cd /home/username/public_html/wp-content/plugins/

# Download the plugin (replace with your source)
git clone https://your-repo/elementor-component-sync.git

# Or upload and extract
scp elementor-component-sync.zip user@host:/path/to/wp-content/plugins/
ssh user@host
cd /path/to/wp-content/plugins/
unzip elementor-component-sync.zip
```

### Step 3: Verify Installation

1. Go to **Plugins** in WordPress Admin
2. Look for "Elementor Component Sync"
3. Status should show "Activate"
4. Look in admin menu for **"Component Sync"** (should appear in left sidebar)

### Step 4: Check Dependencies

The plugin will automatically check for:
- ✓ Elementor (v4.0+) - Must be installed and activated
- ✓ WordPress (5.0+)
- ✓ PHP (7.4+)
- ✓ Elementor Component Library - Must be available

If Elementor is not active, you'll see an error message. Install and activate Elementor first.

**Note**: The plugin works with Elementor's Component Library (elementor_library post type). Make sure you have Elementor's library enabled.

## Updating the Plugin

### From Version 1.0.0 → 1.x.x

1. Go to **Plugins** → **Elementor Component Sync**
2. Click **"Deactivate"** (optional but recommended)
3. Upload the new files via FTP, replacing old ones
4. Activate the plugin again

No database migrations needed - the plugin uses post meta only.

## Removing the Plugin

### Deactivate and Delete

1. Go to **Plugins** in WordPress Admin
2. Find "Elementor Component Sync"
3. Click **"Deactivate"**
4. Click **"Delete"** → **"Delete"** to confirm

Or via FTP:
```bash
rm -rf /wp-content/plugins/elementor-component-sync/
```

**Note**: Deactivating does NOT delete exported/imported components. Those remain as draft posts.

## Post-Installation Setup

### 1. Verify Permissions

- Only administrators can export/import
- Check user role has `manage_options` capability

### 2. Create Test Component

1. Go to **Elementor Library** in WordPress Admin
2. Create a new library item as a **Component**
3. Design a simple component using Elementor
4. Publish the component
5. Go to **Component Sync → Export**
6. Select your test component and export it
7. Go to **Component Sync → Import**
8. Import the exported file
9. Verify it appears in your Elementor Library → Components

### 3. Backup First

Before using in production:
- Backup your WordPress database
- Test import/export with non-critical components first

## Troubleshooting Installation

### "Plugin could not be activated because it triggered a fatal error"

**Causes & Solutions:**
- **Elementor not active**: Install and activate Elementor first
- **PHP version too old**: Upgrade to PHP 7.4 or higher
- **File permissions**: Check file permissions (644 for files, 755 for folders)

**Solution:**
```bash
# Fix permissions via SSH
chmod -R 755 /wp-content/plugins/elementor-component-sync/
chmod -R 644 /wp-content/plugins/elementor-component-sync/*.php
chmod -R 644 /wp-content/plugins/elementor-component-sync/assets/*
```

### "Plugin not appearing in admin menu"

**Possible causes:**
- Plugin not activated
- Elementor not installed
- Cache issue

**Solutions:**
1. Clear WordPress cache (if using cache plugin)
2. Deactivate and reactivate plugin
3. Check wp-config.php for `WP_DEBUG`

### File Permission Errors

If you see permission errors, set correct permissions:

```bash
# Make plugin directory writable
chmod 755 /wp-content/plugins/elementor-component-sync/

# Make all files readable
chmod 644 /wp-content/plugins/elementor-component-sync/elementor-component-sync.php
chmod 644 /wp-content/plugins/elementor-component-sync/includes/*.php
chmod 644 /wp-content/plugins/elementor-component-sync/assets/css/*.css
chmod 644 /wp-content/plugins/elementor-component-sync/assets/js/*.js
```

### AJAX Errors ("Failed to export/import")

**Common causes:**
- Nonce verification failed (security issue)
- File upload limits
- PHP memory limit too low

**Solutions:**
1. Check `php.ini` settings:
   ```
   upload_max_filesize = 64M
   post_max_size = 64M
   memory_limit = 256M
   ```

2. Increase limits in `.htaccess` (if using Apache):
   ```apache
   php_value upload_max_filesize 64M
   php_value post_max_size 64M
   php_value memory_limit 256M
   ```

3. Restart PHP-FPM or Apache:
   ```bash
   sudo systemctl restart php-fpm
   # or
   sudo systemctl restart apache2
   ```

## Hosting Requirements

### Minimum Hosting Specs
- **PHP**: 7.4 or higher (PHP 8.0+ recommended)
- **MySQL**: 5.7 or higher
- **WordPress**: 5.0 or higher
- **Disk Space**: 10MB minimum
- **File Uploads**: 64MB+ allowed

### Recommended Hosting Specs
- **PHP**: 8.1+
- **MySQL**: 8.0
- **SSD Storage**
- **Upload limit**: 256MB+
- **SSL Certificate**: HTTPS enabled

### Known Compatible Hosts
- WP Engine
- Kinsta
- SiteGround
- Bluehost
- DreamHost
- A2 Hosting

## Security Considerations

1. **Keep WordPress Updated**: Always run latest WordPress version
2. **Keep Plugins Updated**: This plugin will receive updates
3. **Strong Admin Passwords**: Use strong, unique passwords for admin accounts
4. **Limited Exports**: Don't share exported files with untrusted parties
5. **File Verification**: Verify file integrity before importing

## Performance Notes

- Plugin adds minimal overhead
- Exports are done via AJAX (non-blocking)
- Imports clear Elementor cache automatically
- Large libraries (50+ components) may take 1-2 minutes to import

## Support & Documentation

- **Plugin Page**: See README.md
- **Help Tab**: Component Sync → Help in WordPress Admin
- **Documentation**: README.md in plugin folder

## Next Steps

1. ✓ Installation complete
2. Go to **Component Sync** in admin menu
3. Read the **Help** tab for usage instructions
4. Try the **Export** tab to export a component
5. Try the **Import** tab to import it back

Enjoy syncing your Elementor components!
