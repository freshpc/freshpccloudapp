# Pull Request Checklist — FreshPC Cloud

## Safety & secrets
- [ ] `config.php` is **not** tracked (`git check-ignore -v config.php` matches).
- [ ] No secrets in code (`password|SECRET|API_KEY|Bearer `).
- [ ] `.gitignore` covers: `config.php`, `.env`, `uploads/`, `logs/`, `reports/`, `.vs/`, `vendor/`, `node_modules/`.

## App integrity
- [ ] All HTML/PHP pages start with `<!doctype html>` (no Quirks Mode).
- [ ] Routes consistent: `/admin.php`, `/engineer.php` (or `/field-engineer.php`), `/login`.
- [ ] Auth endpoints unified:  
  - `POST /api/auth/login`  
  - `GET /api/auth/status`  
  and used in JS.
- [ ] `.htaccess` routes `/api/*` → `index.php`; pretty routes → correct files.

## Functionality quick-test
- [ ] Admin: login → `/admin.php` loads, users CRUD works.
- [ ] Engineer: login → `/engineer.php` loads, tasks appear; accept/start/done/reject OK.
- [ ] Uploads/signatures saved; finished/rejected tasks appear in history.
- [ ] DevTools: no console errors; network calls return 2xx.

## Code hygiene
- [ ] Only one `admin.js` and one `engineer.js` loaded (no duplicates).
- [ ] Lint/format run; debug/test code removed.
- [ ] README updated if setup changed.

## Deployability
- [ ] New env vars documented; no secrets committed.
- [ ] DB changes/migrations noted.
- [ ] Error logs clean after a smoke test.




