# Staging preview (Laravel Cloud)

A public link to click through the actual app — every dashboard, login, the real Blade UI —
before `scholara.cruzeintelligentsystems.com` exists on the real host. **Not** the production
deployment target; that's [docs/DEPLOYMENT.md](./DEPLOYMENT.md) (cPanel + MySQL). This one runs on
[Laravel Cloud](https://cloud.laravel.com), Laravel's own managed hosting platform, with a real
persistent MySQL database.

**Live URL**: https://scholara-production-nujkni.laravel.cloud

## Demo accounts

All password `password`:

| Role | Email |
|---|---|
| Admin | admin@scholara.test |
| Teacher | teacher@scholara.test |
| Parent | parent@scholara.test |
| Learner | learner@scholara.test |
| Nurse | nurse@scholara.test |
| HR | hr@scholara.test |
| Bursar | bursar@scholara.test |
| Librarian | librarian@scholara.test |

## Why Laravel Cloud over Render

An earlier version of this staging setup ran on Render with a Docker image and SQLite in the
container's ephemeral filesystem (removed 2026-08-23). Laravel Cloud replaced it because:

- **Real MySQL**, not SQLite — matches the actual cPanel/MySQL production target, so behavior
  actually transfers. SQLite's loose type enforcement caused a real bug (see the 2026-08-22
  CHANGELOG entry on `decimal` casts) that MySQL wouldn't have hit.
- **Persistent data** — the database survives redeploys; Render's SQLite-in-a-container didn't.
- **No Dockerfile to maintain** — Laravel Cloud builds and deploys directly from the repo; the
  `Dockerfile`/`docker/entrypoint.sh`/`render.yaml` this project used to carry are gone.
- Push-to-deploy on `main` is enabled by default, same as Render was.

## How it's configured

No config files live in this repo for Laravel Cloud — everything is set in its dashboard
(`cloud.laravel.com` → scholara → production → Settings) or via the
[Laravel Cloud CLI](https://laravel.com/cloud/docs/api/cli) (`composer global require
laravel/cloud-cli`, then `cloud auth`).

- **Build command**: `composer install --no-dev --no-interaction --prefer-dist
  --optimize-autoloader && npm ci --audit false && npm run build`
- **Deploy command**: `php artisan migrate --force && php artisan db:seed --force` — safe to
  re-run on every deploy since every seeder call uses `firstOrCreate`/`updateOrCreate`, so it
  never duplicates existing data.
- **Environment variables**: `APP_KEY`, `APP_URL` (the vanity domain above), `DB_CONNECTION=mysql`
  (Laravel Cloud auto-injects `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`/`DB_DATABASE` when a database
  is attached, but does *not* auto-set `DB_CONNECTION` — miss this and the app silently falls back
  to SQLite and 500s on first DB-touching request), `SEED_DEMO_DATA=true`.
- **Database**: a Laravel MySQL cluster, Flex size (scales to zero when idle), 5GB storage,
  `eu-central-1` region, 0-day backup retention (this is test/demo data, not precious).
- Real integrations (NIRA, SMS/OTP, DGateway) are still the fake/stub implementations described in
  `docs/ARCHITECTURE.md` and `docs/DECISIONS.md` — this only proves the app itself runs and is
  reachable, not that those external integrations work.

## When the real subdomain is ready

Switch to following [docs/DEPLOYMENT.md](./DEPLOYMENT.md) for the cPanel/MySQL production deploy.
This Laravel Cloud environment can stay around as a preview environment for testing changes before
they go live.
