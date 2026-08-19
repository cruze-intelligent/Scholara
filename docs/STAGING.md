# Staging preview (Render)

A public link to click through the actual app — every dashboard, login, the real Blade UI —
before `scholara.cruzeintelligentsystems.com` exists on the real host. **Not** the production
deployment target; that's [docs/DEPLOYMENT.md](./DEPLOYMENT.md) (cPanel + MySQL). This one runs
in a Docker container on [Render](https://render.com)'s free tier, using SQLite so it doesn't
need a separate paid database service.

Vercel, Netlify, and GitHub Pages were considered but ruled out: they're static-site/JAMstack
hosts and cannot run a PHP/Laravel backend at all — nothing to build there for this project's
Blade-rendered app. Render's free web-service tier runs arbitrary Docker containers, which is
what this needs.

## What's already in the repo for this

- `Dockerfile` — multi-stage build: Node stage compiles the Vite assets, then a `php:8.3-apache`
  stage runs the app.
- `docker/entrypoint.sh` — rewrites Apache's listen port to Render's `$PORT` at container boot
  (it's assigned dynamically, not fixed), runs migrations, seeds roles (and demo users if
  `SEED_DEMO_DATA=true`), then caches config/routes/views.
- `render.yaml` — a Render Blueprint describing the service and its env vars, so Render can
  mostly configure itself from this file.
- `database/seeders/DatabaseSeeder.php` — seeds demo data on any environment when
  `SEED_DEMO_DATA=true` is set, not just `local`, so this staging box can have the same
  clickable demo accounts as local dev.

This hasn't been build-tested end-to-end yet — there's no Docker available in the dev
environment this was written in. The first deploy attempt may need a round of fixes based on
Render's build log; treat this doc's steps as the intended path, not a guarantee.

## Deploy steps (manual — needs your Render account)

1. Go to [render.com](https://render.com) and sign up / log in (GitHub login is easiest since the
   repo is already on GitHub).
2. **New +** → **Blueprint**. Connect the `cruze-intelligent/Scholara` GitHub repo. Render should
   detect `render.yaml` and propose one service: `scholara-staging`.
3. When prompted for the `APP_KEY` env var (marked `sync: false` in `render.yaml`, so Render asks
   for it rather than guessing), paste:
   ```
   base64:OF0Le/Y6CyR3nB+ewOkU5yxRtw7jREYGDeF+1iESW0M=
   ```
   (Freshly generated for this purpose — fine to use as-is, or generate your own with
   `php artisan key:generate --show` and use that instead.)
4. Click **Apply** / **Create**. Render builds the Docker image (Node asset build, then PHP
   stage) and deploys it — first build typically takes a few minutes.
5. Once live, Render gives you a URL like `https://scholara-staging.onrender.com`. Log in with
   any of the demo accounts (all password `password`): `admin@scholara.test`,
   `teacher@scholara.test`, `parent@scholara.test`, `learner@scholara.test`,
   `nurse@scholara.test`, `hr@scholara.test`, `bursar@scholara.test`, `librarian@scholara.test`.

## Known limitations of this staging setup (by design, not bugs)

- **Data doesn't persist across deploys.** SQLite lives inside the container's ephemeral
  filesystem; every redeploy starts from a fresh migrated + seeded database. Fine for clicking
  through the UI, not for anything you need to keep.
- **Free-tier cold starts.** Render's free web services spin down after inactivity; the first
  request after a quiet period can take 30–60 seconds while it wakes back up.
- **Sessions reset if `APP_KEY` isn't set as a persistent env var** (the entrypoint script warns
  and generates a throwaway one if you skip step 3) — logins won't survive a container restart.
- Real integrations (NIRA, SMS/OTP, SchoolPay) are still the fake/stub implementations described
  in `docs/ARCHITECTURE.md` — this only proves the app itself runs and is reachable, not that
  those external integrations work.

## When the real subdomain is ready

Switch back to following [docs/DEPLOYMENT.md](./DEPLOYMENT.md) for the cPanel/MySQL production
deploy. This Render service can stay around as a preview environment for testing changes before
they go live, or be deleted once it's no longer useful.
