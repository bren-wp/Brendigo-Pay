#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find platform/src platform/bin platform/public wordpress-plugin tests -name '*.php' -print0)
echo 'PASS: PHP lint (all PHP files)'
php platform/tests/run.php
php platform/tests/licenses.php
php tests/license-sync.php
php platform/tests/providers.php
python3 platform/tests/smoke.py
python3 platform/tests/backup_restore.py
php tests/woocommerce-gateway.php
php tests/woocommerce-plugin-bootstrap.php
php tests/wp-admin-sections.php
php tests/wp-uninstall.php
python3 -m py_compile scripts/visual_audit_wp.py
python3 tests/ui-contract.py
python3 tests/security-static.py
python3 tests/provider-readiness.py
python3 -m py_compile scripts/visual_audit_10.py
python3 scripts/css-duplicates.py
if command -v node >/dev/null 2>&1; then
  node --check wordpress-plugin/brendigo-pay/assets/block.js
  node --check wordpress-plugin/brendigo-pay/assets/woocommerce-block.js
  node --check wordpress-plugin/brendigo-pay/assets/admin.js
  echo 'PASS: WordPress plugin JavaScript syntax'
fi
echo 'PASS: All local checks completed'
