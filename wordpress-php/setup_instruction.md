# xString Tech Passwordless Authentication

## Add the Impersonation-Resistant Credential (iRC) Passwordless Authentication Method to WordPress

## Overview

This guide explains how to install, configure and test the xString Tech Passwordless Authentication plugin on WordPress running on a Linux or Windows server, including an Amazon Lightsail instance.

The plugin adds xString Tech passwordless authentication as an additional login option. It complements WordPress’s existing authentication system and does not replace the native username-and-password login.

## 1. Prepare WordPress on Linux
Assumptions: WordPress is already installed on the Linux server, the domain’s DNS record points to the server, and the firewall permits inbound TCP traffic on ports 80 and 443.

### 1.1 Request a TLS Certificate Using the Apache Plugin

Assumption: Certbot is not yet installed. You can check by running `certbot --version`.

```bash
sudo apt update
```
```bash
sudo apt install certbot python3-certbot-apache
```
```bash
sudo certbot --apache -d www.yourdomain.com
```

### 1.2 Edit conf file

```bash
sudo nano /etc/apache2/sites-available/www.yourdomain.com.conf
```
Use the following as a reference configuration. Replace `www.yourdomain.com` with your actual domain, and confirm that the paths match your WordPress installation before applying it.
```text
<VirtualHost *:80>
    ServerName www.yourdomain.com
    DocumentRoot /var/www/html

    RewriteEngine On
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName www.yourdomain.com
    DocumentRoot /var/www/html

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/www.yourdomain.com/privkey.pem

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/www.yourdomain.com-error.log
    CustomLog ${APACHE_LOG_DIR}/www.yourdomain.com-access.log combined
</VirtualHost>
```
Save in nano by pressing `Ctrl+O`, then `Enter`, and then `Ctrl+X`.

```bash
sudo nano /etc/apache2/sites-available/default-ssl.conf
```

Inside the existing `<VirtualHost *:443>` block, make the following changes:
```text
1. Add or update the ServerName directive:
ServerName www.yourdomain.com

2. Replace the two existing self-signed certificate directives with:
SSLCertificateFile /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/www.yourdomain.com/privkey.pem

Example:
<VirtualHost *:443>
        ServerName www.yourdomain.com
        ServerAdmin webmaster@localhost

        DocumentRoot /var/www/html
   ...

        SSLCertificateFile /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem
        SSLCertificateKeyFile /etc/letsencrypt/live/www.yourdomain.com/privkey.pem
   ...
</VirtualHost>

Leave the existing `DocumentRoot`, directory settings, logging and other SSL settings unchanged. Avoid defining conflicting virtual hosts or duplicating directives. If Certbot has already configured the site, review its configuration before making manual changes.
```
Save with Ctrl+O, press Enter, then Ctrl+X.

### 1.3 Test the configuration before reloading Apache and checking HTTPS response

```bash
sudo apache2ctl configtest
```
`Syntax OK` confirms that Apache’s configuration has no syntax errors.

Reload Apache
```bash
sudo systemctl reload apache2
```

Check the HTTPS response, replacing www.yourdomain.com with your own domain:
```bash
curl -I https://www.yourdomain.com
```
You should receive an HTTP response such as 200 OK

## 2. Prepare the Plugin on Linux

### 3.1 Locate the WordPress Plugin Directory

The following instructions assume WordPress is installed at:

```text
/var/www/html
```

Open a terminal on the Linux server and run:

```bash
cd /var/www/html/wp-content/plugins
```
```bash
sudo mkdir xstring-tech-passwordless
```
```bash
cd xstring-tech-passwordless
```

If the plugin directory already exists, skip the `mkdir` command.

### 2.2 Create the Plugin File

Run:

```bash
sudo nano xstring-tech-passwordless.php
```

Download the complete `xstring-tech-passwordless.php` file from this repository, then open it and copy its entire contents into the editor. This is recommended over copying directly from GitHub, as copying from the web interface may inadvertently omit parts of the code.

Save the file:

1. Press `Ctrl+O`.
2. Press `Enter`.
3. Press `Ctrl+X`.

Verify that the file exists:

```bash
ls -l
```

The directory should contain: xstring-tech-passwordless.php

