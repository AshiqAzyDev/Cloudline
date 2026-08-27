# Deployment

Cloudline Billing is a standard Laravel 13 app. Move from local SQLite to MySQL (or any supported database) by changing `.env` only.

## VPS (recommended)

1. PHP 8.3+, Composer, Node 20+, MySQL 8, Nginx.
2. Point the web root at `public/`.
3. Copy `.env.example` to `.env` and set production values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://billing.yourdomain.com`
   - MySQL credentials
   - Live Stripe keys (`pk_live_`, `sk_live_`, webhook secret)
   - A real mailer (`MAIL_MAILER=smtp` or Resend/Postmark)
   - `QUEUE_CONNECTION=database` (or redis)
4. `composer install --no-dev --optimize-autoloader`
5. `npm ci && npm run build`
6. `php artisan migrate --force`
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
8. Supervisor workers for `php artisan queue:work`
9. Cron: `* * * * * cd /path/to/cloudline && php artisan schedule:run >> /dev/null 2>&1`

Stripe webhook endpoint: `https://billing.yourdomain.com/stripe/webhook`

## cPanel / shared hosting

- Document root must be `public/` (or symlink `public_html` → `public`).
- Cron can run `php artisan schedule:run` every minute.
- Queue: if you cannot run a worker, set `QUEUE_CONNECTION=sync` (emails send during the request).
- PDFs use DomPDF (no Chrome required).

## Docker

`docker compose up -d` starts MySQL 8 on port 3306. Point `.env` at it:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cloudline
DB_USERNAME=cloudline
DB_PASSWORD=secret
```
