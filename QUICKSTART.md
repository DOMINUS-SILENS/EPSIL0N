# ⚡ EPSILON DEV KIT - QUICK START

> **Everything you need for stable local development**

---

## 📦 What You Have Now

```
EPSILON/
├── reset-dev.sh                    ← One-click database reset
├── epsilon_dev_dump.sql            ← Database export (after first setup)
├── DEV_KIT_GUIDE.md               ← Complete development guide
├── MYSQL_SETUP.md                 ← MySQL configuration guide
├── DATABASE_DUMPS_GUIDE.md        ← Advanced dump/restore guide
│
├── erp/
│   ├── .env                       ← Updated for local dev
│   ├── database/
│   │   ├── migrations/            ← 13+ database migrations
│   │   └── seeders/               ← 19 seeder files
│   ├── app/Console/Commands/
│   │   └── DumpDatabase.php       ← New artisan command
│   └── ...rest of Laravel app...
│
└── frontend/
    └── ...React app...
```

---

## 🚀 IMMEDIATE SETUP (5 minutes)

### 1. Setup MySQL Database

```bash
# Create database
mysql -u root -e "CREATE DATABASE epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Or with password
mysql -u root -pYOUR_PASSWORD -e "CREATE DATABASE epsilon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Check .env is Correct

```bash
cd erp

# Should show:
# DB_DATABASE=epsilon
# DB_USERNAME=root
# DB_PASSWORD= (empty or your local password)

cat .env | grep DB_
```

### 3. Seed Database

```bash
cd erp
php artisan migrate:fresh --seed
```

### 4. Generate Dump

```bash
# Back to EPSILON root
cd ..

mysqldump -u root epsilon > epsilon_dev_dump.sql
```

**Done!** You now have a seedable, dumpable database.

---

## ⚙️ DAILY WORKFLOW

### Start Dev Environment

**Terminal 1 - Backend**:
```bash
cd erp
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 - Frontend**:
```bash
cd frontend
npm run dev -- --host 127.0.0.1 --port 5173
```

**Terminal 3 - Optional (tests)**:
```bash
cd erp
php artisan test --watch
```

---

### Login

```
URL: http://127.0.0.1:5173
Email: admin@epsilon-erp.local
Pass:  Admin@Epsilon2026
```

---

### Reset Database (Anytime)

```bash
# Option 1: Quick reset
./reset-dev.sh

# Option 2: From dump
mysql -u root epsilon < epsilon_dev_dump.sql

# Option 3: Full rebuild
cd erp && php artisan migrate:fresh --seed
```

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| **DEV_KIT_GUIDE.md** | Full development workflow & troubleshooting |
| **MYSQL_SETUP.md** | Step-by-step MySQL configuration |
| **DATABASE_DUMPS_GUIDE.md** | Advanced dump/seed operations |
| **erp/DATABASE_DUMPS_GUIDE.md** | Laravel dump commands |

---

## ✅ Verify Everything Works

```bash
# Backend health
cd erp
php artisan tinker
>>> DB::select('SELECT 1');   # Should return [{'1': 1}]
>>> exit;

# Frontend
cd frontend
npm run dev
# Navigate to http://127.0.0.1:5173, login works?

# Tests
cd erp
php artisan test
# All tests pass?
```

---

## 🔄 Common Tasks

### Reset Database to Factory State

```bash
./reset-dev.sh
# Or
mysql -u root epsilon < epsilon_dev_dump.sql
```

### Add New Seeder Data

Edit seeders in `erp/database/seeders/` then:

```bash
cd erp
php artisan db:seed --class=YourSeederName
```

### Export Current Database State

```bash
mysqldump -u root epsilon > epsilon_dev_backup_$(date +%Y%m%d).sql
```

### Run Tests

```bash
cd erp
php artisan test              # All tests
php artisan test --filter=OrderTest  # Specific test
php artisan test --watch      # Watch mode
```

### Check Routes

```bash
cd erp
php artisan route:list | grep -i "api"
```

### Check Database Tables

```bash
mysql -u root epsilon -e "SHOW TABLES;"
mysql -u root epsilon -e "SELECT COUNT(*) FROM users;"
```

---

## 🛠️ Troubleshooting

### "Database connection failed"

```bash
# Fix: Verify MySQL credentials in .env
cat erp/.env | grep DB_

# Test MySQL directly
mysql -u root -e "SELECT VERSION();"
```

### "Port 8000 already in use"

```bash
# Kill existing process
lsof -i :8000 | grep LISTEN | awk '{print $2}' | xargs kill -9

# Or use different port
php artisan serve --port 8001
```

### "Seeder failed / Table doesn't exist"

```bash
# Fix: Ensure migrations run first
cd erp
php artisan migrate
php artisan db:seed
```

### "Frontend can't reach backend API"

```bash
# Verify CORS in erp/config/cors.php
# Verify FRONTEND_URL in .env
cat erp/.env | grep FRONTEND

# Check backend is running on 8000
curl http://127.0.0.1:8000/api/auth/me  # Should get 401 (unauthenticated)
```

---

## 🎯 Next Steps

1. **Setup MySQL** (see MYSQL_SETUP.md)
2. **Run `./reset-dev.sh`** once
3. **Start backend & frontend** in separate terminals
4. **Login and test UI**
5. **Make changes** - hot reload works for both
6. **Run tests** when needed
7. **Export dump** when you have good test data

---

## 📊 Project Status

| Component | Status |
|-----------|--------|
| Backend (Laravel 11) | ✅ Ready |
| Frontend (React 18) | ✅ Ready |
| Database (MySQL) | ✅ Configured |
| Seeders | ✅ 19 files, production data |
| Tests | ✅ 26 test files |
| Auth | ✅ Sanctum + session |
| API Routes | ✅ RESTful + SSE |
| **DEV READY** | **✅ YES** |

---

## 💡 Pro Tips

1. **Keep `epsilon_dev_dump.sql` updated** - Update it regularly as you change data
2. **Use seeders for reproducible data** - Don't manually insert test data
3. **Clear caches before testing** - `php artisan optimize:clear`
4. **Check `.env` matches your local setup** - DB credentials, URLs
5. **Use `php artisan tinker`** - Great for debugging queries
6. **Watch mode for tests** - `php artisan test --watch`

---

## 📞 Support

- See **DEV_KIT_GUIDE.md** for detailed troubleshooting
- Check **MYSQL_SETUP.md** for database issues
- See **DATABASE_DUMPS_GUIDE.md** for advanced dump operations

---

**EPSILON is ready for local development!** 🎉

Start with: `./reset-dev.sh`
