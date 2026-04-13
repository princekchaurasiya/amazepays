# Database Setup Guide

## Problem
The application is trying to connect to database `amazepays` with user `amazepays`, but your current database is `laravel_app`.

## Required Database Tables
Based on the HomePageController, the application needs these tables:
- `amazepay_categories` - Product categories
- `storefront_brands` - Storefront / homepage brands (logos, brand landing pages)  
- `products` - Products (synced catalog)
- `slides` - Homepage slides
- `home` - Homepage settings
- Plus many other tables from migrations

## Solution Options

### Option 1: Import Full Database Backup (Recommended - Has Data)
If you want to use the existing backup with data:

1. **Create the database:**
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS amazepays CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

2. **Import the backup:**
```bash
mysql -u root -p amazepays < public/uat_amazepays_db_backup_2May25.sql
```

3. **Create database user (if needed):**
```bash
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'amazepays'@'localhost' IDENTIFIED BY 'your_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON amazepays.* TO 'amazepays'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

4. **Update your `.env` file:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amazepays
DB_USERNAME=amazepays
DB_PASSWORD=your_password
```

### Option 2: Run Migrations (Fresh Database - No Data)
If you want a fresh database with only table structure:

1. **Create the database:**
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS amazepays CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

2. **Create database user (if needed):**
```bash
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'amazepays'@'localhost' IDENTIFIED BY 'your_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON amazepays.* TO 'amazepays'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

3. **Update your `.env` file:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amazepays
DB_USERNAME=amazepays
DB_PASSWORD=your_password
```

4. **Run migrations:**
```bash
php artisan migrate
```

## Verify Setup

After setup, test the connection:
```bash
php artisan tinker
```
Then in tinker:
```php
DB::connection()->getPdo();
// Should return: PDO object without errors
```

## Current Issue
- Your `.env` file likely has `DB_DATABASE=amazepays` and `DB_USERNAME=amazepays`
- But the database `amazepays` doesn't exist or the user doesn't have access
- The database `laravel_app` exists but doesn't have the required tables

## Quick Fix Commands

If you want to use the existing `laravel_app` database, update `.env`:
```env
DB_DATABASE=laravel_app
DB_USERNAME=root  # or your MySQL username
DB_PASSWORD=      # your MySQL password
```

Then run migrations:
```bash
php artisan migrate
```
