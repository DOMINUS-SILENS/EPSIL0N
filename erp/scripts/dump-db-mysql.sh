#!/usr/bin/env bash
##############################################################################
# EPSILON MySQL Database Dump Script
# Generates portable SQL dumps for MySQL database
# Requirements: mysql/mysqldump installed
##############################################################################

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DUMP_DIR="$PROJECT_DIR/database/dumps"

# Load .env config
if [ -f "$PROJECT_DIR/.env" ]; then
    source <(grep "^DB_" "$PROJECT_DIR/.env" | sed 's/^/export /')
fi

# Use defaults or .env values
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-crm_db}"
DB_USERNAME="${DB_USERNAME:-root}"

# Create dumps directory
mkdir -p "$DUMP_DIR"

# Generate timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DUMP_FILE="$DUMP_DIR/epsilon_mysql_${TIMESTAMP}.sql"

echo "[INFO] Dumping MySQL database: $DB_DATABASE"
echo "[INFO] Host: $DB_HOST:$DB_PORT"
echo "[INFO] Output: $DUMP_FILE"
echo ""

# Prompt for password if not set
if [ -z "$DB_PASSWORD" ]; then
    read -sp "MySQL password for $DB_USERNAME: " DB_PASSWORD
    echo ""
fi

# Dump with mysqldump
mysqldump \
    -h "$DB_HOST" \
    -P "$DB_PORT" \
    -u "$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    --routines \
    --triggers \
    --events \
    --single-transaction \
    --default-character-set=utf8mb4 \
    --result-file="$DUMP_FILE" \
    "$DB_DATABASE" && {

    echo "[OK] Dump created: $DUMP_FILE"

    # Compress
    gzip -f "$DUMP_FILE"
    echo "[OK] Compressed: ${DUMP_FILE}.gz"
    echo ""
    echo "✓ Dump ready for distribution/backup"
} || {
    echo "[ERROR] Failed to create dump"
    exit 1
}
