# xString Tech Server Commands

## 1. System Information

Commands for checking Debian, Apache, and listening ports.

#### Check operating system

```bash
cat /etc/os-release
```

#### Check WordPress directory locations

```bash
ls -ld /opt/bitnami/wordpress /var/www/html 2>/dev/null
```

#### Check Bitnami certificate tool

```bash
ls -l /opt/bitnami/bncert-tool 2>/dev/null
```

#### Check Apache and Nginx status

```bash
sudo systemctl is-active apache2
sudo systemctl is-active nginx
sudo systemctl status apache2
```

#### Check processes listening on HTTP/HTTPS ports

```bash
sudo ss -ltnp | grep -E ':(80|443)\b'
```

## 2. Apache Configuration

Commands for inspecting, testing, and reloading Apache.

#### List Apache virtual hosts

```bash
sudo apache2ctl -S
```

#### View default HTTP site

```bash
sudo cat /etc/apache2/sites-enabled/000-default.conf
```

#### View default SSL site

```bash
sudo cat /etc/apache2/sites-enabled/default-ssl.conf
```

#### Edit the test domain configuration

```bash
sudo nano /etc/apache2/sites-available/test.xstring.tech.conf
```

#### Edit the AAS domain configuration

```bash
sudo nano /etc/apache2/sites-available/aas.xstring.tech.conf
```

#### Edit the default SSL configuration

```bash
sudo nano /etc/apache2/sites-available/default-ssl.conf
```

### Test and Apply Changes

#### Check Apache configuration syntax

```bash
sudo apache2ctl configtest
```

#### Reload Apache after configuration changes

```bash
sudo systemctl reload apache2
```

#### Stop Apache

```bash
sudo systemctl stop apache2
```

#### Start Apache

```bash
sudo systemctl start apache2
```

#### Check Apache service status

```bash
sudo systemctl status apache2
```

#### Test HTTPS response headers

```bash
curl -I https://aas.xstring.tech
```

## 3. SSL Certificates

Commands for checking Certbot and certificates.

#### Check Certbot version

```bash
certbot --version
```

#### Update package lists

```bash
sudo apt update
```

#### Install Certbot and Apache integration

```bash
sudo apt install certbot python3-certbot-apache
```

#### Request or configure a certificate for AAS

```bash
sudo certbot --apache -d aas.xstring.tech
```

#### List available certificates

```bash
sudo ls -l /etc/letsencrypt/live/
```

#### Inspect the AAS certificate directory

```bash
sudo ls -l /etc/letsencrypt/live/aas.xstring.tech/
```

#### View the certificate chain

```bash
sudo cat /etc/letsencrypt/live/aas.xstring.tech/fullchain.pem
```

## 4. WordPress Administration

Commands for managing WordPress users and settings.

#### Go to the WordPress directory

```bash
cd /var/www/html
```

#### List WordPress users

```bash
sudo -u www-data wp user list \
  --fields=ID,user_login,user_email,roles \
  --path=/var/www/html
```

#### Update a user's password interactively

```bash
sudo -u www-data wp user update user \
  --prompt=user_pass \
  --path=/var/www/html
```

Replace `user` with the actual WordPress username.

#### Check the WordPress site URL

```bash
sudo -u www-data wp option get home \
  --path=/var/www/html
```

## 5. Passwordless Authentication Plugin

Commands for editing, checking, and debugging the plugin.

#### Create the plugin directory

```bash
sudo mkdir -p /var/www/html/wp-content/plugins/xstring-tech-passwordless
```

#### Edit the plugin

```bash
sudo nano /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Check the plugin file

```bash
sudo ls -l /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Check PHP syntax

```bash
sudo php -l /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

### Inspect Specific Code

#### Find the API function

```bash
sudo grep -n -A 45 "function api_post" /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Find unsuccessful-response handling

```bash
sudo grep -n -B 15 -A 20 "unsuccessful response" /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Find landing-page URI references

```bash
sudo grep -n "landing_pg_uri" /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Display plugin lines 500–560

```bash
sudo sed -n '500,560p' /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Display plugin lines 660–720

```bash
sudo sed -n '660,720p' /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### Display plugin lines 843–910

```bash
sudo sed -n '843,910p' /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

## 6. Logs and Troubleshooting

Commands for inspecting Apache error logs.

#### Follow the Apache error log in real time

```bash
sudo tail -f /var/log/apache2/error.log
```

Press `Ctrl+C` to stop following the log.

#### Display the latest 50 error-log lines

```bash
sudo tail -n 50 /var/log/apache2/error.log
```

## 7. Shell Navigation

Common terminal commands.

#### Clear the terminal screen

```bash
clear
```

#### List files in the current directory

```bash
ls
```

#### Move to the home directory's API test folder

```bash
cd ~/xstring-api-test
```

#### Move up one directory

```bash
cd ..
```

#### View command history

```bash
history
```

## 8. Reusable Command Reference

Frequently used commands for routine maintenance.

#### 1. Check PHP syntax

```bash
sudo php -l /var/www/html/wp-content/plugins/xstring-tech-passwordless/xstring-tech-passwordless.php
```

#### 2. Check Apache configuration

```bash
sudo apache2ctl configtest
```

#### 3. Review recent Apache errors

```bash
sudo tail -n 50 /var/log/apache2/error.log
```

#### 4. Check Apache service status

```bash
sudo systemctl status apache2
```

#### 4. Check the rewrite rule

```bash
sudo -u www-data wp rewrite list --path=/var/www/html | grep code-exchange
```

#### 4. Check the WordPress permalink setting

```bash
sudo -u www-data wp option get permalink_structure --path=/var/www/html
```

#### 4. Edit Apache configuration

```bash
sudo nano /etc/apache2/apache2.conf
```

