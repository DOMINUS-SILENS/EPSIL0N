#!/bin/bash
# PostgreSQL Database Setup Script for EPSILONE Kernel Testing
# Run this script to create the test database and schema

set -e

echo "Setting up EPSILONE Kernel PostgreSQL database..."

# Database configuration
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USER="${DB_USER:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-password}"
DB_NAME="${DB_DATABASE:-epsilone_kernel}"

# Create database
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d postgres <<EOF
-- Create database if it doesn't exist
SELECT 'CREATE DATABASE $DB_NAME'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME')\gexec
EOF

echo "Database '$DB_NAME' created (or already exists)"

# Create schema
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="$SCRIPT_DIR/../resources/sql/event_store/001_create_event_store.sql"

if [ -f "$SCHEMA_FILE" ]; then
    echo "Applying schema from $SCHEMA_FILE..."
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$SCHEMA_FILE"
    echo "Schema applied successfully"
else
    echo "Schema file not found: $SCHEMA_FILE"
    exit 1
fi

echo "Database setup complete!"
