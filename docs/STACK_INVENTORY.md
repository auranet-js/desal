# Stack Inventory — Desal.pl (case 0 dla DuoCMS playbook)

> **Data inwentaryzacji:** 2026-05-28
> **Projekt:** Desal.pl (CodeIgniter / DuoCMS, klient: Zakład Złomowania Pojazdów)
> **Cel dokumentu:** rzetelna podstawa do wyceny upgrade'u DuoCMS + reusable template dla pozostałych instalacji DuoCMS w portfolio Auranet (Victorini, Agria, JanSchenk, Wydrukinasztuki, projekty supportowe).
> **Status repo:** case 0 — pierwsza inwentaryzacja wedle nowej konwencji (po feedbacku 2026-05-28 że nie wolno wyceniać bez tego dokumentu).

## TL;DR

Sklep deklaratywnie po „bumpie PHP" (zrobione 2026-05-28), realnie ma **5 niezatestowanych legacy komponentów** które potencjalnie blokują pracę użytkownika (panel admin upload) i frontend. Pełna lista poniżej.

**Najpilniejsze (klient może wykryć dziś-jutro):**

| # | Komponent | Konsekwencja jeśli zostanie | Effort |
|---|---|---|---|
| 1 | **KCFinder 2.51** (2010-08-25) | Klient nie wgra zdjęcia produktu / dokumentu w panelu admin (PHP 8 wywala `create_function`, `each()`) | 8-12h |
| 2 | **CKEditor 4.5.10** (2016) | Brak patchy XSS od 3 lat, edytor treści w panelu | 2h (do 4.22 LTS) lub 16h (do 5.x) |
| 3 | **Charset latin2 w `duo_orders`** | Polskie znaki w nazwiskach / adresach klientów = ryzyko cichej korupcji przy JOIN-ach | 4-8h |
| 4 | **OPcache wyłączony** (mimo cPanel) | Każde żądanie re-parse PHP, panel admin wolny | 0.5h diagnoza |
| 5 | **Bootstrap 3.3.7** (EOL 2019-07-24) | Panel wygląda jak z 2016, niezgodny z brandbook Auranet | 16-24h |

**Można zostawić na potem (ale zostaje na liście):**
- jQuery 1.11 + 3.2 ładowane równocześnie (legacy migracji niedokończonej)
- FontAwesome 4.3.0 (EOL, ale działa)
- robots.txt + sitemap.xml (404) — efekt SEO, nie funkcjonalny
- Security headers (HSTS, CSP) — ryzyko teoretyczne

---

## 1. Środowisko serwera

| Parametr | Wartość | Status | Uwagi |
|---|---|---|---|
| **PHP** | **8.3.31** | OK (LTS do 2027-11) | Bump 2026-05-28 z 7.3.33. SAPI: litespeed. |
| Zend | 4.3.31 | OK | |
| SAPI | LiteSpeed | OK | Hosting CloudLinux. |
| memory_limit | 128M | OK dla DuoCMS | Sklep z 27k produktów — może wymagać 256M przy migracji charsetu. |
| max_execution_time | 120s | OK | |
| upload_max_filesize | 64M | OK | |
| **opcache.enable** | **false** | **PROBLEM** | Janek włączał w cPanel — nie widać efektu w `phpinfo()`. Wymaga diagnozy: PHP Selector per-account vs LiteSpeed server-level. Każde żądanie re-parse PHP. |
| display_errors | 0 | OK | (`.htaccess` z `display_errors on` usunięte przy bumpie 2026-05-28) |
| disable_functions | passthru, system, show_source, popen, pclose, shell_exec, proc_open | OK | Standardowy zestaw shared. |
| Open Basedir | (puste) | OK | |
| extensions | 64 załadowanych (gd, mysqli, mbstring, intl, curl, openssl, fileinfo, exif, soap, xmlrpc itd.) | OK | Komplet pod DuoCMS + KCFinder + CKEditor. |

---

## 2. Framework

