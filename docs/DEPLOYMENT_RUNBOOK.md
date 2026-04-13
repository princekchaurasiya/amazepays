# Production Deployment Runbook

> Follow this checklist step-by-step for initial production go-live.  
> Estimated time: 4-6 hours including UAT.

---

## Pre-Deployment Checklist (T-48h)

- [ ] All feature tests passing (`php artisan test`)
- [ ] Security pen test completed, all critical/high findings fixed
- [ ] Load test run (k6 / Artillery) — response times acceptable under 100 concurrent users
- [ ] UAT sign-off from stakeholders (B2C flow, B2B flow, admin panel)
- [ ] Staging deployment tested end-to-end
- [ ] All external dependencies active: MSG91, IPHub, Razorpay, Woohoo sandbox
- [ ] Database backup of CURRENT production taken
- [ ] Rollback plan documented and tested on staging

---

## Server Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 8.2 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Redis | 6.0 | 7.0 |
| Nginx | 1.20 | Latest |
| RAM | 4 GB | 8 GB |
| CPU | 2 vCPU | 4 vCPU |
| Disk | 50 GB SSD | 100 GB SSD |

---

## Step 1: Server Setup

```bash
# Install PHP 8.2 + extensions
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.2-fpm php8.2-mysql php8.2-redis php8.2-gd php8.2-curl \
    php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl php8.2-mbstring

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

# Install Redis
sudo apt install redis-server
sudo systemctl enable redis-server

# Install Nginx
sudo apt install nginx
sudo systemctl enable nginx
```

---

## Step 2: Application Deployment

```bash
# Clone / pull code
cd /var/www
git clone https://github.com/your-org/amazepays.git amazepays
cd amazepays
git checkout main

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install and build frontend
npm ci
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure .env with all production values (see PHASE_0_ACTIONS.md)
nano .env
```

---

## Step 3: Database Migration (MAINTENANCE WINDOW)

```bash
# Put app in maintenance mode
php artisan down --secret="your-maintenance-bypass-token"

# Run all migrations (includes table renames)
php artisan migrate --force

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder --force

# Assign super-admin role to your admin user
php artisan tinker
>>> $user = User::where('email', 'admin@amazepays.com')->first();
>>> $user->assignRole('super-admin');
```

**CRITICAL: Table rename migration will run for ~30-60 seconds on large tables. Schedule a 5-minute maintenance window.**

---

## Step 4: Nginx Configuration

```nginx
# /etc/nginx/sites-available/amazepays
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/amazepays/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Security headers (supplemented by Laravel SecurityHeaders middleware)
    add_header X-Real-IP $remote_addr;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny direct access to sensitive files
    location ~ /\.(env|git|htaccess) {
        deny all;
    }

    # Increase body size for payment proof uploads
    client_max_body_size 10M;

    # Gzip
    gzip on;
    gzip_types text/plain application/json application/javascript text/css;
}

server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## Step 5: Supervisor Configuration

```ini
# /etc/supervisor/conf.d/amazepays-horizon.conf
[program:amazepays-horizon]
process_name=%(program_name)s
command=php /var/www/amazepays/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/amazepays-horizon.log
stopwaitsecs=3600

# /etc/supervisor/conf.d/amazepays-queue.conf
[program:amazepays-security-queue]
process_name=%(program_name)s
command=php /var/www/amazepays/artisan queue:work redis --queue=security --tries=1 --timeout=30
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/amazepays-security-queue.log
```

---

## Step 6: Scheduled Tasks (Crontab)

```bash
# Add to /etc/cron.d/amazepays or www-data crontab
* * * * * www-data cd /var/www/amazepays && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled tasks in `routes/console.php`:**

```php
Schedule::command('voucher:sync-catalog')
    ->dailyAt('02:00')
    ->onOneServer();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes();

// Clean expired blocked IPs
Schedule::call(fn () => \App\Models\BlockedIp::active()->expired()->delete())
    ->hourly();
```

---

## Step 7: Post-Deployment Verification

```bash
# Bring app back up
php artisan up

# Verify all routes work
curl https://yourdomain.com/api/v1/health

# Verify admin panel
curl -I https://yourdomain.com/panel

# Verify Redis
php artisan tinker
>>> Cache::put('test', 'ok', 60)
>>> Cache::get('test')  # Should return 'ok'

# Verify queue
php artisan queue:monitor redis:default,redis:security

# Verify Horizon
# Visit https://yourdomain.com/horizon (admin only)

# Run smoke test
php artisan test --testsuite=Feature --filter=AuthTest
```

---

## Step 8: Security Hardening Post-Deploy

```bash
# Set proper file permissions
sudo chown -R www-data:www-data /var/www/amazepays
sudo find /var/www/amazepays -type f -exec chmod 644 {} \;
sudo find /var/www/amazepays -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache

# Protect sensitive directories
sudo chmod 600 .env

# Configure PHP-FPM security
# /etc/php/8.2/fpm/php.ini
# expose_php = Off
# display_errors = Off
# log_errors = On
```

---

## Rollback Plan

If critical issues are found post-deployment:

```bash
# 1. Put app in maintenance mode
php artisan down

# 2. Restore database from pre-upgrade backup
mysql -u root -p amazepays_db < backup_pre_upgrade.sql

# 3. Checkout previous code
git checkout v1-stable  # or previous tag

# 4. Install previous dependencies
composer install --no-dev

# 5. Run old frontend build (if cached)
npm run build

# 6. Bring app back up
php artisan up
```

---

## Monitoring Setup

1. **Sentry** — Set `SENTRY_LARAVEL_DSN` in `.env`
2. **Laravel Horizon** — Monitor queue health at `/horizon`
3. **Security Dashboard** — Monitor at `/panel/security`
4. **Server monitoring** — Set up Uptime Robot or Better Uptime alerts for:
   - `https://yourdomain.com/up` (200 check)
   - `/api/v1/health` (200 check)
   - Response time alert > 2000ms

---

## UAT Checklist

### B2C Flow
- [ ] User registers with OTP
- [ ] User logs in
- [ ] Browse catalog
- [ ] Place order via wallet
- [ ] View voucher code (require PIN if enabled)
- [ ] Wallet load request
- [ ] 2FA setup and verification

### B2B Flow
- [ ] Tenant login
- [ ] Place bulk order
- [ ] Maker-checker for high-value order
- [ ] Wallet load request with UTR
- [ ] API key generation
- [ ] Reseller API order via API key + HMAC

### Admin Flow
- [ ] Admin login + 2FA
- [ ] Product CRUD
- [ ] Order management
- [ ] Wallet load approval
- [ ] Security dashboard — view events, block/unblock IP
- [ ] Offer creation and application
- [ ] Audit log review

---

## See also

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Production-safe logging (no payment/OTP secrets in `storage/logs`)
- [SECURITY.md](SECURITY.md) -- Webhook and API hardening
