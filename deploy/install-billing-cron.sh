#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CRON_SCRIPT="${ROOT}/bin/billing-cron.php"
LOG_DIR="${ROOT}/storage/logs"
LOG_FILE="${LOG_DIR}/billing-cron.log"
MARKER_BEGIN='# BEGIN fusionlink-billing'
MARKER_END='# END fusionlink-billing'
CRON_LINE="5 6 * * * TZ=Asia/Manila /usr/bin/php ${CRON_SCRIPT} all >> ${LOG_FILE} 2>&1"

if [[ ! -f "$CRON_SCRIPT" ]]; then
  echo "Missing billing cron script: $CRON_SCRIPT" >&2
  exit 1
fi

mkdir -p "$LOG_DIR"
chmod 755 "$LOG_DIR"
chmod 755 "${ROOT}/bin"
chmod 755 "$CRON_SCRIPT"

EXISTING="$(crontab -l 2>/dev/null || true)"
CLEANED="$(printf '%s\n' "$EXISTING" | awk -v b="$MARKER_BEGIN" -v e="$MARKER_END" '
  $0==b {skip=1; next}
  $0==e {skip=0; next}
  !skip {print}
')"
CLEANED="$(printf '%s\n' "$CLEANED" | grep -v 'fusionlink/bin/billing-cron.php' || true)"

{
  printf '%s\n' "$CLEANED"
  echo "$MARKER_BEGIN"
  echo "$CRON_LINE"
  echo "$MARKER_END"
} | crontab -

echo "Installed into current user crontab ($(whoami))"
echo "Schedule: daily 06:05 Asia/Manila"
echo "Log file: ${LOG_FILE}"
echo
echo "Test now with:"
echo "  php ${CRON_SCRIPT} all"
