#!/bin/bash

##############################################################################
# EPSILON DEV RESET SCRIPT
# Resets local database with fresh seeders + generates dump
# Usage: ./reset-dev.sh
##############################################################################

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ERP_DIR="$PROJECT_DIR/erp"
DUMPS_DIR="$PROJECT_DIR/erp/database/dumps"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}== EPSILON DEV RESET ==${NC}\n"

# Step 1: Clear caches
echo -e "${YELLOW}[1/5] Clearing Laravel caches...${NC}"
cd "$ERP_DIR"
php artisan optimize:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan config:clear > /dev/null 2>&1 || true

# Step 2: Check database connection
echo -e "${YELLOW}[2/5] Checking database connection...${NC}"
php artisan tinker << 'EOF' > /dev/null 2>&1
DB::connection()->getPdo();
echo 'OK';
exit;
EOF

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Database connection failed${NC}"
    echo "  Verify .env DB settings:"
    echo "  - DB_HOST=127.0.0.1"
    echo "  - DB_PORT=3306"
    echo "  - DB_DATABASE=epsilon"
    echo "  - DB_USERNAME=root"
    exit 1
fi

echo -e "${GREEN}✓ Database OK${NC}"

# Step 3: Fresh migrate with seed
echo -e "${YELLOW}[3/5] Running migrations and seeders...${NC}"
php artisan migrate:fresh --seed --quiet

echo -e "${GREEN}✓ Database seeded${NC}"

# Step 4: Generate dump
echo -e "${YELLOW}[4/5] Generating SQL dump...${NC}"
mkdir -p "$DUMPS_DIR"

DUMP_FILE="$PROJECT_DIR/epsilon_dev_dump.sql"
DB_NAME=$(grep "^DB_DATABASE" "$ERP_DIR/.env" | cut -d'=' -f2)
DB_USER=$(grep "^DB_USERNAME" "$ERP_DIR/.env" | cut -d'=' -f2)
DB_PASS=$(grep "^DB_PASSWORD" "$ERP_DIR/.env" | cut -d'=' -f2 || echo "")
DB_HOST=$(grep "^DB_HOST" "$ERP_DIR/.env" | cut -d'=' -f2)

if [ -z "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" > "$DUMP_FILE" 2>/dev/null
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DUMP_FILE" 2>/dev/null
fi

if [ -f "$DUMP_FILE" ]; then
    SIZE=$(du -h "$DUMP_FILE" | cut -f1)
    echo -e "${GREEN}✓ Dump created: $DUMP_FILE ($SIZE)${NC}"
else
    echo -e "${YELLOW}⚠ Could not generate dump (mysqldump not available?)${NC}"
fi

# Step 5: Summary
echo ""
echo -e "${YELLOW}[5/5] Summary${NC}"
echo -e "${GREEN}✓ Local dev database reset successfully${NC}"
echo ""
echo "Database: $DB_NAME"
echo "Backend: http://127.0.0.1:8000"
echo "Frontend: http://127.0.0.1:5173"
echo ""
echo "Test data users:"
echo "  admin@epsilon-erp.local / Admin@Epsilon2026"
echo "  yves.sales@epsilon-erp.local / Sales@Epsilon2026"
echo ""
echo "Next steps:"
echo "  1. Backend:  cd erp && php artisan serve"
echo "  2. Frontend: cd frontend && npm run dev"
echo ""
echo -e "${GREEN}✓ Done!${NC}"
