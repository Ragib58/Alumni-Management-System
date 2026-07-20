# Deployment Guide — Production

Target: Ubuntu 22.04, PHP 8.3, PostgreSQL 15, Redis 7, Nginx, Node 20.
Designed to scale to **50,000+ alumni users**.

---

## 1. Server provisioning

```bash
sudo apt update && sudo apt install -y \
  nginx postgresql redis-server supervisor git unzip \
  php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 2. PostgreSQL configuration

```bash
sudo -u postgres psql <<'SQL'
CREATE DATABASE ams_production;
CREATE USER ams WITH ENCRYPTED PASSWORD 'change-me-strong';
GRANT ALL PRIVILEGES ON DATABASE ams_production TO ams;
ALTER DATABASE ams_production OWNER TO ams;
SQL
```

Tuning `/etc/postgresql/15/main/postgresql.conf` (16 GB RAM example):

```
max_connections = 200
shared_buffers = 4GB
effective_cache_size = 12GB
work_mem = 20MB
maintenance_work_mem = 512MB
random_page_cost = 1.1            # SSD
max_wal_size = 4GB
```

Use **PgBouncer** (transaction pooling) in front of Postgres at high concurrency.
Enable `pg_stat_statements` to find slow queries. All hot paths are already
indexed (see `2024_05_01_000006_add_performance_indexes`).

## 3. Redis (cache / session / queue)

`/etc/redis/redis.conf`:
```
maxmemory 2gb
maxmemory-policy allkeys-lru       # cache eviction
appendonly yes                     # persistence for queues
requirepass change-me
```

## 4. Application deploy

```bash
cd /var/www && git clone <repo> ams && cd ams/backend
cp .env.production.example .env         # fill in secrets
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force   # + others as needed
php artisan storage:link

# Cache everything for production speed
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan optimize

sudo chown -R www-data:www-data storage bootstrap/cache
```

### Frontend build

```bash
cd /var/www/ams/frontend
npm ci
echo "VITE_API_URL=https://api.ams.example.com/api/v1" > .env.production
npm run build          # outputs dist/ (Nginx serves this)
```

## 5. Nginx

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/ams
sudo ln -s /etc/nginx/sites-available/ams /etc/nginx/sites-enabled/
sudo certbot --nginx -d ams.example.com -d api.ams.example.com
sudo nginx -t && sudo systemctl reload nginx
```

## 6. Queue workers (Supervisor)

```bash
sudo mkdir -p /var/log/ams
sudo cp deploy/supervisor/ams-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start ams-worker:* ams-notifications:*
```

Restart workers on each deploy: `php artisan queue:restart`.

## 7. Scheduler (cron)

```bash
sudo crontab -u www-data deploy/crontab.txt
```

This drives event reminders (hourly), thank-you messages (daily), failed-job
pruning, and nightly backups.

## 8. Backups

```bash
sudo chmod +x deploy/backup.sh      # runs nightly via cron (retention 14 days)
```

Test a restore quarterly:
```bash
pg_restore -h 127.0.0.1 -U ams -d ams_restore_test --clean db_YYYYMMDD.dump
```

## 9. Zero-downtime deploy (summary)

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan optimize
php artisan queue:restart
(cd ../frontend && npm ci && npm run build)
```

## 10. Scaling to 50k+ users

- **Horizontal**: multiple app nodes behind a load balancer; sessions/cache/queue
  in shared Redis; `->onOneServer()` guards scheduled tasks (already applied).
- **DB**: PgBouncer pooling + read replicas for analytics/report queries.
- **Media**: switch `FILESYSTEM_DISK=s3` + CloudFront for avatars/banners/tickets.
- **Notifications**: dedicated `notifications` queue + more workers (config'd).
- **Analytics**: already cached (Redis, TTL) and flushed on mutation.
- **CDN** in front of the SPA `dist/`.
- **Monitoring**: Laravel Telescope (staging), Horizon (queues), Sentry (errors),
  `pg_stat_statements` + slow-query log.
