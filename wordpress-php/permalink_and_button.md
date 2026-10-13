## 1. WordPress Permalink Configuration

After installing and activating the xString Tech Passwordless plugin, configure WordPress to process the plugin's `/code-exchange` authentication callback.

### Step 1 — Configure Permalinks

1. Sign in to WordPress Admin.
2. Navigate to **Settings → Permalinks**.
3. Under **Common Settings**, select **Post name**.
4. Click **Save Changes**.

Test iRC authentication. If it fails, refresh the page and try again. If authentication succeeds, no further action is required.

### Step 2 — Troubleshoot a 404 Error

If the `/code-exchange` callback returns an Apache `404 Not Found` error, check that the WordPress `.htaccess` file contains the standard WordPress rewrite rules.

The following commands assume WordPress is installed in `/var/www/html`.

Check whether the file exists:

```bash
ls -l /var/www/html/.htaccess
```

If the file is missing or the rewrite rules are absent, create or edit it:

```bash
sudo nano /var/www/html/.htaccess
```

Add the following standard WordPress rewrite rules:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

Save with **Ctrl+O**, press **Enter**, then exit with **Ctrl+X**.

Ensure Apache permits `.htaccess` overrides for the WordPress directory. If necessary, consult the server administrator before changing Apache configuration or file permissions.

Test the Apache configuration:

```bash
sudo apache2ctl configtest
```

If the result is `Syntax OK`, reload Apache:

```bash
sudo systemctl reload apache2
```

Retest iRC authentication. If the 404 error persists, check the Apache virtual host and directory configuration.

## 2. Add a Continue with iRC Authentication Button

The plugin provides a shortcode to display the passwordless authentication button on a WordPress page. The plugin also automatically adds the button to the standard WordPress login form.

### Step 1 — Create a Page

1. In WordPress Admin, navigate to **Pages → Add New Page**.
2. Enter a page title, such as **Passwordless Authentication**.
3. Add any introductory instructions required.

### Step 2 — Add the Shortcode

1. Click the **+** icon to open the Block Inserter.
2. Search for **Shortcode** and select the Shortcode block.
3. Enter the following shortcode:

```text
[irc_passwordless]
```

4. Click **Publish**.

If the **+** icon is not visible, click inside the page content area and type `/shortcode` to search for the block.

### Step 3 — Test the Button
