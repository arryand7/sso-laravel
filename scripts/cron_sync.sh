#!/usr/bin/env bash
set -euo pipefail

root="/var/www/gate.sabira-iibs.id"

/usr/bin/php "$root/scripts/sync_users.php"
/usr/bin/php "$root/scripts/sync_gate_to_lms.php"
