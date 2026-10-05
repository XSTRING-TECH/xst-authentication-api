## Basic Linux Commands

```bash
cat /etc/os-release
sudo pm2 list
sudo pm2 stop all
sudo ss -ltnp | grep ':80'
sudo certbot certonly --standalone -d www.yourdomain.com
cd /var/www/nodeapp
ls
npm init -y
npm install express axios dotenv body-parser jsonwebtoken cookie-parser
sudo node myserver.js
nano .env
nano myserver.js
clear
history
```

### Editing files with nano

```bash
nano .env
nano myserver.js
```

In `nano`:

* `Ctrl + O` → save
* `Enter` → confirm filename
* `Ctrl + X` → exit

### Useful nano shortcuts

* `Ctrl + W` → search
* `Ctrl + _` → go to a specific line
* `Ctrl + K` → cut current line
* `Ctrl + U` → paste previously cut line
* `Ctrl + X` → exit
