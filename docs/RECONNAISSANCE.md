# Rekonesans — stan wyjściowy

**Data:** 2026-05-27
**Zlecenie:** klient Pan Dariusz (Desal sp. z o.o.) zgłasza nieaktywne integracje Otomoto + Allegro. Auranet (Janek Schenk) podjął się diagnozy.
**Metoda:** wyłącznie READ-ONLY przez MCP `desal-duocms` z naszego serwera Elara — bez SSH, bez modyfikacji produkcji.

## TL;DR

Trzymamy:

1. **Allegro** — cron działa codziennie i wypełnia półpipeline (auto-sketch → produkt), ale od **2021-04-16** nie wystawia nowych aukcji. Refresh tokenu OAuth w DB jest pusty, ostatnia próba odświeżenia była dziś rano (2026-05-27 08:00 UTC). **Refresh token wygasł / został wyczyszczony — wymaga ponownej autoryzacji przez panel admin.**
2. **Otomoto** — pipeline nie został nigdy domknięty. Brak tabeli `duo_shop_otomoto` (analogicznej do allegro), `duo_category_otomoto` jest pusta od początku, ostatnie parametry mapowane w lipcu 2021. **Co gorsza, integracja używa legacy API `api.otomoto.pl`** które od 2022 zostało przeniesione do **OLX Group Partner API** (`api.olx.pl/partner/*`). Wymaga przepisania, nie tylko nowych kluczy.
3. Jest ślad porzuconej próby naprawy — `OtoTest.php` × 2 i `OtomotoModel.php` modyfikowane jesienią 2022 (ostatnio 2022-10-26). Ktoś podjął robotę i jej nie domknął.

## Co potwierdzono

### Środowisko (potwierdzone przez `status`)

| Element | Wartość | OK? |
|---|---|---|
| PHP | 7.3.33 (LiteSpeed) | OK (legacy, brak EOL) |
| MariaDB | 10.11.16 | OK |
| Baza | `desal_duonet`, prefix `duo_` | OK |
| Extensions | mysqli, pdo_mysql, curl, mbstring, openssl, zip, soap, simplexml, gd | komplet |
| `disable_functions` | passthru, system, show_source, popen, pclose, shell_exec, proc_open | typowe |
| `allow_url_fopen` | false | OK — API tylko przez cURL |
| `open_basedir` | pusty | swobodny dostęp w obrębie konta |

Pierwsze 2 fazy crona DZIAŁAJĄ (część → produkt). 3-cia faza (produkt → kanał) jest złamana dla obu integracji.

### Domena biznesowa (zdekodowana z modeli)

```
duo_cars (287 wierszy)
   ↓ rozbiórka (admin ręcznie, duocms/Cars.php)
duo_car_sketches (28 428 wierszy)
   ↓ klik "wystaw" w panelu → wpis do duo_allegro_timetable
duo_allegro_timetable (0 wierszy, kolejka chwilowa)
   ↓ cron Cron::car_timetable() co N minut, batch 5
   ↓ CarModel::product_from_sketch — KOMPLETNA, log type=1
duo_products (27 190 wierszy, update_time: 2026-05-27 08:50 — TABELA ŻYWA)
   ↓ jeśli status=1 → AllegroModel::add_auction_from_product — ZŁAMANA (token!)
   ↓ jeśli status2=1 → OtomotoModel::add_advert_from_product — ZŁAMANA (legacy API!)
duo_shop_allegro (2 065, ostatni 2021-04-16)
[duo_shop_otomoto NIE ISTNIEJE — kanał nigdy nie skończony]
```

### Histogram aktywności (z `duo_allegro_logs`, 44 083 wpisów)

| type | znaczenie | count | najnowszy | interpretacja |
|---|---|---|---|---|
| 1 | część dodana jako produkt | 26 429 | **2026-05-26 14:15** | cron działa, dodaje produkty |
| -1 | część już jest w bazie | 1 414 | **2026-05-27 09:00** | cron działa dziś |
| 3 | (nieznane) | 13 396 | 2026-04-24 16:00 | coś szwankuje ~miesiąc |
| 2 | **aukcja Allegro dodana** | 2 146 | **2021-04-16 09:25** | **STOP** — 5 lat |
| -3 | Otomoto: ma już ogłoszenie | 625 | 2026-05-12 16:00 | ktoś klikał ostatnio |
| -2 | Allegro: ma już aukcję | 73 | 2026-03-27 09:28 | |

