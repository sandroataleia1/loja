#!/usr/bin/env bash
# Backup diário do PostgreSQL — roda dentro de um container com acesso à rede Docker
# Uso: docker run --rm --network store_default \
#        -e POSTGRES_HOST=pgsql -e POSTGRES_DB=store \
#        -e POSTGRES_USER=store -e POSTGRES_PASSWORD=... \
#        -v /backups/store:/backups \
#        postgres:16-alpine /scripts/backup.sh

set -euo pipefail

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="${BACKUP_DIR:-/backups}"
KEEP_DAYS="${KEEP_DAYS:-7}"

POSTGRES_HOST="${POSTGRES_HOST:-pgsql}"
POSTGRES_PORT="${POSTGRES_PORT:-5432}"
POSTGRES_DB="${POSTGRES_DB:-store}"
POSTGRES_USER="${POSTGRES_USER:-store}"

FILENAME="${BACKUP_DIR}/store_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "[backup] Starting backup of ${POSTGRES_DB} at ${TIMESTAMP}"

PGPASSWORD="$POSTGRES_PASSWORD" pg_dump \
  -h "$POSTGRES_HOST" \
  -p "$POSTGRES_PORT" \
  -U "$POSTGRES_USER" \
  -d "$POSTGRES_DB" \
  --format=plain \
  --no-owner \
  --no-acl \
  | gzip > "$FILENAME"

echo "[backup] Saved: ${FILENAME} ($(du -sh "$FILENAME" | cut -f1))"

# Remove backups mais antigos que KEEP_DAYS dias
find "$BACKUP_DIR" -name "store_*.sql.gz" -mtime +"$KEEP_DAYS" -delete
echo "[backup] Cleaned backups older than ${KEEP_DAYS} days"
