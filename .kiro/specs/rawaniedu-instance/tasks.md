# Tasks: rawaniedu.com instance

- [x] Fresh-install migration fix (roles by slug) — tested on an empty local DB
- [x] Brand name from settings in `auth.app_name` (+ escape)
- [x] PHP 8.4: fileinfo + opcache
- [x] Clone, DB, .env, composer, migrate, seed, permission re-grant, brand rows, admin account
- [x] nginx vhost, cron (scheduler + queue)
- [x] DNS `A @ → 198.244.189.4` (mail records kept)
- [x] Let's Encrypt (apex + www), HTTPS redirect
- [x] Live check: login → dashboard 200, `.env`/`.git` 403
- [x] Nightly backup entry
- [ ] Logo for رواني (currently the الأول logo)
- [ ] Mail sending (currently `MAIL_MAILER=log`)
