#!/bin/bash

# Database Setup Script for Gift Giggles
# This script helps set up the database for the application

echo "========================================="
echo "Database Setup for Gift Giggles"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get MySQL root password
read -sp "Enter MySQL root password: " ROOT_PASSWORD
echo ""

# Get database name
read -p "Enter database name (default: amazepays): " DB_NAME
DB_NAME=${DB_NAME:-amazepays}

# Get database username
read -p "Enter database username (default: amazepays): " DB_USER
DB_USER=${DB_USER:-amazepays}

# Get database password
read -sp "Enter database password for user '$DB_USER': " DB_PASSWORD
echo ""

echo ""
echo "Creating database '$DB_NAME'..."
mysql -u root -p"$ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database '$DB_NAME' created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create database. Please check your MySQL root password.${NC}"
    exit 1
fi

echo ""
echo "Creating user '$DB_USER'..."
mysql -u root -p"$ROOT_PASSWORD" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ User '$DB_USER' created successfully${NC}"
else
    echo -e "${YELLOW}⚠ User might already exist, continuing...${NC}"
fi

echo ""
echo "Granting privileges..."
mysql -u root -p"$ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';" 2>/dev/null
mysql -u root -p"$ROOT_PASSWORD" -e "FLUSH PRIVILEGES;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Privileges granted successfully${NC}"
else
    echo -e "${RED}✗ Failed to grant privileges${NC}"
    exit 1
fi

echo ""
echo "========================================="
echo "Choose setup method:"
echo "========================================="
echo "1. Import full backup (has data) - Recommended"
echo "2. Run migrations (fresh database, no data)"
echo ""
read -p "Enter choice (1 or 2): " CHOICE

if [ "$CHOICE" = "1" ]; then
    echo ""
    echo "Importing database backup..."
    if [ -f "public/uat_amazepays_db_backup_2May25.sql" ]; then
        mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < public/uat_amazepays_db_backup_2May25.sql 2>/dev/null
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Database backup imported successfully${NC}"
        else
            echo -e "${RED}✗ Failed to import backup${NC}"
            exit 1
        fi
    else
        echo -e "${RED}✗ Backup file not found: public/uat_amazepays_db_backup_2May25.sql${NC}"
        exit 1
    fi
elif [ "$CHOICE" = "2" ]; then
    echo ""
    echo "Running migrations..."
    php artisan migrate --force
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Migrations completed successfully${NC}"
    else
        echo -e "${RED}✗ Migrations failed${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Invalid choice${NC}"
    exit 1
fi

echo ""
echo "========================================="
echo "Update your .env file with:"
echo "========================================="
echo "DB_CONNECTION=mysql"
echo "DB_HOST=127.0.0.1"
echo "DB_PORT=3306"
echo "DB_DATABASE=$DB_NAME"
echo "DB_USERNAME=$DB_USER"
echo "DB_PASSWORD=$DB_PASSWORD"
echo ""
echo -e "${GREEN}Setup complete!${NC}"
