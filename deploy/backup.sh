#!/usr/bin/env bash
# =============================================================================
# AMS backup — PostgreSQL dump + uploaded files, with 14-day retention and
# optional S3 offload. Run nightly via cron (see deploy/crontab.txt).
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/ams/backend"
BACKUP_DIR="/var/backups/ams"
RETENTION_DAYS=14
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

mkdir -p "$BACKUP_DIR"

# --- Load DB credentials from .env ---
export $(grep -E '^DB_(DATABASE|USERNAME|PASSWORD|HOST|PORT)=' "$APP_DIR/.env" | xargs)

# --- Database dump (custom format, compressed) ---
PGPASSWORD="$DB_PASSWORD" pg_dump \
  -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" \
  -U "$DB_USERNAME" -Fc "$DB_DATABASE" \
  -f "$BACKUP_DIR/db_${TIMESTAMP}.dump"

# --- Uploaded files (avatars, banners, logos, tickets) ---
tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" -C "$APP_DIR/storage/app" public

# --- Optional: push to S3 (uncomment + configure aws cli) ---
# aws s3 cp "$BACKUP_DIR/db_${TIMESTAMP}.dump"        "s3://ams-backups/db/"
# aws s3 cp "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" "s3://ams-backups/storage/"

# --- Prune old local backups ---
find "$BACKUP_DIR" -type f -mtime +"$RETENTION_DAYS" -delete

echo "[$(date)] Backup complete: db_${TIMESTAMP}.dump + storage_${TIMESTAMP}.tar.gz"
