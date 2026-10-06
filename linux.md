# Basic Linux Commands

```bash
cat /etc/os-release
sudo pm2 list
sudo pm2 stop all
sudo ss -ltnp | grep ':80'
sudo apt update
sudo apt install snapd
sudo snap install snapd
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/local/bin/certbot
certbot --version
sudo certbot certonly --standalone -d www.yourdomain.com
cd /var/www/nodeapp
ls
npm init -y
npm install express axios dotenv body-parser jsonwebtoken cookie-parser
nano .env
nano myserver.js
clear
sudo node myserver.js
history
```

## Editing Files with nano

```bash
nano .env
nano server.js
```

> **Note:** If `server.js` cannot be edited because of Linux file permission restrictions, simply create a new file instead:
>
> ```bash
> nano myserver.js
> ```

In `nano`:

* `Ctrl + O` → save
* `Enter` → confirm filename
* `Ctrl + X` → exit

### Useful nano Shortcuts

* `Ctrl + W` → search
* `Ctrl + _` → go to a specific line
* `Ctrl + K` → cut current line
* `Ctrl + U` → paste previously cut line
* `Ctrl + X` → exit
