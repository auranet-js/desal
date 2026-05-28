# KCFinder pod PHP 8.3 — wynik testu empirycznego

> **Data testu:** 2026-05-28
> **Środowisko:** Desal.pl produkcja, PHP 8.3.31, LiteSpeed, KCFinder 2.51 (2010-08-25)
> **Cel:** zweryfikować czy klient może wgrać zdjęcie / dokument w panelu admin po bumpie PHP 7.3.33 → 8.3.31 (2026-05-28).

## Verdykt

**KCFinder NIE DZIAŁA pod PHP 8.3.31.** Klient kliknie „dodaj zdjęcie" w panelu admin → HTTP 500 → blank screen. Wymaga wymiany.

## Evidence

### 1. HTTP test (real-world request)

```
GET https://desal.pl/assets/plugins/ckeditor/kcfinder/browse.php?type=images
→ HTTP/2 500
→ content-type: text/html; charset=UTF-8
→ content-length: 0
```

`content-length: 0` + `Content-Type: text/html` = PHP wyprodukował 500, ale ciało jest puste, bo `display_errors=0` w `php.ini` + `log_errors=""` (puste/false). Komunikat fatal idzie w pustkę. Klient nie wie co się stało — widzi blank screen.

### 2. CLI test (pełne komunikaty błędów)

```bash
php -d display_errors=1 -d error_reporting=E_ALL \
  -r "chdir('/home6/.../kcfinder'); \$_GET['type']='images'; require 'browse.php';"
```

Zwrócił **8 warningów/notices/deprecated** + zrenderowany HTML KCFindera (bez HTTP session context CLI nie wywala fatal-em):

| # | Plik | Linia | Komunikat |
|---|---|---|---|
| 1 | `core/autoload.php` | 48 | `Deprecated: __autoload() is deprecated, use spl_autoload_register() instead` |
| 2 | `config.php` | 28 | `Notice: Undefined index: HTTP_HOST` |
| 3 | `core/uploader.php` | 140 | `Warning: session_start(): Cannot start session when headers already sent` |
| 4 | `config.php` | 28 | `Notice: Undefined index: HTTP_HOST` (powtórzony) |
| 5 | `core/uploader.php` | 182 | `Notice: Undefined index: HTTP_HOST` |
| 6 | `core/uploader.php` | 183 | `Notice: Undefined index: HTTP_HOST` |
| 7 | `core/uploader.php` | 187 | `Notice: Undefined index: HTTP_HOST` |
| 8 | `core/browser.php` | 103, 104 | `Warning: Cannot modify header information - headers already sent by (output started at autoload.php:48)` |
| 9 | `tpl/tpl_javascript.php` | 15 | `Notice: Undefined index: HTTP_USER_AGENT` |

### 3. Code-level miny (statyczna analiza)

| Funkcja | Plik | Linia | Status PHP 8 |
|---|---|---|---|
| **`each($image)`** | `lib/class_gd.php` | 55, 56 | **USUNIĘTE** w PHP 8.0 — fatal `Call to undefined function each()` przy hot path resize obrazu |
| **`__autoload($class)`** (globalna) | `core/autoload.php` | 48 | **Deprecated** w PHP 7.2, formalnie nie usunięte w 8.x ale ostrzeżenie w każdym requeście. Usunięte w PHP 9.0. |
| Brak `create_function` | n/a | n/a | n/a — wcześniejszy alarm był pomyłką (grep z OR-em zliczał pliki które miały *któryś* z 3 patternów) |

## Konsekwencje biznesowe

**Co przestaje działać dla klienta:**

1. **Wgrywanie zdjęcia produktu** w panelu admin (CKEditor → ikona obrazka → KCFinder browser)
2. **Wgrywanie zdjęcia aktualności** (ten sam mechanizm)
3. **Wgrywanie zdjęcia w gallery** (CKEditor w content blockach)
4. **Wgrywanie dokumentu** (PDF, DOC) — jeśli klient kiedykolwiek to robił przez CKE
5. **Browsing istniejących plików** w katalogu uploads/user_files (browse listing też wraca 500)

**Co działa:**

- Reszta panelu admin DuoCMS (CRUD na produktach, zamówieniach, aktualnościach BEZ obrazu)
- CKEditor sam w sobie (toolbar, formatowanie tekstu)
- Frontend sklepu (frontend nie używa KCFindera, tylko ścieżki do plików już wgranych)
- Istniejące zdjęcia produktów wgrane przed bumpem PHP 8 — nadal renderują się na froncie

## Rekomendacja

**Pakiet „Panel admin operacyjny"** w nowej propozycji do klienta:

| Sub-item | Effort | Priorytet |
|---|---|---|
| Wymiana KCFinder na elFinder (open source, aktywny, PHP 8 compatible) | 8-12h | **P0** |
| Bump CKEditor 4.5.10 → 4.22.1 LTS-end (drop-in, security patches) | 2h | P1 |
| Test smoke uploadu (10 produktów × różne formaty) | 1h | P0 |

**Razem:** 11-15h.

**Alternatywa:** szybki patch — backportować `each()` polyfill + `spl_autoload_register` wrapper do KCFindera. Effort: 3-4h, ale **łata na umarłej bibliotece**, ekspozycja na nieznane CVE (KCFinder 2.51 = 15 lat bez patchy bezpieczeństwa). NIE rekomendowane jako produkcyjne rozwiązanie, **dopuszczalne jako tymczasowy hot-fix** (1-2 tygodnie) jeśli klient potrzebuje uploadu **dziś** a pakiet wymiany dopiero za tydzień.

## Cross-DuoCMS implication

Każda inna instalacja DuoCMS w portfolio Auranet (Victorini, Agria, JanSchenk, Wydrukinasztuki, supportowe) **ma ten sam problem** jeśli była/będzie bumpowana do PHP 8+. KCFinder 2.51 jest standardowym zestawem DuoCMS od czasu Janka.

Rekomendacja: w `~/projekty/_duocms-playbook/KCFINDER_REPLACEMENT.md` udokumentować standardową procedurę wymiany — reusable across portfolio.

## Powiązane

- `docs/STACK_INVENTORY.md` (sekcja 4 — CKEditor + KCFinder)
- `docs/decyzje/2026-05-28-php-bump-7.3-do-8.3.md`
- Memory: `feedback_full_stack_inventory_before_quote.md`
- Memory: `feedback_duocms_playbook_reusability.md`
