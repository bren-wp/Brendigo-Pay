#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find platform/src platform/bin platform/public wordpress-plugin tests -name '*.php' -print0)
echo 'PASS: PHP lint (all PHP files)'
php platform/tests/run.php
php platform/tests/providers.php
python3 platform/tests/smoke.py
python3 platform/tests/backup_restore.py
php tests/woocommerce-gateway.php
echo 'PASS: All local checks completed'