| Komponent | Wersja zainstalowana | Najnowsza w branchu | Najnowsza absolutnie | EOL | Status |
|---|---|---|---|---|---|
| **CodeIgniter** | **3.1.13** | 3.1.13 (head) | CI 4.5.x (przepisanie) | CI3 supported „indefinitely security-only" (BCIT bot maintenance, nie aktywny rozwój) | **OK pod PHP 8.3, NIE migrujemy do CI4** (rewrite całej aplikacji) |
| DuoCMS | n/a (autorski CMS, nie wersjonowany) | n/a | n/a | utrzymywany przez Auranet | **Reusable across portfolio — patrz `_duocms-playbook/`** |

> **Decyzja:** zostajemy na CI 3.1.13. Migracja do CI 4 to przepisanie całej aplikacji (różny routing, ORM, struktura katalogów) — uzasadniona tylko przy reskinie + rebrand AURAADMIN, nie jako warunek upgrade'u stacku.

---

## 3. Biblioteki front-end (panel admin DuoCMS)

| Komponent | Wersja | Data | Najnowsza | EOL | Risk | Effort (BS3→BS5 etc.) |
|---|---|---|---|---|---|---|
| **Bootstrap** | **3.3.7** | 2016-07-25 | 5.3.x | **2019-07-24** | High — UI legacy + niezgodne z brandbook Auranet | **16-24h** (refactor templates + brandbook) |
| **jQuery (legacy)** | **1.11.1** | 2014 | n/a | 2016 (1.x dropped) | Medium — ładowany razem z 3.2, podwójny | 1-2h cleanup |
| **jQuery (modern)** | **3.2.0** | 2017 | 3.7.1 | n/a | Low — działa, można podbić do 3.7 | 0.5h |
| **jQuery Migrate** | **1.2.1** | 2013 | 3.4.x | n/a | Symptom — używane do bridge 1→3 niedokończonego | usuwa się po cleanup jQuery |
| **jQuery UI** | (brak wersji w header, min) | 2021-08 mtime | 1.13.x | utrzymywane | Medium — CVE w starszych wersjach | 1-2h sprawdzić wersję + ew. bump |
| **Chosen.jQuery** | (brak wersji w header) | 2021-08 mtime | 1.8.7 (umarły 2017) | dead project | Medium — replacement: Tom-Select | 2-4h jeśli używane |
| **FontAwesome** | **4.3.0** | 2015 | 6.x | 4.x niewspierane | Low — działa, brak nowoczesnych ikon | 2-4h (FA4→FA6) |
| **Bootstrap-multiselect** | (BS3-specific widget) | 2021-08 mtime | dead | dead | Medium — wymiana razem z BS5 | wpięte w 16-24h Bootstrap |
| **MetisMenu** | min | 2021-08 mtime | utrzymywane | n/a | Low | 0 |
| **html5shiv** | (IE8 polyfill) | 2021-08 mtime | obsolete | n/a | Low — usunąć | 0.5h |
| **placeholders.min.js** | (IE9 polyfill) | 2021-08 mtime | obsolete | n/a | Low — usunąć | 0.5h |
| **Lightbox2** | **2.8.2** | 2015 | 2.11.4 | sporadycznie utrzymywane | Low | 0-1h |
| **Slick** (carousel) | brak wersji | 2017-ish | 1.8.1 (umarły 2017) | dead | Medium — replacement: SwiperJS | 2-3h jeśli używane |
| **EasyAutocomplete** | **1.3.5** | 2015 | 1.3.5 (ostatnie) | dead | Low | sprawdzić użycie |
| **plupload** | folder pusty / nieskonfigurowany (`js/plupload.full.min.js` nie istnieje) | 2019-02 (katalog) | 3.x | dead project | brak — nie wpięte | 0 |
| **Toastr** | (brak wersji w header) | 2019 mtime | utrzymywane | n/a | Low | 0 |

---

## 4. Edytor + uploader (CKEditor + KCFinder) — STREFA KRYTYCZNA

### CKEditor

