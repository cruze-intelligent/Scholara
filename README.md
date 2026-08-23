# Scholara

Scholara is an integrated school management ecosystem for the Ugandan and global education
market — one system serving Learners, Parents, Teachers, Nurses, HR/Payroll staff, and School
Administrators from a single source of truth, with an offline-first path for rural connectivity.

The full product requirements and market/regulatory research live in
[`School System Development Plan.pdf`](./School%20System%20Development%20Plan.pdf) and are
distilled into the docs below.

## Stack

| Layer | Choice | Why |
|---|---|---|
| Backend | PHP 8 / Laravel | Runs on standard shared/cPanel hosting, no Node server required in production, strong batteries-included auth/migrations/queues. |
| Frontend | Blade + Livewire + Alpine.js | Server-rendered, one codebase/one deploy, works on PHP-only hosting. |
| Database | MySQL / MariaDB | Standard on shared hosting; relational model fits student/financial/health records. |
| Local dev | Laragon (PHP, Composer, MySQL, Apache/Nginx bundle) | Fastest path to a working Windows dev environment. |
| Auth roles | [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) | Role/permission model for Admin, Teacher, Parent, Learner, Nurse, HR, Bursar, Librarian. |
| Hosting target | `scholara.cruzeintelligentsystems.com` (subdomain, cPanel) | See [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md). |

See [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) for the module map and
[docs/ROADMAP.md](./docs/ROADMAP.md) for build order.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

See [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md) for pushing this to the
`scholara.cruzeintelligentsystems.com` subdomain.

## Documentation

- [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) — module map, role model, offline-first plan
- [docs/DATA_MODEL.md](./docs/DATA_MODEL.md) — core database entities
- [docs/COMPLIANCE.md](./docs/COMPLIANCE.md) — Data Protection & Privacy Act 2019, NIRA, PDPO, 2FA
- [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md) — cPanel subdomain deployment
- [docs/STAGING.md](./docs/STAGING.md) — public preview deploy (Laravel Cloud) for testing before the subdomain exists
- [docs/PERFORMANCE.md](./docs/PERFORMANCE.md) — keeping PHP/MySQL from slowing down the machine
- [docs/ROADMAP.md](./docs/ROADMAP.md) — build phases and current status
- [docs/DECISIONS.md](./docs/DECISIONS.md) — product defaults adopted where the plan didn't
  specify a formula (grading weights, PAYE/NSSF rates, Five Rights, etc.)
- [CHANGELOG.md](./CHANGELOG.md) — running log of what's been built
