#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CRON_SCRIPT="${ROOT}/bin/db-backup.php"
LOG_DIR="${ROOT}/storage/logs"
LOG_FILE="${LOG_DIR}/db-backup.log"
MARKER_BEGIN='# BEGIN fusionlink-db-backup'
MARKER_END='# END fusionlink-db-backup'
CRON_LINE="30 2 * * * TZ=Asia/Manila /usr/bin/php ${CRON_SCRIPT} --keep-days=14 >> ${LOG_FILE} 2>&1"

if [[ ! -f "$CRON_SCRIPT" ]]; then
  echo "Missing DB backup script: $CRON_SCRIPT" >&2
  exit 1
fi

mkdir -p "$LOG_DIR" "${ROOT}/storage/backups"
chmod 755 "$LOG_DIR" "${ROOT}/bin"
chmod 755 "$CRON_SCRIPT"

EXISTING="$(crontab -l 2>/dev/null || true)"
CLEANED="$(printf '%s\n' "$EXISTING" | awk -v b="$MARKER_BEGIN" -v e="$MARKER_END" '
  $0==b {skip=1; next}
  $0==e {skip=0; next}
  !skip {print}
')"
CLEANED="$(printf '%s\n' "$CLEANED" | grep -v 'fusionlink/bin/db-backup.php' || true)"

{
  printf '%s\n' "$CLEANED"
  echo "$MARKER_BEGIN"
  echo "$CRON_LINE"
  echo "$MARKER_END"
} | crontab -

echo "Installed into current user crontab ($(whoami))"
echo "Schedule: daily 02:30 Asia/Manila"
echo "Log file: ${LOG_FILE}"
echo
echo "Test now with:"
echo "  php ${CRON_SCRIPT}"