| Parametr | Wartość |
|---|---|
| **Wersja** | **4.5.10** (`timestamp G6DE`, revision `b47abaf`) |
| Data wydania | 2016-04-29 |
| Status | **EOL 2023-06-30** dla całej linii 4.x |
| Najnowsza 4.x | 4.22.1 (2023-06) — kompatybilna, bezpieczna |
| Najnowsza 5.x | 5.x — breaking changes, license model GPL/komercja |
| Risk | High — brak patchy XSS od 3 lat. CKEditor 4 historycznie miał CVE w plugin do contentu (image, link, paste). |
| Effort | 2h do 4.22 LTS-end (drop-in compatible) lub 16h do 5.x (przepisanie configu + breaking changes) |

**Konfiguracja:** `assets/plugins/ckeditor/config.js` — toolbar groups, `removeButtons`, `removeDialogTabs`, `allowedContent = true` (NIEBEZPIECZNE — pozwala dowolny HTML), `extraPlugins = 'iframe'`. KCFinder wpięty przez `filebrowserBrowseUrl` / `filebrowserUploadUrl` (linie 38-44).

### KCFinder

| Parametr | Wartość |
|---|---|
| **Wersja** | **2.51** (`uploader.php` const VERSION) |
| **Data wydania** | **2010-08-25** (!!) |
| Autor | Pavel Tzonkov — projekt **effectively dead od 2014** |
| Status | brak EOL formalnego (autor nie wydał oświadczenia), ale **15 lat bez wydania** |
| Najnowsza | brak — repo KCFinder/3.0 na GitHubie nigdy nie zostało wydane jako stable |
| Risk | **CRITICAL** — pliki PHP używają `create_function()` (usunięte PHP 8.0), `each()` (usunięte PHP 8.0), call-time pass-by-reference |
| Wpięcie | `assets/plugins/ckeditor/config.js` linie 38-44 + `application/views/layouts/admin.php` + `application/views/duocms/Custom_elements/field.php` |
| Konsekwencja awarii | **Klient nie może wgrać zdjęcia produktu / aktualności / dokumentu** w panelu admin DuoCMS |
| Effort | 8-12h wymiana na nowoczesny uploader (3 opcje — patrz sekcja Rekomendacje) |

**Grep `create_function|each()`** zwraca match w 11 plikach KCFindera:
```
js/browser/joiner.php
lib/helper_dir.php
lib/class_input.php
lib/helper_httpCache.php
lib/class_gd.php
lib/class_zipFolder.php
lib/helper_path.php
js_localize.php
core/browser.php
core/autoload.php
core/uploader.php
```

