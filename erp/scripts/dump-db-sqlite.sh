#!/usr/bin/env bash
##############################################################################
# EPSILON SQLite Database Dump Script
# Generates portable SQL dumps for SQLite database
##############################################################################

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DUMP_DIR="$PROJECT_DIR/database/dumps"
DB_FILE="$PROJECT_DIR/database/database.sqlite"

# Create dumps directory
mkdir -p "$DUMP_DIR"

# Generate timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DUMP_FILE="$DUMP_DIR/epsilon_sqlite_${TIMESTAMP}.sql"

echo "[INFO] Generating SQLite dump: $DUMP_FILE"

# Dump only if database exists
if [ ! -f "$DB_FILE" ]; then
    echo "[ERROR] Database file not found: $DB_FILE"
    exit 1
fi

# Extract SQL dump (works with SQLite via query)
php -r "
\$db = new SQLite3('$DB_FILE');
\$query = \$db->querySingle('SELECT sql FROM sqlite_master WHERE sql NOT NULL;', true);
if (!empty(\$query)) {
    file_put_contents('$DUMP_FILE', implode(\"\\n\\n\", (array)\$query));
    echo \"[OK] Dump created: $DUMP_FILE\\n\";
} else {
    echo \"[ERROR] Could not extract schema\\n\";
    exit(1);
}
\$db->close();
" || {
    # Fallback: use PHP's pdo
    php -r "
\$pdo = new PDO('sqlite:$DB_FILE');
\$tables = \$pdo->query(\"SELECT name FROM sqlite_master WHERE type='table'\")->fetchAll(PDO::FETCH_COLUMN);
\$dump = '';
foreach (\$tables as \$table) {
    \$schema = \$pdo->query(\"SELECT sql FROM sqlite_master WHERE type='table' AND name='\$table'\")->fetch(PDO::FETCH_COLUMN);
    \$dump .= \$schema . \";\\n\\n\";
}
file_put_contents('$DUMP_FILE', \$dump);
echo \"[OK] Dump created: $DUMP_FILE\\n\";
"
}

# Compress
gzip -f "$DUMP_FILE"
echo "[OK] Compressed: ${DUMP_FILE}.gz"
echo ""
echo "✓ Dump ready for distribution/backup"
