# Audyt techniczny Desal.pl — 2026-05-27

Szybki przegląd warstw nie-integracyjnych przeprowadzony przy okazji diagnozy Otomoto/Allegro. Nie był to pełny audyt — to lista najważniejszych spraw wykrytych w 15 minutach.

## Krytyczne — bezpieczeństwo i utrzymanie

### PHP 7.3.33 — EOL od 5 lat

- **End of Life:** 2021-12-06 (oficjalne zakończenie wsparcia przez PHP.net)
- Bez patchy bezpieczeństwa od ponad 4 lat
- Każda znana CVE w PHP 7.3 z lat 2022-2026 pozostaje niezałatana
- Aktualnie wspierane PHP: 8.2 (do 2026-12), 8.3 (do 2027-11), 8.4 (do 2028-12)
- **Rekomendacja:** migracja na PHP 8.2+ przy okazji prac nad sklepem. DuoCMS na CI 3.x może wymagać drobnych korekt (deprecation removal), ale generalnie kompatybilny z PHP 8.

### Brak nagłówków bezpieczeństwa (`curl -I https://desal.pl/`)

Brakuje:
- `Strict-Transport-Security` (HSTS) — bez tego browser może być przekierowany na HTTP przez MITM
- `Content-Security-Policy` (CSP) — bez tego XSS jest łatwiejszy do wykorzystania
- `X-Frame-Options: DENY` — bez tego clickjacking jest możliwy
- `X-Content-Type-Options: nosniff` — bez tego browser może źle interpretować typy plików
- `Referrer-Policy` — wycieka informacja o przeglądanych podstronach do zewnętrznych serwisów

Wprowadzenie tych nagłówków to ~30 minut pracy w `.htaccess` lub w warstwie hostingu — drobna robota o dużym efekcie zabezpieczającym.

### SSL/TLS

- Certyfikat: Let's Encrypt, ważny 16.04.2026 - 15.07.2026 (auto-odnawiany przez hosting)
- SAN obejmuje `*.desal.pl` i `desal.pl` — działa poprawnie mimo że CN to `cpcontacts.desal.dmkhosting.net` (default cert hostingu)
- HTTP → HTTPS redirect działa
- OK, nic do zmiany w tej warstwie

### Sekretne ścieżki — częściowo OK

- `/wp-admin`, `/.env`, `/admin`, `/duocms` → HTTP 302 (do `/pl/`) zamiast 404 — bot scanner widzi że strona istnieje
- `/.git/config`, `/application/config/database.php` → 403 (`.htaccess` chroni) — OK
- **Rekomendacja:** poprawić routing tak, żeby nieistniejące ścieżki zwracały 404, nie redirect na home (drobnostka, ale standard)

## Krytyczne — SEO i widoczność w Google

Sklep ma 27 000 produktów. Tymczasem w Google `site:desal.pl` znajduje **niespełna 10 stron** (głównie statyczne: O nas, Kontakt, Transport, kilka kategorii). To znaczy że ~99,99% asortymentu jest **niewidoczne** w wyszukiwarce.

Powody techniczne:

### 1. `robots.txt` → HTTP 404

Plik nie istnieje. Google nie ma instrukcji jakie sekcje skanować, jakie pomijać, gdzie szukać sitemapy.

### 2. `sitemap.xml` → HTTP 404

Plik nie istnieje. Bez tego Google odkrywa strony wyłącznie przez crawl linków. Sklep z 27 tysiącami produktów ma zbyt głęboką strukturę, żeby crawler doszedł do każdej karty produktu.

### 3. Brakuje wszystkich kluczowych meta-tagów

Sprawdzone z User-Agent Googlebot:

