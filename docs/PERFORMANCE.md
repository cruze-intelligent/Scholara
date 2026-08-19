# Performance

PHP is only the backend request-handler here — it should never be the thing that makes the
machine feel slow. What that means concretely, and what's already in place:

## Local dev

- **OPcache is enabled** in the Laragon PHP install (`php.ini`, `[opcache]` section). Without it,
  every request recompiles the entire Laravel framework from source — the first request after a
  restart took 5+ seconds; with OPcache warm, the same request drops to ~300ms. This was the
  actual fix for the "don't let PHP cause lag" concern, not a workaround — it's the standard
  production setting, just also turned on for local dev.
- `opcache.enable_cli=0` — CLI commands (`artisan migrate`, tinker, tests) don't share the web
  OPcache, so schema/code changes made via CLI are never masked by stale cached bytecode.
- `opcache.validate_timestamps=1` with a 1s `revalidate_freq` — picks up file edits automatically
  during development without needing a manual cache clear. (In production, flip
  `validate_timestamps` to `0` and clear the cache on deploy instead — checking file mtimes on
  every request is itself overhead once code stops changing every few seconds.)
- **MySQL only needs to run while actively developing.** It's a real Windows process
  (`mysqld.exe`, started manually against `C:\laragon\data\mysql`), not a service that starts at
  boot or idles in the background — stop it when you're not working on Scholara.
- **`php artisan serve`** (the built-in dev server) is single-threaded and only for local
  development — start it when testing, stop it when done. It is not what production runs.

## Production (cPanel)

- PHP-FPM (via MultiPHP Manager) + OPcache, not `php artisan serve`.
- `php artisan config:cache`, `route:cache`, `view:cache` on every deploy (see
  [docs/DEPLOYMENT.md](./DEPLOYMENT.md)) — these remove filesystem scanning and Blade compilation
  from the request path entirely.
- Queue heavy work (payment webhooks, SMS sending, predictive-analytics batch runs) through
  Laravel's queue system rather than doing it inline in a request — see the `database` queue
  driver note in DEPLOYMENT.md for why that works even without a persistent queue worker process
  on constrained shared hosting.
