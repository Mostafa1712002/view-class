# Design: rawaniedu.com instance

- Path `/www/wwwroot/rawaniedu.com` (git clone of `main`), docroot `public/`, owner `www`.
- PHP 8.4 FPM (`/tmp/php-cgi-84.sock`); `fileinfo` built from source for it and opcache enabled (8.2 is too old for zipstream-php ^8.3).
- DB `rawani`, user `rawani`; credentials in `/root/.rawani_creds`.
- Brand: `settings` rows `brand_name_ar/en` (platform-level). `auth.app_name` now follows them (AppServiceProvider), escaped.
- Fresh install: `migrate` → `db:seed` → re-run the `permission_role` grant migrations' `up()` (seeders `sync()` role permissions and drop the migration grants).
- Cron: `schedule:run` + `queue:work --stop-when-empty --max-time=55` every minute (no supervisor on this box).
- TLS: acme.sh, cert in `/www/server/panel/vhost/cert/rawaniedu.com/`, auto-renew by the existing acme cron.
- Backup: added to `/root/daily-backup.sh`.
- Deploy: `cd /www/wwwroot/rawaniedu.com && git pull origin main && chown -R www:www . && sudo -u www HOME=/tmp /www/server/php/84/bin/php artisan migrate --force && … config:cache view:cache`.