### Klucze API znalezione w `duo_options`

(snapshot wartości: `~/secrets/desal/api-options-snapshot-2026-05-27.env`)

**Allegro:**
- `client_id`, `client_secret`, `seller_id` (6265081), endpoint `api.allegro.pl/` — OBECNE
- `access_token`, `refresh_token` — **PUSTE**
- `token_expiration` = `1779876006` (2026-05-27 08:00:06 UTC, czyli **DZIŚ rano**)

Wniosek: cron `Cron::index2()` (`AllegroModel::refresh_token()`) odpalił refresh **dziś** i zapisał pusty token. To znaczy że refresh OAuth zwrócił 4xx/5xx, a kod blindly nadpisał. Allegro REST API od 2024 zaostrzyło wymogi: refresh_token ma TTL 12 miesięcy + scope `allegro:api:sale:offers` musi być explicit. Najpewniej któryś z tych warunków przestał być spełniony.

**Otomoto:**
- `client_id` = `1169` — **legacy OAuth** (4-cyfrowy ID, stara generacja)
- `client_secret` (MD5-like 32 znaki)
- `username` = `desal.tarnow@gmail.com`, `password` = `koszycem1` (raw w DB — **Resource Owner Password Flow**, dziś wycofany)
- `mode` = `production`
- `token`, `token_expiration` — **PUSTE**
- W kodzie `OtomotoModel.php` hardcoded link: `https://www.otomoto.pl/api/open/` (produkcja) lub `https://sbotomotopl.playground.lisbontechhub.com/api/open/` (sandbox)

Wniosek: cały Otomoto siedzi na **wycofanym przez OLX Group API**. Nowy schemat (od 2022/2023) to `api.olx.pl/partner/*` z OAuth 2.0 authorization_code flow. Wymaga:
- nowej rejestracji aplikacji w panelu Otomoto Pro / OLX Partner
- nowych credentials (i ten typowy 32-znakowy client_id, NIE 4-cyfrowy)
- refaktoryzacji `OtomotoModel::get_token()` i wszystkich endpointów

Potwierdzenie: probe z Elary (`curl -sI`) zwraca **200/301** dla `api.olx.pl/partner/categories/` (nowe API żyje) i **403/308** dla `api.otomoto.pl/api/open/*` (CloudFront frontend, nie API).

## Struktura kodu

```
application/
├── config/                          standardowe CI (database, routes, autoload…)
│                                    Brak osobnych otomoto.php / allegro.php — credentials w DB
├── controllers/
│   ├── api/                         publiczne REST endpointy
│   │   ├── Allegro.php (2.3 KB)     callback OAuth Allegro `code_get` — KLUCZ do re-autoryzacji
│   │   ├── Otomoto.php (374 B)      stub bez callbacku
│   │   ├── Key.php (8 KB)
│   │   ├── Ceneo.php
│   │   └── Payments.php
│   ├── cron/
│   │   └── Cron.php (4.3 KB)        index/index2/index3/car_timetable/createotomotoad
│   ├── duocms/                      panel admin
│   │   ├── Allegro.php (36 KB)      pełny panel CRUD aukcji + dostawy + paczki + orders
│   │   ├── Otomoto.php (4 KB)       głównie test endpoints (settings_region/city/category_test*)
│   │   ├── Otomoto_parameters.php (1.6 KB)
│   │   ├── OtoTest.php (426 B)      ślad z 2022-10
│   │   ├── Otomoto/ParametersController.php (391 B)  stub
│   │   ├── Products.php (22 KB)     panel produktów, jesień 2022 modyfikowany
│   │   └── ... (Cars, Configuration, Orders, Users, etc.)
│   ├── OtoTest.php (205 B)          scratch
│   └── ... (Account, Ajax, Home, Kontakt, Merchant, Oferta, Zamowienie)
├── libraries/
│   ├── OpenPayU/                    PayU SDK
│   ├── Facebook/                    Graph SDK
│   ├── nusoap/                      legacy SOAP (Poczta?)
│   ├── REST_Controller.php (77 KB)  philsturgeon/codeigniter-rest-server
│   └── ... (Format, Cache, MY_Pagination, merchant)
└── models/
    ├── AllegroModel.php (54 KB)     KOMPLETNY pipeline Allegro
    ├── OtomotoModel.php (55 KB)     KOMPLETNY (na legacy API) pipeline Otomoto
    ├── CategoryOtomotoModel.php (569 B)  stub
    ├── OnSellModel.php (670 B)      check status ogłoszenia Otomoto
    ├── CarModel.php (15 KB)         product_from_sketch — kluczowa metoda
    ├── ProductModel.php (55 KB)
    └── ... (40+ pomocniczych)
```