```bash
sudo ls -l /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

Proceed to [Section 4: Activate and Configure the Plugin](#4-activate-and-configure-the-plugin).

---

## 3. Prepare the Plugin on Windows

Assumptions: WordPress is already installed on the Windows server, the domain’s DNS record points to the server, inbound TCP traffic on ports 80 and 443 is permitted, and a valid TLS certificate has been configured.

If you still need to obtain a TLS certificate for your WordPress domain, follow the **win-acme Certificate Guide** in the [xString Tech Windows Server preparation guide](https://github.com/XSTRING-TECH/software-downloads/blob/main/03_Prep_CCS_Win_Server.md). Adapt the instructions to your WordPress domain and web-server configuration.

### 3.1 Locate the WordPress Plugin Directory

Locate the WordPress installation directory on the Windows server.

The plugin directory will typically be:

```text
C:\...\wordpress\wp-content\plugins\
```

The exact path depends on how WordPress was installed.

### 3.2 Create the Plugin Directory

Using Windows File Explorer, navigate to the WordPress `wp-content\plugins` directory.

Create a folder named:

```text
xstring-tech-passwordless
```

### 3.3 Copy the Plugin File

Copy the complete `xstring-tech-passwordless.php` file from this repository into the new directory.

The resulting directory structure should be:

```text
wp-content
└── plugins
    └── xstring-tech-passwordless
        └── xstring-tech-passwordless.php
```

### Windows Web Server Requirement
The plugin uses a WordPress rewrite rule to handle the `/code-exchange` callback endpoint. The web server must route requests for this endpoint through WordPress.

If the Windows installation uses IIS, ensure its URL Rewrite configuration supports the WordPress permalink rules and passes the request to WordPress’s `index.php`. If the endpoint returns a 404 error, check the rewrite configuration and save the WordPress permalink settings again.

Proceed to [Section 4: Activate and Configure the Plugin](#4-activate-and-configure-the-plugin).

---

## 4. Activate and Configure the Plugin

These steps apply to both Linux and Windows installations.

### 4.1 Activate the Plugin

Log in to WordPress as an administrator.

If you do not know the WordPress administrator password, use one of the recovery methods below.

**Linux — reset the password with WP-CLI**

If WP-CLI is installed and the WordPress path is `/var/www/html`, run the following command. Replace `user` with the existing WordPress administrator username. WP-CLI prompts for the new password rather than requiring it to be written directly into the command:

```bash
sudo -u www-data wp user update user --prompt=user_pass --path=/var/www/html
```
If WP-CLI is not installed or this command is not suitable for your server, use the recovery method provided by your hosting environment.

**Windows — reset the password**

If you can access the administrator account’s registered email address, open `https://your-wordpress-domain.com/wp-login.php?action=lostpassword`, enter the account’s username or email address, and follow the password-reset link sent by WordPress. If email-based recovery is unavailable, use the recovery procedure documented for your WordPress hosting environment.

Open the WordPress login page, replacing your-wordpress-domain.com with your own domain:

```bash
https://your-wordpress-domain.com/wp-login.php
```

After logging in successfully as an administrator, navigate to:

**Plugins → Installed Plugins**

Find:

```text
xString Tech Passwordless Authentication
```

Click **Activate**.

After activation, the following settings page should be available:

**Settings → xString Passwordless**

### 4.2 Enter the xString Configuration

Navigate to:

**Settings → xString Passwordless**

