# 🗄️ EPSILON MySQL Setup Guide

> Step-by-step setup for local EPSILON development database

---

## Prerequisites

- MySQL 8.0+ installed and running
- Access to MySQL CLI (`mysql` command)
- `mysqldump` available (comes with MySQL)

---

## Step 1: Create Database

```bash
# Connect to MySQL as root
mysql -u root -p

# Then inside MySQL:
CREATE DATABASE epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Verify
SHOW DATABASES;
```

If MySQL has no password:
```bash
mysql -u root -e "CREATE DATABASE epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## Step 2: Verify Connection from Laravel

```bash
cd erp

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT 1'); // Should return array with 1
>>> exit;
```

If connection fails:
```bash
# Check .env values
cat .env | grep DB_

# Edit as needed
nano .env
```

---

## Step 3: Run Migrations and Seeders

```bash
cd erp

# Generate app key if needed
php artisan key:generate

# Clear caches
php artisan optimize:clear

# Fresh migrations + seed
php artisan migrate:fresh --seed
```

Expected output:
```
   INFO  Running migrations:

  Migration_name ............ 50ms DONE
  ...more migrations...

   INFO  Seeding database:

  Database\Seeders\DatabaseSeeder [====...====] xx ms
```

---

## Step 4: Verify Data Was Seeded

```bash
# Check users were created
mysql -u root epsilon -e "SELECT COUNT(*) as user_count FROM users;"

# Should return: 4 users (admin, yves, marie, pierre)
```

---

## Step 5: Generate Dev Dump

```bash
# Once data is seeded, create a dump
mysqldump -u root epsilon > epsilon_dev_dump.sql

# Verify dump was created
ls -lh epsilon_dev_dump.sql

# Optional: compress it
gzip -c epsilon_dev_dump.sql > epsilon_dev_dump.sql.gz
```

---

## Step 6: Test Restore

```bash
# Drop test (CAREFUL!)
mysql -u root -e "DROP DATABASE epsilon;"

# Recreate and restore from dump
mysql -u root -e "CREATE DATABASE epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root epsilon < epsilon_dev_dump.sql

# Verify restoration
mysql -u root epsilon -e "SELECT COUNT(*) as articles FROM article;"
```

---

## 🔧 Troubleshooting

### "ERROR 1045: Access denied for user 'root'@'localhost'"

MySQL is running but root needs a password:

```bash
# Find your password or reset it
mysql -u root -p    # Type password when prompted

# Or, if password is empty:
mysql -u root
```

### "ERROR 1064: MySQL syntax error"

Your `mysqldump` version might differ. Try:

```bash
# Without advanced options (simpler dump)
mysqldump -u root epsilon > epsilon_dev_dump.sql

# Or with full options
mysqldump -u root --routines --triggers --single-transaction epsilon > epsilon_dev_dump.sql
```

### "Database 'epsilon' doesn't exist"

Create it:

```bash
mysql -u root -e "CREATE DATABASE epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Migrations fail

```bash
# Check what tables exist
mysql -u root epsilon -e "SHOW TABLES;"

# If empty, run migrations manually
cd erp
php artisan migrate
```

---

## 📋 Full Setup Script (Alternative)

Save as `setup-mysql.sh`:

```bash
#!/bin/bash

echo "Creating EPSILON database..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Running migrations and seeders..."
cd erp
php artisan migrate:fresh --seed

echo "Generating dump..."
mysqldump -u root epsilon > epsilon_dev_dump.sql

echo "Done!"
ls -lh epsilon_dev_dump.sql
```

Then:
```bash
chmod +x setup-mysql.sh
./setup-mysql.sh
```

---

## ✅ Verification Checklist

- [ ] MySQL is running
- [ ] Database `epsilon` exists
- [ ] `.env` has correct DB_ values
- [ ] `php artisan migrate:fresh --seed` completes
- [ ] Users table has 4 users
- [ ] Articles table has 4+ products
- [ ] `mysqldump` works and creates `.sql` file
- [ ] Restore from dump works

---

## 📚 Useful MySQL Commands

```bash
# Connect to database
mysql -u root epsilon

# Show all tables
SHOW TABLES;

# Count records in a table
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM article;

# Show table structure
DESCRIBE users;
DESCRIBE article;

# Delete all data (CAREFUL!)
TRUNCATE TABLE users;

# Exit
exit;
```

---

## 🎯 Once Setup is Complete

1. You should have `epsilon_dev_dump.sql` in EPSILON folder
2. Backend can start: `php artisan serve`
3. Frontend can start: `npm run dev`
4. You can reset anytime: `mysql -u root epsilon < epsilon_dev_dump.sql`

---

**Next**: See `DEV_KIT_GUIDE.md` for full development workflow