Logi pliki: `application/logs/` zawiera **tylko `index.html`** — CI nie pisze do plików, cała obserwowalność integracji jest w tabeli `duo_allegro_logs`.

## Ślady poprzedniego programisty

`duo_options` ma rezydua z projektu **Septem** (innej firmy):
- `facebook_page_name` = `Septem`
- `sendit_login` = `jakub.o@septemonline.com`
- `enadawca_login` = `info@septemonline.com`
- `paypal_email` = `daniel820@o2.pl` (nie desal)

DuoCMS prawdopodobnie został sklonowany z projektu Septem — config nie został w pełni przekonfigurowany.

`OtoTest.php` × 2 + `OtomotoModel.php` modyfikowane 2022-10-25, `controllers/duocms/Products.php` 2022-10-25 w tym samym dniu — porzucona robota nad Otomoto z jesieni 2022.

## Wstępna mapa ścieżki naprawy

Kolejność proponowana:

### 1. Allegro (priorytet — szybka win)

- Re-autoryzacja OAuth: klient zaloguje się przez panel admin, link generowany z `AllegroModel::get_login_url()`, callback `api/Allegro::code_get()` zapisuje nowe access+refresh tokeny w `duo_options`
- **Ryzyko 1:** scope/uprawnienia aplikacji w panelu Allegro Developer mogą być nieaktualne — sprawdzić
- **Ryzyko 2:** Allegro od 2024 wymaga 2FA przy autoryzacji + zatwierdzenie regulaminu nowego sprzedawcy
- **Estymata:** kilka godzin testowania + ewentualny fix logiki refresh (`AllegroModel::refresh_token()`)

### 2. Otomoto (priorytet — przewidywany większy wysiłek)

- Klient Pan Dariusz rejestruje **nową aplikację** w panelu Otomoto Pro / OLX Group Partner
- Otrzymuje nowy client_id (32-znakowy) + client_secret
- Refaktoryzacja `OtomotoModel.php` 55 KB:
  - zmiana endpointu z `www.otomoto.pl/api/open/` na `api.olx.pl/partner/`
  - przejście z Resource Owner Password Flow na Authorization Code Flow
  - aktualizacja struktur request/response (changed parameters, kategorie, atrybuty)
- **Estymata:** 1-3 dni pracy (zależy od kompletności API OLX vs starego)

### 3. Czyszczenie kodu (opcjonalnie po naprawie)

- Usunięcie `OtoTest.php` × 2 (scratch)
- Usunięcie konfiguracji "Septem" z `duo_options`
- Indeksy na `duo_products(car_id, sketch_item_id, otomoto_id, status, status2)` — poprawi crony

## Pełne dane

- Struktura tabel: [`SCHEMA.md`](SCHEMA.md)
- Dostępy: [`PRODUCTION_ACCESS.md`](PRODUCTION_ACCESS.md)
- Architektura integracji (po lekturze pełnych modeli): `INTEGRATIONS.md` (w przygotowaniu — Etap 3)
- Diagnoza i plan naprawy: `DIAGNOSIS.md` (w przygotowaniu — Etap 3)