Under **Cloud API Credentials**, enter the six configuration values supplied through [xstring.tech](https://xstring.tech/). 
| Setting | Description |
|---|---|
| `CLIENT_NPK` | xString client NPK |
| `AUTH_SCOPE` | xString authentication scope |
| `AAS_BASE_URL` | xString SaaS Authentication API base URL |
| `ACCESS_KEY_ID` | xString access key ID |
| `SECRET_ACCESS_KEY` | xString secret access key |
| `AUTH_CODE_URL` | Public HTTPS URL that WordPress uses to receive the authentication callback; it must end in `/code-exchange`. |

Also, enter your site's landing page URL, for example, `https://your-wordpress-domain.com`.

Example values:

```text
CLIENT_NPK=your-client-npk
AUTH_SCOPE=your-auth-scope
AAS_BASE_URL=https://saas.xstring.tech
ACCESS_KEY_ID=your-access-key-id
SECRET_ACCESS_KEY=your-secret-access-key
AUTH_CODE_URL=https://your-wordpress-domain.com/code-exchange
LANDING_PG_URL=https://your-wordpress-domain.com
```

These are illustrative placeholders, not actual credentials. Replace each placeholder with the value supplied for your configuration.

**Security:** Do not publish real credentials in GitHub repositories, documentation, screenshots or other publicly accessible locations.

### 4.3 Configure the Callback URL
In the WordPress dashboard, navigate to **Settings → Permalinks**. Under **Common Settings**, select **Post name**, then click **Save Changes**. 

This refreshes WordPress’s rewrite rules so the `/code-exchange` callback can be routed to WordPress. Confirm that the public callback URL in `AUTH_CODE_URL` matches the site’s HTTPS domain and `/code-exchange` path.

## 5. Test WordPress Login

Replace your-wordpress-domain.com with your own domain. Open the normal WordPress login page.

If you are still logged in as an administrator, open the WordPress login page in another browser or log out of the administrator account. You should then see Continue with iRC Authentication.

```text
https://your-wordpress-domain.com/wp-login.php
```
Select **Continue with iRC Authentication** and complete the authentication request in a supported authenticator app. If the button or authentication flow does not work, refresh the page and try again. If it succeeds, confirm that the user is logged in to the expected WordPress account.

If you see this error:

`cURL error 28: Failed to connect to saas.xstring.tech port 443 after 10000 ms: Timeout was reached`

The SaaS Authentication API may be temporarily unavailable. Please email info@xstring.tech for assistance.

The existing WordPress login should remain available, including:

- Username or Email Address
- Password
- Log In

The plugin adds an additional option:

**Continue with iRC Authentication**

The standard WordPress username-and-password login should remain available alongside the additional iRC option.

## 6. Authenticator App Requirements

Users of a WordPress site using this plugin need a compatible **authenticator app** to complete passwordless authentication.

Supported authenticator apps include:

* **myAge**
* **iRC Auth**

When a user signs up or signs in to the WordPress site, the authentication request is handled by the configured xString authentication service.

The user then uses the **Confirm it’s you** feature in a supported authenticator app to approve the authentication request.

> **Important:** Users must have a compatible authenticator app installed on their device to complete passwordless authentication.

### Get the Authenticator App

Users can obtain the supported Authenticator Apps from the official app stores:

### myAge Authenticator

**[Download on the App Store](https://apps.apple.com/us/app/myage/id6763018516)**

**[Get it on Google Play](https://play.google.com/store/apps/details?id=com.xstring.poadc&pcampaignid=web_share)**

### iRC Auth

**Download on the App Store — Coming Soon**

**[Get it on Google Play](https://play.google.com/store/apps/details?id=com.xstring.ircauth&pcampaignid=web_share)**

---

## 7. iRC Passwordless Authentication Flow

The expected flow is:

```text
WordPress
   ↓
xString /anoi
   ↓
xString authentication
   ↓
/code-exchange
   ↓
Plugin receives code and state
   ↓
xString /token-exchange
   ↓
JWT returned
   ↓
JWT signature verified using JWKS
   ↓
NPK extracted
   ↓
Find WordPress user by `xstring_npk`
   ↓
Matching account → log in
No matching account → attempt account creation
   ↓
Normal WordPress session
```

### New WordPress User

When a user successfully authenticates through xString for the first time, the plugin attempts to create a corresponding WordPress account and associate it with the authenticated credential.

For Example:

```text
Username:
NPK.123456789

xstring_npk:
NPK.123456789
```

The `xstring_npk` user metadata value associates the WordPress account with the authenticated credential’s NPK.

When the same NPK authenticates again, the plugin uses this metadata to locate the existing WordPress account and log the user in.

If the username derived from the NPK already belongs to an account whose `xstring_npk` metadata does not match the authenticated credential’s NPK, the plugin rejects the login or account creation. It does not automatically link the credential to that existing account. This safeguard does not remove or disable the account’s existing WordPress username-and-password login.

---

## 8. WordPress Authentication Remains in Control

The plugin is intended to complement WordPress rather than replace its existing authentication mechanisms.

WordPress remains responsible for its user accounts, roles, sessions, passwords and permissions.

The iRC passwordless method provides an additional authentication option.

## 9. Installation Checklist

Before considering the installation complete, verify the following:

- [ ] The plugin file is in the correct plugin directory.
- [ ] The plugin is activated in WordPress.
- [ ] All six xString configuration values are entered and saved.
- [ ] `AUTH_CODE_URL` uses the correct public HTTPS domain and `/code-exchange` path.
- [ ] WordPress rewrite rules have been refreshed if required.
- [ ] The website has a valid TLS certificate.
- [ ] The normal WordPress login remains available.
- [ ] iRC passwordless authentication completes successfully.
- [ ] A newly authenticated user is associated with the correct `xstring_npk` value.
- [ ] A subsequent authentication locates the existing WordPress account.

## 10. Configuration Reference

The seven required settings are:

```text
CLIENT_NPK
AUTH_SCOPE
AAS_BASE_URL
ACCESS_KEY_ID
SECRET_ACCESS_KEY
AUTH_CODE_URL
LANDING_PG_URL
```

The default xString SaaS API base URL is:

```text
https://saas.xstring.tech
```

The callback URL should normally follow this format:

```text
https://your-wordpress-domain.com/code-exchange
```

Site's landing page URL should normally follow this format:

```text
https://your-wordpress-domain.com
```

Replace `your-wordpress-domain.com` with the actual WordPress domain.
