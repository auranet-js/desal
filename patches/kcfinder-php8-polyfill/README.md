# Hot-fix KCFinder pod PHP 8.3 — patch package

> **Status:** GOTOWY DO DEPLOYMENTU (nie wgrane — czeka na decyzję Janka)
> **Effort:** ~30 minut deployment + 30 minut smoke test
> **Risk:** niski (polyfill funkcji tylko jeśli nie istnieje, idempotentny)
> **Cel:** klient może wgrać zdjęcie produktu w panelu admin (obecnie HTTP 500)

## Pliki w patch

- `_polyfill.php` — definicja polyfill funkcji `each()` (usuniętej w PHP 8.0)
- `apply.sh` — bash script wykonujący deployment (z weryfikacją + rollback-ready)

## Plan wdrożenia

### 1. Backup obecnego stanu

```bash
# Na serwerze:
cp /home6/desal/public_html/assets/plugins/ckeditor/kcfinder/core/autoload.php \
   /home6/desal/public_html/assets/plugins/ckeditor/kcfinder/core/autoload.php.bak-$(date +%Y%m%d-%H%M%S)
```

### 2. Wgranie `_polyfill.php`

Upload `_polyfill.php` do `assets/plugins/ckeditor/kcfinder/_polyfill.php` (root katalogu kcfinder, NIE w core/).

### 3. Modyfikacja `core/autoload.php`

Dodać na początku pliku (po komentarzu nagłówka, przed `// PHP VERSION CHECK`):

```php
// PHP 8+ polyfill — dodane 2026-05-28 jako hot-fix (Auranet)
require_once __DIR__ . '/../_polyfill.php';
```

### 4. Test funkcjonalny

```bash
# CLI test (powinien dać HTML browse listing, BEZ "Call to undefined function each()")
php -d display_errors=1 -d error_reporting=E_ALL \
  -r "chdir('/home6/desal/public_html/assets/plugins/ckeditor/kcfinder'); \$_GET['type']='images'; require 'browse.php';" \
  > /tmp/kcfinder-post-fix.html 2>&1

# Sprawdź czy nie ma fatal
grep -i "fatal\|Call to undefined" /tmp/kcfinder-post-fix.html

# HTTP test
curl -sI "https://desal.pl/assets/plugins/ckeditor/kcfinder/browse.php?type=images" | head -1
# Oczekiwany result: HTTP/2 200 (nie 500)
```

### 5. Test uploadu w panelu admin

1. Zaloguj się do panelu Desal jako admin
2. Edycja dowolnego produktu → kliknij ikonę obrazka w CKEditor → otwiera się browser KCFindera
3. Upload jpg → sprawdź że plik ląduje w `user_files/`
4. Wstaw obraz do treści → zapisz produkt
5. Sprawdź na froncie że obraz renderuje się

### 6. Monitoring 24h

Jeśli wszystko OK po 24h — patch zostaje w produkcji do czasu wymiany na elFinder (planowane w pakiecie A).

## Rollback (jeśli coś się sypie)

```bash
# Na serwerze:
mv /home6/desal/public_html/assets/plugins/ckeditor/kcfinder/core/autoload.php.bak-* \
   /home6/desal/public_html/assets/plugins/ckeditor/kcfinder/core/autoload.php

rm /home6/desal/public_html/assets/plugins/ckeditor/kcfinder/_polyfill.php
```

Po rollback wracamy do stanu HTTP 500 przy uploadzie — klient nadal nie może wgrać zdjęcia. Hot-fix to TYMCZASOWE rozwiązanie do czasu wymiany na elFinder.

## Powiązane

- `~/projekty/desal/docs/KCFINDER_TEST_RESULT_2026-05-28.md` — pełny wynik testu pre-fix
- `~/projekty/_duocms-playbook/KCFINDER_REPLACEMENT.md` — playbook wymiany na elFinder (stała eliminacja problemu)