| Tag | Status |
|---|---|
| `<title>` | OK („DESAL - Zakład Złomowania Pojazdów") |
| `<meta name="description">` | **BRAK** |
| `<meta name="keywords">` | BRAK (mniej istotne) |
| `<link rel="canonical">` | **BRAK** |
| `<meta name="viewport">` | OK (responsive) |
| `<html lang="pl">` | OK |
| `<h1>` | **BRAK** |
| Open Graph (`og:title`, `og:image`...) | **BRAK** (nie ma preview w social media) |
| Twitter Cards | BRAK |
| JSON-LD Schema.org (Product, Offer, BreadcrumbList) | **BRAK** |
| Google Analytics / GTM / GA4 | **ZOMBIE** — wpięty Universal Analytics `UA-79935351-1` wyłączony przez Google 2024-07-01 |

> **Korekta 2026-05-28:** poprzednia wersja wpisu („BRAK") była uproszczeniem. Realny stan: `application/views/layouts/main.php` linie 193-202 mają wpięty `<script async src="https://www.googletagmanager.com/gtag/js?id=UA-79935351-1">` plus `gtag('config', 'UA-79935351-1')`. To Universal Analytics, którego Google wyłączyło 1 lipca 2024 — sklep ma fałszywie aktywny tracker, nie zbiera danych od ~22 miesięcy. Efekt biznesowy ten sam co przy braku (zero danych o ruchu), ale techniczne podejście inne: trzeba **wymienić** UA na GA4 + dorzucić GSC (a nie tylko dodać GA4 od zera).

### 4. Brak danych ruchu

UA-79935351-1 zombie nie zbiera danych od lipca 2024. To znaczy że **nie wiadomo**: ile osób wchodzi, skąd, na jakie strony, gdzie odpadają, ile konwertuje. Decyzje biznesowe podejmowane „w ciemno".

### 5. Charset niespójny w bazie

Niektóre tabele MySQL używają `latin2`, inne `utf8mb3`. Przy polskich znakach (ąćęłńóśźż) może to powodować pomyłki rendering w niektórych miejscach panelu admin lub na frontendzie.

## Wnioski

**Najpilniejsze (low effort, high impact):**

1. **Wygenerować i opublikować `sitemap.xml`** — pozwoli Google odkryć 27 tysięcy produktów (1-2h pracy)
2. **Dorzucić `robots.txt`** — sygnalizuje Google co i jak indeksować (15 min)
3. **Dodać meta description + h1 + canonical** w szablonach (1-2h pracy)
4. **Wymienić zombie UA-79935351-1 na GA4 + dorzucić GSC** — żeby zobaczyć rzeczywisty ruch i pozycje (30 min konfiguracji). **Status 2026-05-28:** uzgodnione z Jankiem jako GRATIS dorzutek do SEO basics (baza pod przyszły upsell).
5. **Dorzucić nagłówki bezpieczeństwa** w `.htaccess` (30 min)

> **Estymata komunikowana klientowi w mailu z 2026-05-27**: 4-6h dla najpilniejszych spraw SEO (sitemap + robots + meta + Schema.org). Migracja PHP 7.3 → 8.2: 6-8h plus testy.

**Średniopilne:**

6. JSON-LD Schema.org `Product` + `Offer` na kartach produktu — Google wyświetla wtedy ceny i dostępność wprost w wynikach wyszukiwania
7. Migracja PHP 7.3 → 8.2 (~4-8h pracy + testy)
8. Open Graph dla preview w mediach społecznościowych

**Niskopilne / długoterminowe:**

9. Refaktor `duo_products` (indeksy na `car_id`, `status`, `otomoto_id`, `status2` — przyspieszy panel admin)
10. Migracja niespójnego charset latin2 → utf8mb4

## Co to oznacza biznesowo

Sklep z 27 tysiącami unikalnych pozycji to ogromny zasób, którego potencjał SEO jest dziś **niewykorzystany w ~99%**. Każdy kierowca szukający w Google konkretnej części (np. „klamka zewnętrzna Kia Ceed II") z dużym prawdopodobieństwem **nie znajdzie Państwa oferty**, mimo że ją Państwo macie w katalogu i wystawiacie na Otomoto.

Naprawa tylko 2-3 z powyższych punktów (sitemap, meta tags, Schema.org) może w skali 3-6 miesięcy podnieść ruch organiczny z wyszukiwarki Google **kilkakrotnie**. To bezpłatny strumień ruchu, którego dziś nie ma.

To temat osobny od integracji Otomoto/Allegro, ale realnie ważny dla rentowności sklepu. Warto rozważyć go w jednym pakiecie.
