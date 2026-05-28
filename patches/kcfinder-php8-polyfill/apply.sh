#!/bin/bash
# apply.sh — wdrożenie hot-fixu KCFinder polyfill na produkcji Desal.
# Uruchom NA SERWERZE (przez SSH / cron / panel) z katalogu zawierającego ten patch.
#
# Idempotent: wielokrotne uruchomienie nie psuje stanu.
# Bezpieczne: tworzy backup, weryfikuje, ma rollback path.

set -e

KCFINDER_DIR="/home6/desal/public_html/assets/plugins/ckeditor/kcfinder"
PATCH_DIR="$(cd "$(dirname "$0")" && pwd)"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

echo "=== KCFinder PHP 8 polyfill — apply ==="
echo "Target: $KCFINDER_DIR"
echo "Patch source: $PATCH_DIR"
echo ""

# Sanity check — czy KCFinder istnieje
if [ ! -f "$KCFINDER_DIR/core/autoload.php" ]; then
    echo "ERROR: KCFinder nie znaleziony w $KCFINDER_DIR"
    exit 1
fi

# Sanity check — czy patch source ma _polyfill.php
if [ ! -f "$PATCH_DIR/_polyfill.php" ]; then
    echo "ERROR: _polyfill.php nie znaleziony w $PATCH_DIR"
    exit 1
fi

# === KROK 1 — backup autoload.php ===
BACKUP="$KCFINDER_DIR/core/autoload.php.bak-$TIMESTAMP"
cp "$KCFINDER_DIR/core/autoload.php" "$BACKUP"
echo "[1/4] Backup: $BACKUP"

# === KROK 2 — wgranie _polyfill.php ===
cp "$PATCH_DIR/_polyfill.php" "$KCFINDER_DIR/_polyfill.php"
echo "[2/4] Skopiowano _polyfill.php"

# === KROK 3 — modyfikacja autoload.php ===
# Sprawdź czy już nie ma polyfilla wpiętego (idempotency)
if grep -q "_polyfill.php" "$KCFINDER_DIR/core/autoload.php"; then
    echo "[3/4] Polyfill już wpięty w autoload.php — pomijam"
else
    # Dodaj require_once na początku po nagłówku
    # Strategy: znajdź pierwszą linię z `// PHP VERSION CHECK` i wstaw przed nią
    sed -i.tmp '/\/\/ PHP VERSION CHECK/i\
// PHP 8+ polyfill — dodane '$TIMESTAMP' jako hot-fix (Auranet)\
require_once __DIR__ . '"'"'/../_polyfill.php'"'"';\
' "$KCFINDER_DIR/core/autoload.php"
    rm -f "$KCFINDER_DIR/core/autoload.php.tmp"
    echo "[3/4] Wpięto polyfill require do autoload.php"
fi

# === KROK 4 — verification ===
echo "[4/4] Verification..."

# Czy linia z require istnieje
if grep -q "_polyfill.php" "$KCFINDER_DIR/core/autoload.php"; then
    echo "  OK: require _polyfill.php obecny w autoload.php"
else
    echo "  FAIL: require nie został dodany"
    exit 1
fi

# CLI test żeby sprawdzić czy nie ma fatal
TEST_OUT=$(php -d display_errors=1 -d error_reporting=E_ERROR \
    -r "chdir('$KCFINDER_DIR'); \$_GET['type']='images'; require 'browse.php';" 2>&1 || true)

if echo "$TEST_OUT" | grep -qi "Fatal\|Call to undefined function each"; then
    echo "  FAIL: nadal pojawia się fatal po polyfill"
    echo "  $TEST_OUT" | head -5
    echo ""
    echo "Rolling back..."
    mv "$BACKUP" "$KCFINDER_DIR/core/autoload.php"
    rm -f "$KCFINDER_DIR/_polyfill.php"
    exit 1
else
    echo "  OK: CLI test przeszedł bez fatal"
fi

echo ""
echo "=== Patch zastosowany ==="
echo ""
echo "Następny krok: manualny test w przeglądarce"
echo "  1. Login do panelu admin Desal"
echo "  2. Edycja produktu → ikona obrazka w CKEditor"
echo "  3. Upload jpg → sprawdź że plik ląduje w user_files/"
echo ""
echo "Rollback w razie czego:"
echo "  mv $BACKUP $KCFINDER_DIR/core/autoload.php"
echo "  rm $KCFINDER_DIR/_polyfill.php"
