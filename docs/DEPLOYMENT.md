# AmazePays Deployment Guide

> **Version:** 2.0  
> **Last Updated:** April 2026

---

## Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Environment Configuration](#2-environment-configuration)
3. [Development Setup](#3-development-setup)
4. [Production Deployment](#4-production-deployment)
5. [CI/CD Pipeline](#5-cicd-pipeline)
6. [Database Migrations](#6-database-migrations)
7. [Queue & Scheduler](#7-queue--scheduler)
8. [Monitoring](#8-monitoring)
9. [Backup Strategy](#9-backup-strategy)
10. [Scaling](#10-scaling)

---

## 1. Server Requirements

### Minimum Production Requirements

| Component | Requirement | Recommended |
|-----------|-------------|-------------|
| PHP | 8.3+ | 8.3 (Laravel 13 minimum) |
| MySQL | 8.0+ | 8.0 LTS |
| Redis | 7.0+ | 7.2 |
| Nginx | 1.24+ | Latest stable |
| Node.js | 20 LTS | 20 LTS (for Vite build) |
| RAM | 4 GB | 8 GB |
| CPU | 2 cores | 4 cores |
| Disk | 40 GB SSD | 100 GB SSD |

### PHP Extensions Required

```
php-cli, php-fpm, php-mysql, php-redis, php-curl, php-gd,
php-mbstring, php-xml, php-zip, php-bcmath, php-intl,
php-fileinfo, php-tokenizer, php-json, php-openssl
```

### PHP Configuration

```ini
; php.ini (production)
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 12M
max_input_vars = 5000
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0    ; Disable in production (clear on deploy)
```

---

## 2. Environment Configuration

### `.env` Template (Production)

```bash
APP_NAME=AmazePays
APP_ENV=production
APP_KEY=                      # Generated: php artisan key:generate
APP_DEBUG=false
APP_URL=https://amazepays.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amazepays
DB_USERNAME=amazepays_user
DB_PASSWORD=                  # Strong password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=               # Set in production

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.provider.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@amazepays.com

# Payment Gateways (system-level defaults)
CCAVENUE_MERCHANT_ID=
CCAVENUE_ACCESS_CODE=
CCAVENUE_WORKING_KEY=
CCAVENUE_LINK=https://secure.ccavenue.com/transaction/transaction.do

UNLIMIT_BASE_URL=https://psp.in.unlimit.com/ma-new
UNLIMIT_API_LOGIN=
UNLIMIT_API_PASSWORD=
UNLIMIT_CALLBACK_SECRET=

# Security
IPHUB_API_KEY=                # VPN detection
SENTRY_LARAVEL_DSN=           # Error tracking
```

### Environment Security Rules

- `.env` file is **never committed** to version control
- `.env.example` contains only placeholder values (no real secrets)
- Production secrets managed via server environment or secret manager
- `APP_KEY` backed up securely (required for decrypting all encrypted data)

---

## 3. Development Setup

### Initial Setup

```bash
# Clone repository
git clone <repo-url> amazepays
cd amazepays

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build frontend
npm run dev           # Development (with HMR)
# OR
npm run build         # Production build

# Start development server
php artisan serve

# Start queue worker (separate terminal)
php artisan queue:work

# Start Vite dev server (separate terminal)
npm run dev
```

### Development Tools

```bash
# Clear all caches
php artisan optimize:clear

# Run tests
php artisan test

# Check code style
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse

# Fresh database with seeds
php artisan migrate:fresh --seed
```

---

## 4. Production Deployment

### Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# 1. Pull latest code
git pull origin main

# 2. Install PHP dependencies (production)
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Install Node dependencies and build
npm ci
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache       # If using Blade Icons

# 6. Restart queue workers
php artisan queue:restart

# 7. Restart PHP-FPM
sudo systemctl reload php8.3-fpm

echo "Deployment complete!"
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name amazepays.com;

    root /var/www/amazepays/public;
    index index.php;

    # SSL
    ssl_certificate /etc/ssl/certs/amazepays.crt;
    ssl_certificate_key /etc/ssl/private/amazepays.key;
    ssl_protocols TLSv1.2 TLSv1.3;

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip
    gzip on;
    gzip_types text/css application/javascript application/json;

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    # Deny access to dotfiles
    location ~ /\. {
        deny all;
    }
}

# HTTP to HTTPS redirect
server {
    listen 80;
    server_name amazepays.com;
    return 301 https://$host$request_uri;
}
```

### Zero-Downtime Deployment

For zero-downtime deployments, use Laravel Envoyer or a symlink-based strategy:

```
/var/www/amazepays/
├── releases/
│   ├── 20260407120000/    # Previous release
│   └── 20260407140000/    # Current release
├── shared/
│   ├── .env               # Shared environment
│   ├── storage/           # Shared storage
│   └── node_modules/      # Shared node_modules (optional)
└── current -> releases/20260407140000/   # Symlink
```

---

## 5. CI/CD Pipeline

### GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml
name: CI/CD Pipeline

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: amazepays_test
        ports: ['3306:3306']
      redis:
        image: redis:7
        ports: ['6379:6379']

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mysql, redis, mbstring, xml, bcmath
          coverage: xdebug

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install Node dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Run PHP linting
        run: ./vendor/bin/pint --test

      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse --level=6

      - name: Run tests
        run: php artisan test --coverage-min=60
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: amazepays_test
          DB_USERNAME: root
          DB_PASSWORD: password
          REDIS_HOST: 127.0.0.1

      - name: Security audit
        run: composer audit

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'

    steps:
      - name: Deploy to production
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/amazepays
            bash deploy.sh
```

### Pre-commit Hooks

```bash
# .husky/pre-commit (or similar)
npm run lint
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=6

# Prevent secrets from being committed
git diff --cached --name-only | xargs grep -l "password\|secret\|key" && echo "WARNING: Possible secret detected" && exit 1
```

---

## 6. Database Migrations

### Migration Commands

```bash
# Run pending migrations
php artisan migrate

# Run with force in production
php artisan migrate --force

# Rollback last batch
php artisan migrate:rollback

# Fresh database (DEV ONLY)
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Migration Best Practices

- Always use `--force` flag in CI/CD for production
- Never use `migrate:fresh` in production
- Create rollback migrations for every schema change
- Test migrations on a copy of production data before deploying
- Use `Schema::hasColumn()` checks for safe migrations

---

## 7. Queue & Scheduler

### Laravel Horizon (Queue Dashboard)

```bash
# Install Horizon
composer require laravel/horizon

# Publish config
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"

# Start Horizon (production)
php artisan horizon
```

### Supervisor Configuration

```ini
; /etc/supervisor/conf.d/amazepays-horizon.conf
[program:amazepays-horizon]
process_name=%(program_name)s
command=php /var/www/amazepays/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/amazepays/storage/logs/horizon.log
stopwaitsecs=3600
```

### Task Scheduler

```bash
# Add to crontab
* * * * * cd /var/www/amazepays && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

| Task | Frequency | Description |
|------|-----------|-------------|
| Catalog sync (Woohoo) | Every 4 hours | Fetch and upsert products |
| Catalog sync (others) | Daily at 2 AM | Fetch KGen, VD, Lysto products |
| Provider balance check | Every 30 min | Update provider wallet balances |
| Reconciliation | Daily at 3 AM | Verify wallet balances |
| Pending order cleanup | Every 15 min | Expire orders pending > 30 min |
| Report generation | Weekly Sunday | Generate weekly reports |
| Audit log archival | Monthly | Archive logs older than 6 months |
| Failed job cleanup | Daily | Prune failed jobs older than 30 days |

---

## 8. Monitoring

### Application Monitoring

| Tool | Purpose | Setup |
|------|---------|-------|
| Sentry | Error tracking and performance | `SENTRY_LARAVEL_DSN` in `.env` |
| Laravel Telescope | Debug tool (dev/staging only) | `composer require laravel/telescope` |
| Laravel Horizon | Queue monitoring dashboard | Accessible at `/horizon` |
| Laravel Log Viewer | Application logs | Accessible at `/admin/logs` |

### Health Check Endpoint

```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? true : false,
        'redis'    => Redis::ping() === true,
        'queue'    => Queue::size('default') !== null,
        'storage'  => is_writable(storage_path()),
    ];

    $healthy = !in_array(false, $checks, true);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toISOString(),
    ], $healthy ? 200 : 503);
});
```

### Alerting

| Alert | Condition | Channel |
|-------|-----------|---------|
| Error spike | > 10 errors in 5 min | Email + Slack |
| Queue backlog | > 100 pending jobs | Email |
| Failed jobs | Any failed job | Email |
| Disk space | > 80% used | Email |
| Payment failure rate | > 20% in 1 hour | Email + SMS |
| Provider API down | 3 consecutive failures | Email |

---

## 9. Backup Strategy

### Database Backups

```bash
# Daily automated backup (add to cron)
mysqldump -u amazepays_user -p amazepays \
  --single-transaction \
  --routines \
  --triggers \
  | gzip > /backups/amazepays_$(date +%Y%m%d_%H%M%S).sql.gz

# Retention: 30 daily, 12 weekly, 12 monthly
```

### File Storage Backups

```bash
# Backup uploaded files (proofs, images)
rsync -avz /var/www/amazepays/storage/app/ /backups/storage/
```

### Backup Verification

- Weekly automated restore test to staging
- Verify backup integrity with checksum
- Test database restore procedure quarterly

---

## 10. Scaling

### Horizontal Scaling

```
                    ┌──────────────┐
                    │ Load Balancer│
                    │ (Nginx/HAProxy)
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────┴─────┐┌────┴─────┐┌────┴──────┐
       │  App Server ││App Server││App Server │
       │  (PHP-FPM)  ││(PHP-FPM) ││(PHP-FPM)  │
       └──────┬──────┘└────┬─────┘└────┬──────┘
              │            │            │
              └────────────┼────────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────┴─────┐┌────┴─────┐┌────┴──────┐
       │MySQL Primary││  Redis   ││   Redis   │
       │+ Replica    ││ (Cache)  ││ (Queue)   │
       └─────────────┘└──────────┘└───────────┘
```

### Scaling Triggers

| Metric | Threshold | Action |
|--------|-----------|--------|
| CPU utilization | > 70% sustained | Add app server |
| Response time (p95) | > 2 seconds | Add app server / optimize queries |
| Queue depth | > 500 jobs | Add queue workers |
| DB connections | > 80% of max | Add read replica |
| Redis memory | > 70% of max | Increase Redis instance size |

---

## Related Documents

- [ARCHITECTURE.md](ARCHITECTURE.md) -- System architecture
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Production logging hygiene (no secrets in `storage/logs`)
- [SECURITY.md](SECURITY.md) -- Security configuration
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Migration details
