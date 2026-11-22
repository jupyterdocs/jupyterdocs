# JupyterDocs migration runbook: Laravel Cloud to a VPS behind Cloudflare

Deadlines from the Laravel Cloud notice: **suspension Nov 27, termination and data deletion Dec 4.**
Work backwards from those. The database export (step 1) is the only step that cannot be redone later, so do it first.

## 0. What can and cannot move

| Piece | Where it lives now | Moves? |
|---|---|---|
| Uploaded documents | Cloudflare R2 | **No.** It is already off the platform. Keep using it. |
| DNS, CDN, SSL | Cloudflare | **No.** Stays. Only the origin address changes. |
| Database | Laravel Cloud | **Yes. This is the data you can lose.** |
| PHP app | Laravel Cloud | Yes. Code is in GitHub. |
| Queue worker, scheduler | Laravel Cloud | Yes. Recreate them on the new server. |
| Environment variables | Laravel Cloud dashboard | Yes. Copy them out now. |

"Cloudflare free forever" cannot host this app by itself. Workers and Pages do not run Laravel/PHP, and the database and queue worker need a real server. The realistic plan is a small VPS as the origin, with Cloudflare (free) in front and R2 for files. Cloudflare stays free. The VPS is the only cost.

## 1. Today: save everything (no downtime, do not skip)

1. **Export the database.** In the Laravel Cloud dashboard open the database, then look for Export, Backup or Connection details.
   - If there is an export button, download the dump.
   - If not, copy the host, port, user, password and database name, then run from your own machine:
     - MySQL: `mysqldump --single-transaction --no-tablespaces -h HOST -P PORT -u USER -p DBNAME > jupyterdocs.sql`
     - Postgres: `pg_dump -h HOST -p PORT -U USER -Fc DBNAME > jupyterdocs.dump`
   - A restricted account may block database access. If so, contact Laravel Cloud support and ask for a data export. Cite the Dec 4 deletion date.
2. **Copy every environment variable** out of the dashboard into a private file (password manager or encrypted note). Never commit it.
3. **Keep `APP_KEY` exactly the same.** Changing it logs everyone out and breaks encrypted values.
4. **Confirm R2 is independent.** Check that the bucket is in your own Cloudflare account and that the R2 API token is yours. If the bucket is owned by the platform, copy it with `rclone` before Dec 4.
5. **Check the Cloudflare DNS record** for jupyterdocs.com. Note its current target so you can roll back.

## 2. Pick a server

Needs: 1 vCPU, 1 GB RAM minimum (2 GB is more comfortable), Ubuntu 24.04 LTS, 20 GB disk. Any provider works (Hetzner, DigitalOcean, Vultr, Contabo, Oracle Cloud free tier). Choose a region close to your students.
Rough monthly cost for a small VPS is a few dollars. Check current prices, since they change.
Note that **file storage is on R2**, so the server disk only holds code, logs and temp files.

## 3. Prepare the server

```bash
# as root, once
adduser deploy && usermod -aG sudo deploy
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw enable

apt update && apt install -y nginx git unzip curl qpdf supervisor \
  php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  php8.3-mysql php8.3-sqlite3 php8.3-gd php8.3-intl php8.3-bcmath
# use php8.3-pgsql instead of php8.3-mysql if your database is Postgres
# database server: apt install -y mariadb-server   (or postgresql)

# composer and node
curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt install -y nodejs
```

`qpdf` is installed here on purpose. It is what linearizes PDFs (fast first page).
The app requires PHP `^8.2`. 8.3 satisfies that.

## 4. Deploy the app

```bash
sudo mkdir -p /var/www/jupyterdocs && sudo chown deploy: /var/www/jupyterdocs
cd /var/www/jupyterdocs
git clone https://github.com/<your-account>/jupyterdocs.git .
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env     # then fill it in, see step 5
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 5. Environment file

Start from the variables you saved in step 1. Values that must be right on the new server:

```
APP_NAME=JupyterDocs
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jupyterdocs.com
APP_KEY=<the SAME key as before>

DB_CONNECTION=mysql          # or pgsql
DB_HOST=127.0.0.1
DB_DATABASE=jupyterdocs
DB_USERNAME=...
DB_PASSWORD=...

FILESYSTEM_DISK=s3           # R2 settings below, copy from the old dashboard
AWS_ACCESS_KEY_ID=...  AWS_SECRET_ACCESS_KEY=...  AWS_BUCKET=...
AWS_DEFAULT_REGION=auto  AWS_ENDPOINT=...  AWS_USE_PATH_STYLE_ENDPOINT=true

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp  MAIL_HOST=...  MAIL_PORT=...  MAIL_USERNAME=...  MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...  MAIL_FROM_NAME="${APP_NAME}"
CLOUDCONVERT_API_KEY=...

