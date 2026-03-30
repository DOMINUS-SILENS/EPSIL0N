#!/bin/bash
#
# EPSILON Database Dump Script
# Generates timestamped SQL dumps with optional compression
#
# Usage:   ./dump-db.sh [--compress] [--restore <dump-file>]
# Examples:
#   ./dump-db.sh                    # Create dump (uncompressed)
#   ./dump-db.sh --compress         # Create dump (gzipped)
#   ./dump-db.sh --restore epsilon_erp_20260327_120000.sql.gz

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DUMP_DIR="$PROJECT_DIR/database/dumps"
DB_NAME="${DB_DATABASE:=epsilon_erp}"
DB_USER="${DB_USERNAME:=root}"
DB_HOST="${DB_HOST:=127.0.0.1}"

mkdir -p "$DUMP_DIR"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Dump database
dump_database() {
    local TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    local DUMP_FILE="$DUMP_DIR/${DB_NAME}_${TIMESTAMP}.sql"
    local COMPRESS=${1:=false}

    log_info "Connecting to database: $DB_NAME on $DB_HOST"
    log_info "Generating dump: $DUMP_FILE"

    mysqldump \
        -h "$DB_HOST" \
        -u "$DB_USER" -p \
        --routines \
        --triggers \
        --events \
        --single-transaction \
        --default-character-set=utf8mb4 \
        --quick \
        --lock-tables=false \
        "$DB_NAME" > "$DUMP_FILE"

    if [ $? -eq 0 ]; then
        log_success "Dump created"

        if [ "$COMPRESS" = "true" ]; then
            log_info "Compressing..."
            gzip -f "$DUMP_FILE"
            log_success "Dump compressed: ${DUMP_FILE}.gz"
            echo -e "\n${GREEN}Finalfile:${NC} ${DUMP_FILE}.gz"
            ls -lh "${DUMP_FILE}.gz"
        else
            echo -e "\n${GREEN}Final file:${NC} $DUMP_FILE"
            ls -lh "$DUMP_FILE"
        fi
    else
        log_error "Dump failed!"
        exit 1
    fi
}

# Restore database
restore_database() {
    local DUMP_FILE="$1"

    if [ ! -f "$DUMP_FILE" ]; then
        # Try to find with .gz
        if [ -f "${DUMP_FILE}.gz" ]; then
            DUMP_FILE="${DUMP_FILE}.gz"
        else
            log_error "Dump file not found: $DUMP_FILE"
            exit 1
        fi
    fi

    log_info "Restoring from: $DUMP_FILE"
    log_warning "This will overwrite existing data in: $DB_NAME"
    read -p "Continue? (yes/no): " -r

    if [[ $REPLY != "yes" ]]; then
        log_info "Cancelled"
        exit 0
    fi

    if [[ "$DUMP_FILE" == *.gz ]]; then
        log_info "Decompressing and importing..."
        gunzip -c "$DUMP_FILE" | mysql -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME"
    else
        log_info "Importing..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" < "$DUMP_FILE"
    fi

    if [ $? -eq 0 ]; then
        log_success "Database restored successfully"
    else
        log_error "Restore failed!"
        exit 1
    fi
}

# List dumps
list_dumps() {
    log_info "Database dumps in: $DUMP_DIR"
    if [ -d "$DUMP_DIR" ]; then
        ls -lhS "$DUMP_DIR"/*.sql* 2>/dev/null || log_warning "No dumps found"
    else
        log_warning "Dump directory doesn't exist: $DUMP_DIR"
    fi
}

# Main
case "${1:-}" in
    --compress)
        dump_database "true"
        ;;
    --restore)
        restore_database "$2"
        ;;
    --list)
        list_dumps
        ;;
    --help)
        cat <<EOF
EPSILON Database Dump Script

Usage:
    $0 [command] [options]

Commands:
    (default)           Create uncompressed dump
    --compress          Create gzipped dump
    --restore <file>    Restore from dump file
    --list              List all dumps
    --help              Show this help

Environment variables:
    DB_DATABASE         Database name (default: epsilon_erp)
    DB_USERNAME         Database user (default: root)
    DB_HOST             Database host (default: 127.0.0.1)

Examples:
    $0                              # Create dump
    $0 --compress                   # Create compressed dump
    $0 --restore epsilon_erp_*.sql  # Restore dump
    $0 --list                       # List available dumps

EOF
        ;;
    *)
        dump_database "false"
        ;;
esac