**Test empiryczny** (task #2): kliknąć „dodaj zdjęcie" w panelu admin Desala i obserwować `error_log`. Verdykt + ekran błędu → mailowy raport.

### Rekomendacje uploader (do dyskusji z klientem)

| Opcja | Effort | Pros | Cons |
|---|---|---|---|
| A. Wymiana KCFinder → CKFinder 3 (free) | 12-16h | Pisany dla CKE 4/5, support, aktywny rozwój | Free wersja ograniczona, komercyjna płatna |
| B. Wymiana KCFinder → elFinder | 8-12h | Open source, aktywny, popularny w portfolio CMSów (Roundcube, Drupal) | Wymaga PHP 7.4+, działa pod 8.x ale wymaga konfiguracji |
| C. Własny uploader (filepond/dropzone + custom PHP endpoint) | 12-20h | Pełna kontrola, reusable w innych instalacjach DuoCMS | Więcej kodu Auranet do utrzymania |

**Domyślna rekomendacja:** B (elFinder) — dla Desala. Dla AURAADMIN rebrandu długoterminowo C (custom, reusable).

---

## 5. Baza danych — charset i collation

### Stan obecny: niespójność dwóch charsetów

| Charset | Liczba tabel | Kluczowe tabele |
|---|---|---|
| **latin2_general_ci** | **25** | `duo_orders`, `duo_orders_items`, `duo_shop_allegro`, `duo_shop_allegro_photos`, `duo_shop_attributes_groups`, `duo_shop_codes`, `duo_shop_inpost_rel`, `duo_shop_options`, `duo_newsletter_emails`, `duo_newsletter_mailings`, `duo_candidate`, `duo_category_otomoto`, `duo_custom_fields`, `duo_menus`, `duo_otomoto_parameter_bind`, `duo_positions`, `duo_shop_blocked_products`, `duo_shop_codes_used`, `duo_shop_one_time_codes`, `duo_shop_product_relations`, `duo_shop_story`, `duo_users_rebate_groups`, `duo_custom_fields_translations`, `duo_shop_attributes_groups_translations` |
| **utf8mb3_general_ci** | **44** | `duo_users`, `duo_products`, `duo_pages`, `duo_cars`, `duo_categories`, `duo_news`, `duo_galleries`, `duo_photos`, `duo_menu`, `duo_options`, `duo_phinxlog`, `duo_strings`, `duo_templates`, `duo_languages`, `duo_allegro_logs`, `duo_allegro_timetable`, `duo_offer_categories`, `duo_partnerzy`, `duo_shop_attributes`, `duo_shop_delivery`, `duo_shop_product_pack`, `duo_tmp_files`, `duo_wizerunki` + translations tables |
| **utf8mb4_*** | **0** | brak |

### Co to oznacza biznesowo

- **Polskie znaki w nazwiskach klientów / adresach** (`duo_orders.address`, `duo_orders.client_name`...) są w **latin2**. Działa pod warunkiem że PHP/MySQL connection charset też latin2 lub że jest automatyczna konwersja. Każda zmiana driver / parametru connection = ryzyko niepoprawnego rendering („Å»ółć" zamiast „Żółć").
- **JOIN `duo_orders` ⨝ `duo_users`** = latin2 ⨝ utf8mb3 = MySQL musi konwertować in-flight. Większość przypadków OK, ale przy `WHERE name = 'Żółć'` może nie znaleźć (bo „Ż" w utf8mb3 = 2 bajty, w latin2 = 1 bajt na innym code-pointcie).
- **Brak utf8mb4** = zero supportu dla emoji w treściach (klient może chcieć dodać emoji w opisie aktualności / FAQ) i niektórych znaków specjalnych (np. „—" em dash kontra „-" dywiz).

### Plan migracji

1. **Audyt bez utf8mb4-incompatible**: wszystkie tabele inwentaryzacja, identyfikacja FK constraints
2. **Backup pełny** (mysqldump --default-character-set=utf8mb4)
3. **Migracja per-tabela** `ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` (latin2→utf8mb4 NIE jest no-op — wymaga konwersji per-row)
4. **Aktualizacja CI** `application/config/database.php` → `'char_set' => 'utf8mb4'`, `'dbcollat' => 'utf8mb4_unicode_ci'`
5. **Smoke test**: zamówienia z polskimi znakami, JOIN-y kluczowych raportów, eksport CSV

**Effort:** 4-8h dla 69 tabel + smoke test. Dla DuoCMS playbook = scripted (jeden komand-launcher per instalacja).

---

## 6. SEO + analytics (frontend, ze wcześniejszego audytu)

Z `docs/TECH_AUDIT_2026-05-27.md` — przepisane skondensowane:

| Element | Status |
|---|---|
| `robots.txt` | 404 |
| `sitemap.xml` | 404 |
| `<meta name="description">` | brak |
| `<link rel="canonical">` | brak |
| `<h1>` | brak |
| Open Graph | brak |
| JSON-LD Schema.org Product/Offer | brak |
| GA tracker | **zombie UA-79935351-1** (wyłączony przez Google 2024-07-01, niewymieniony na GA4) |
| GSC | nieaktywne |

**Effort cały SEO basics paczka:** 4-6h (już uzgodnione z klientem w mailu z 2026-05-27, część pakietu).

---

## 7. Security headers (frontend)

Z `docs/TECH_AUDIT_2026-05-27.md`:

| Header | Status |
|---|---|
| `Strict-Transport-Security` | brak |
| `Content-Security-Policy` | brak |
| `X-Frame-Options` | brak |
| `X-Content-Type-Options` | brak |
| `Referrer-Policy` | brak |

**Effort:** 30 min .htaccess + 30 min testy.

---

## 8. Lista skondensowana z estymatami godzin

| # | Item | Effort | Priorytet | W zakresie aktualnej oferty? |
|---|---|---|---|---|
| 1 | KCFinder wymiana (→ elFinder lub custom) | 8-12h | **P0 — blokuje pracę** | **NIE — do dorzucenia** |
| 2 | CKEditor 4.5.10 → 4.22 LTS-end | 2h | P1 | NIE — do dorzucenia |
| 3 | OPcache diagnoza + włączenie | 0.5h | P1 (performance) | Część PHP bump retroaktywnie |
| 4 | Bootstrap 3 → 5 + brandbook Auranet | 16-24h | P2 (UI legacy, brand mismatch) | **NIE — osobny pakiet** |
| 5 | Charset latin2 → utf8mb4 migracja | 4-8h | P1 (polskie znaki) | NIE — do dorzucenia lub osobny pakiet |
| 6 | jQuery cleanup (1.11+3.2 → 3.7 only) | 1-2h | P3 | razem z BS5 |
| 7 | FontAwesome 4 → 6 | 2-4h | P3 | razem z BS5 |
| 8 | sitemap.xml + robots.txt + meta tags + GA4 + GSC | 4-6h | P1 (SEO basics) | **TAK — uzgodnione 2026-05-27** |
| 9 | JSON-LD Schema.org Product+Offer | 2-3h | P2 (rich snippets) | osobny upsell |
| 10 | Security headers (.htaccess) | 1h | P1 | drobnostka — wpięta w SEO basics |
| 11 | Otomoto fix (z DIAGNOSIS v4) | 4h | P0 (uzgodnione) | **TAK** |
| 12 | Otomoto panel enhancement | 1h | P1 | **TAK** |

**Razem do uzgodnienia z klientem PONAD aktualną ofertę:** items 1, 2, 4, 5 = **30-46h dodatkowo**, w naturalnych pakietach:
- **Pakiet „Panel admin operacyjny"** (items 1+2+3+5) = 14.5-22.5h → bez tego klient może mieć cichy fail uploadu i utratę polskich znaków
- **Pakiet „UI panel admin pod brandbook Auranet"** (items 4+6+7) = 19-30h → cosmetic, do dyskusji
- **Pakiet „SEO + analytics"** items 8+9+10 = 7-10h, część w aktualnej ofercie

---

## 9. Co dalej

1. **Task #2** — empiryczny test KCFindera pod PHP 8.3, dokładne komunikaty błędów → załącznik do raportu mailowego dla klienta
2. **Task #3** — gdzie dokładnie BS3 jest wpięty w admin views, mapa plików → estymata BS3→BS5 doprecyzowana
3. **Task #5** — komunikat do klienta (do Janka na js@auranet.com.pl wedle memory) z propozycją dorzutek do aktualnej oferty
4. **Task #4** — wydzielenie tego dokumentu jako `STACK_INVENTORY.template.md` w `~/projekty/_duocms-playbook/` + powiązane playbooki (PHP8, KCFinder replacement, charset migration, AURAADMIN rebrand notes)

## 10. Powiązane dokumenty

- `docs/TECH_AUDIT_2026-05-27.md` — audyt techniczny (SEO, security, charset)
- `docs/DIAGNOSIS.md` — Otomoto / Allegro diagnoza
- `docs/decyzje/2026-05-28-php-bump-7.3-do-8.3.md` — ADR bump PHP
- Memory: `feedback_full_stack_inventory_before_quote.md` — geneza tego dokumentu
- Memory: `feedback_duocms_playbook_reusability.md` — cel cross-DuoCMS
- Memory: `project_auraadmin_rebrand_plan.md` — kontekst długoterminowy
