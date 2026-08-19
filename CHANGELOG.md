# Changelog

Running log of what's been built, in plain language. Newest first.

## 2026-08-19

- Read `School System Development Plan.pdf` in full; distilled it into `docs/ARCHITECTURE.md`,
  `docs/COMPLIANCE.md`, `docs/ROADMAP.md`.
- Decided the stack with the project owner: Laravel (PHP) monolith, Blade + Livewire/Alpine
  frontend, MySQL, deployed to the `scholara.cruzeintelligentsystems.com` subdomain on existing
  cPanel hosting. Documented the deploy steps in `docs/DEPLOYMENT.md` (subdomain not yet
  created on the actual host — steps are ready for when it is).
- Initialized the local git repo, added `origin` → `github.com/cruze-intelligent/Scholara`
  (repo existed but was empty).
- No PHP/Composer/MySQL were present on the dev machine; installing Laragon to get a working
  local Laravel environment.