ADS_ENABLED=false            # switch on after AdSense approval
ADSENSE_CLIENT=ca-pub-1443751242152710
```

Copy the other `AWS_*`, Google login and `SOCIAL_*` values exactly as they were.
**Rotate secrets while you are here.** The R2 token and the Google client secret were pasted into a chat earlier, so generate new ones and put the new values in this file only.

## 6. Restore the data

```bash
mysql -u root -e "CREATE DATABASE jupyterdocs CHARACTER SET utf8mb4; CREATE USER 'jd'@'localhost' IDENTIFIED BY '...'; GRANT ALL ON jupyterdocs.* TO 'jd'@'localhost';"
mysql -u jd -p jupyterdocs < jupyterdocs.sql
php artisan migrate --force      # applies anything newer than the dump
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 7. Web server, queue worker, scheduler

**Nginx** (`/etc/nginx/sites-available/jupyterdocs`, then symlink to sites-enabled):

```nginx
server {
    listen 80;
    server_name jupyterdocs.com www.jupyterdocs.com;
    root /var/www/jupyterdocs/public;
    index index.php;
    client_max_body_size 64M;          # raise to your upload limit

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

**Queue worker** (`/etc/supervisor/conf.d/jd-worker.conf`). This runs PDF linearization and conversion jobs:

```ini
[program:jd-worker]
command=php /var/www/jupyterdocs/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
numprocs=1
stopwaitsecs=3600
```

Then `supervisorctl reread && supervisorctl update`.

**Scheduler:** `crontab -u www-data -e` and add
`* * * * * cd /var/www/jupyterdocs && php artisan schedule:run >> /dev/null 2>&1`

**PHP tuning:** in `/etc/php/8.3/fpm/conf.d/99-jd.ini` set `opcache.enable=1`, `opcache.validate_timestamps=0`, `upload_max_filesize=64M`, `post_max_size=64M`. Remember to reload php-fpm after each deploy when timestamps are off.

## 8. Test before switching DNS

1. Edit your own computer's hosts file to point jupyterdocs.com at the new server's IP (or use the IP with a Host header).
2. Check: homepage, login, a document page, preview, download, upload (admin), search.
3. Check `https://jupyterdocs.com/ads.txt` returns your publisher line.
4. Run `php artisan pdf:linearize-existing` so existing PDFs get the fast-open treatment (the queue worker must be running).

## 9. Cut over

1. Put the Cloudflare SSL mode on **Full (strict)** after installing an origin certificate. Quickest route: Cloudflare dashboard, SSL/TLS, Origin Server, create a certificate, install it in Nginx on port 443.
2. In Cloudflare DNS change the `A` record for `@` (and `www`) to the new server IP, proxy **on** (orange cloud).
3. Wait a minute, then reload the site and check the response `server` header and `Server-Timing` header to confirm you are hitting the new origin.
4. **Keep the old platform alive** until Nov 27 if the account still works, as a rollback. To roll back, change the DNS record back.

## 10. After the cutover

- **Cloudflare cache rule:** cache static assets (`/build/*`) for a long time. This is free and cuts load on the VPS.
- **Backups (required, the platform no longer does this for you):** nightly `mysqldump` to R2 or another bucket, keep 14 days.

  ```bash
  0 3 * * * mysqldump --single-transaction -u jd -p'...' jupyterdocs | gzip > /var/backups/jd-$(date +\%F).sql.gz
  ```

  Then sync `/var/backups` off the server with `rclone`. A backup that only lives on the same server is not a backup.
- **Security updates:** `apt install unattended-upgrades`.
- **Monitoring:** a free uptime check (UptimeRobot or similar) on the homepage.
- **AdSense:** it needs the site to stay reachable. After approval, follow the ad unit steps.
- **Email:** add SPF, DKIM and DMARC DNS records for your sending provider so mail does not land in spam.

## 11. Deploying updates later

```bash
cd /var/www/jupyterdocs
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart jd-worker:*
```

## Timeline (today is Nov 22)

| When | Do |
|---|---|
| Nov 22 | Step 1 (export, env vars, confirm R2). Pick a server. |
| Nov 23 | Steps 3 to 7. |
| Nov 24 | Step 8, full test. |
| Nov 25 | Step 9, cutover, 2 days of margin before the suspension. |
| Nov 26 onward | Step 10, backups first. |

## If something goes wrong

- **500 error:** `tail -n 50 /var/www/jupyterdocs/storage/logs/laravel.log`
- **Blank page or 502:** `systemctl status php8.3-fpm nginx`
- **Everyone logged out:** `APP_KEY` differs from the old one.
- **Files will not preview or download:** R2 keys, bucket name or endpoint wrong in `.env`.
- **PDFs never linearize:** worker not running (`supervisorctl status`) or `qpdf` missing (`which qpdf`).
- **Redirect loop after enabling Cloudflare:** SSL mode is "Flexible". Switch it to Full (strict).
