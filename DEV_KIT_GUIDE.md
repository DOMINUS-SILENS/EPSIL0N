# 🔧 EPSILON DEV KIT - Local Development Guide

> **Local dev environment setup & workflow for EPSILON ERP**

---

## 📋 Quick Start

```bash
# Reset local database with fresh seeders
./reset-dev.sh

# Backend (Terminal 1)
cd erp
php artisan serve --host=127.0.0.1 --port=8000

# Frontend (Terminal 2)
cd frontend
npm run dev -- --host 127.0.0.1 --port 5173
```

**Access**:
- Backend API: http://127.0.0.1:8000
- Frontend: http://127.0.0.1:5173

---

## 👤 Test Credentials

```
Email:    admin@epsilon-erp.local
Password: Admin@Epsilon2026

Or other users:
  yves.sales@epsilon-erp.local / Sales@Epsilon2026
  marie.stock@epsilon-erp.local / Stock@Epsilon2026
  pierre.delivery@epsilon-erp.local / Delivery@Epsilon2026
```

---

## 🗂️ Project Structure

```
EPSILON/
├── reset-dev.sh                      ← Main reset script
├── epsilon_dev_dump.sql              ← Exportable database dump
├── erp/                              ← Laravel backend
│   ├── .env                          ← Local config
│   ├── app/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
└── frontend/                         ← React frontend
    ├── .env
    ├── src/
    └── package.json
```

---

## 🔧 Configuration

### Backend `.env`

```env
APP_NAME=EPSILON
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=epsilon
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SANCTUM_STATEFUL_DOMAINS=127.0.0.1:5173,localhost:5173
FRONTEND_URL=http://127.0.0.1:5173
```

### Frontend `.env`

```env
VITE_API_URL=http://127.0.0.1:8000/api
VITE_APP_URL=http://127.0.0.1:5173
VITE_SSE_URL=http://127.0.0.1:8000
```

---

## 🔄 Workflow

### 1. Fresh Start

```bash
# Reset everything
./reset-dev.sh
```

This will:
- ✓ Clear Laravel caches
- ✓ Check MySQL connection
- ✓ Run fresh migrations + seeders
- ✓ Export `epsilon_dev_dump.sql`

### 2. Start Development

```bash
# Terminal 1: Backend
cd erp
php artisan serve

# Terminal 2: Frontend
cd frontend
npm run dev
```

### 3. Make Changes

Edit code freely. Both Laravel and Vite have hot-reload.

### 4. Run Tests

```bash
# Backend
cd erp
php artisan test

# Frontend (if applicable)
cd frontend
npm run test
```

### 5. Reset Database

```bash
# Restore from dump
mysql -u root epsilon < epsilon_dev_dump.sql

# Or full reset
./reset-dev.sh
```

---

## 🌱 Seeders

**Active seeders** (DatabaseSeeder):

1. `EpsilonCoreSeeder` - Users + core setup
2. `EntrepriseDepotSeeder` - Companies + warehouses
3. `ProductCatalogSeeder` - Articles + catalog
4. `CustomerOrderSeeder` - Clients + orders
5. `InventorySeeder` - Stock movements

**Optional seeders** (can be added to DatabaseSeeder):

- ArticleFamilleSeeder
- ArticleMarqueSeeder
- DeliveryTourSeeder
- LeadSeeder
- PreSalesSeeder
- SalesSeeder
- StockMovementSeeder
- TradeMarketingSeeder
- FleetSeeder

---

## 💾 Database Backup & Restore

### Export current database

```bash
# Automatic (via reset-dev.sh)
./reset-dev.sh

# Manual dump
mysqldump -u root epsilon > epsilon_dev_dump.sql

# Compressed
mysqldump -u root epsilon | gzip > epsilon_dev_dump.sql.gz
```

### Restore dump

```bash
# From .sql
mysql -u root epsilon < epsilon_dev_dump.sql

# From .sql.gz
gunzip -c epsilon_dev_dump.sql.gz | mysql -u root epsilon
```

### Share dump

```bash
# Create versioned dump
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mysqldump -u root epsilon > epsilon_dev_dump_${TIMESTAMP}.sql

# Keep it for distribution
cp epsilon_dev_dump_${TIMESTAMP}.sql shared/
```

---

## 🐛 Troubleshooting

### "Database connection failed"

```bash
# Verify MySQL is running
mysql -u root -e "SELECT 1;"

# Check .env
cat erp/.env | grep DB_

# Verify credentials in .env match your MySQL setup
```

### "Seeder failed"

```bash
# Check seeders are syntactically correct
cd erp
php artisan tinker
>>> include('database/seeders/ProductCatalogSeeder.php');

# Or just migrate fresh
php artisan migrate:fresh --seed
```

### "Port 8000 already in use"

```bash
# Change port
php artisan serve --port=8001

# Or kill the process
lsof -i :8000
kill -9 <PID>
```

### "Node modules missing"

```bash
cd frontend
npm install
npm run dev
```

---

## 📊 Database Schema

Main tables seeded:

```
users              - Auth users
entreprise         - Companies/Organizations
depot              - Warehouses/Depots
article            - Products/Items
article_mouvement  - Stock movements
client             - Customers
commande           - Sales orders
mission            - Field missions (SFA)
lead               - CRM leads
```

---

## 🎯 Development Checklist

- [ ] MySQL running on 127.0.0.1:3306
- [ ] Database `epsilon` created and accessible as `root`
- [ ] `.env` files configured locally
- [ ] `./reset-dev.sh` runs without errors
- [ ] Backend starts: `php artisan serve`
- [ ] Frontend starts: `npm run dev`
- [ ] Login works with test credentials
- [ ] API calls return 200/expected status
- [ ] Tests pass: `php artisan test`

---

## 📚 Useful Commands

```bash
# Backend
cd erp
php artisan migrate               # Run migrations
php artisan migrate:fresh         # Reset migrations
php artisan db:seed              # Run seeders
php artisan migrate:fresh --seed # Fresh + seed
php artisan test                 # Run tests
php artisan tinker               # Interactive CLI

# Frontend
cd frontend
npm install                      # Install deps
npm run dev                      # Dev server
npm run build                    # Production build
npm run test                     # Run tests

# Database
mysql -u root -p epsilon         # MySQL CLI
mysqldump -u root epsilon > dump.sql  # Create dump
mysql -u root epsilon < dump.sql      # Restore dump
```

---

## 🔗 Useful Endpoints

```
GET  /api/auth/me              # Current user
POST /api/auth/login           # Login
POST /api/auth/logout          # Logout
GET  /api/auth/csrf-token      # CSRF token

GET  /api/events/stream        # SSE stream (with token)

GET  /api/articles             # Products list
GET  /api/depots               # Warehouses list
GET  /api/customers            # Clients list
```

See `erp/routes/api.php` for complete list.

---

## 📝 Notes

- **APP_DEBUG=true** is normal for local development
- **DATABASE_URL** in .env is overridden by individual DB_* vars
- **Seeders are non-destructive** (use `insertOrIgnore()`)
- **Migration order matters** - foreignKey constraints depend on migration sequence

---

## 🚀 Next Steps

1. Run `./reset-dev.sh` once to initialize
2. Start backend and frontend in separate terminals
3. Login and test the UI
4. Export dump for backup: `./reset-dev.sh` (automatic)
5. Continue development with hot-reload

---

**Created**: 2026-03-27
**Version**: 1.0
**Status**: Local Development Ready
