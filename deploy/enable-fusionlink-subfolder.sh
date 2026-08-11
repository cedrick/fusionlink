#!/usr/bin/env bash
set -euo pipefail

CONF_SRC="$(cd "$(dirname "$0")" && pwd)/apache-fusionlink-subfolder.conf"
CONF_DST="/etc/apache2/sites-available/000-default.conf"

echo "Installing Apache vhost from:"
echo "  $CONF_SRC"
echo "  -> $CONF_DST"

sudo cp "$CONF_SRC" "$CONF_DST"
sudo apache2ctl configtest
sudo systemctl reload apache2

echo ""
echo "Fusionlink should now be available at:"
echo "  http://localhost/fusionlink/"
echo "  https://allinjuanservices.com/fusionlink/"
