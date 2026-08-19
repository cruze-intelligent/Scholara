# Deployment — `scholara.cruzeintelligentsystems.com`

Target: a cPanel-hosted subdomain on `cruzeintelligentsystems.com`. The subdomain does not
exist yet — steps 1–2 create it.

## 1. Create the subdomain in cPanel

1. cPanel → **Domains** → **Create A New Domain**.
2. Domain: `scholara.cruzeintelligentsystems.com`. Leave "Share document root" unchecked.
3. Document root: point it at a folder **outside** the eventual Laravel `public/` sibling, e.g.
   `scholara` (cPanel will suggest `public_html/scholara` — that's fine as the *deploy target*,
   but the site's actual web root must end up being `public_html/scholara/public`, Laravel's
   `public/` folder, not the project root — see step 4).
4. Confirm DNS: cPanel adds the subdomain's A/AAAA record automatically if it manages DNS for
   the domain; otherwise add `scholara` → server IP at whatever registrar/DNS host manages
   `cruzeintelligentsystems.com`.

## 2. Confirm PHP version and extensions

cPanel → **MultiPHP Manager** → select `scholara.cruzeintelligentsystems.com` → PHP 8.2+
(Laravel 11 requires PHP ≥ 8.2). Then **Select PHP Version** → enable extensions: `mbstring`,
`openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`.

## 3. Database

cPanel → **MySQL Databases** → create a database and a dedicated DB user with all privileges on
it (don't reuse a shared/root user). Note the generated names — cPanel usually prefixes them
with the cPanel account name (e.g. `cruzeint_scholara`).

## 4. Get the code onto the server

Two options depending on what the hosting plan supports:

**A. Git deploy (preferred, if cPanel's Git Version Control feature or SSH is available)**
```bash
# on the server, via cPanel Terminal or SSH
cd ~
git clone https://github.com/cruze-intelligent/Scholara.git scholara-src
cd scholara-src
composer install --no-dev --optimize-autoloader
```
Then either symlink/move `scholara-src/public/*` into the subdomain's web root while keeping
the rest of the app **outside** the web root (standard Laravel-on-shared-hosting pattern), or
if the host only lets you point the web root at one folder, deploy the whole app under
`public_html/scholara-src` and set the subdomain's document root to
`public_html/scholara-src/public`.

**B. Upload via File Manager/FTP (if no SSH/Git)**
Build the `vendor/` folder locally (`composer install --no-dev --optimize-autoloader`) and
upload the whole project as a zip via File Manager, extract it, and set the subdomain's document
root to `<project>/public` as above. Composer won't run on shared hosting without SSH, so
`vendor/` must be pre-built and shipped.

## 5. Environment

Copy `.env.example` to `.env` on the server and fill in:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://scholara.cruzeintelligentsystems.com
DB_HOST=localhost
DB_DATABASE=<cpanel db name>
DB_USERNAME=<cpanel db user>
DB_PASSWORD=<cpanel db password>
```
Then, via SSH/Terminal:
```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 6. HTTPS

cPanel → **SSL/TLS Status** → run AutoSSL for `scholara.cruzeintelligentsystems.com` (Let's
Encrypt via cPanel is normally free and automatic once the subdomain resolves).

## 7. Cron (predictive analytics, queued jobs)

cPanel → **Cron Jobs** → add, every minute:
```
* * * * * php /home/<cpanel-user>/scholara-src/artisan schedule:run >> /dev/null 2>&1
```

---

Once SSH/Git access on the actual hosting account is confirmed, this doc should be updated with
the real cPanel account username and any host-specific quirks (some shared hosts disable
`exec`/`proc_open`, which affects queue workers — check before relying on `php artisan queue:work`
in production; `database` queue driver + the cron scheduler above works without it).
