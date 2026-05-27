# Otomoto / OLX Car Parts API — referencja aktualna

**Źródła:**
- https://www.otomoto.pl/api/doc/ (Swagger UI — JS-rendered, niedostępne via WebFetch)
- https://www.otomoto.pl/news/jak-uzyskac-dostep-do-api
- https://www.otomoto.pl/news/rejestracja-api
- https://www.otomoto.pl/news/faq-api
- https://developer.olxgroup.com/products (OLX Group — Car Parts API + Motors API)
- https://developer.olx.ua/swagger/v2/partner_api.yaml (OpenAPI 3.0.2 — ten sam stack co PL)
- https://developer.olxgroup.com/docs/making-requests-to-the-api

**Fetched:** 2026-05-27

## TL;DR dla naprawy Desala

**Dwie ścieżki dostępu — wybierz jedną:**

| Ścieżka | API | Marketplace'y | Workflow |
|---|---|---|---|
| **A. OLX Car Parts API** (REKOMENDOWANE) | OLX Group Developer | Otomoto + Autovit + OLX.pl + OLX.ro — 4 jednocześnie | Multi-marketplace ze wspólnym SDK |
| **B. Otomoto API** (legacy-friendly) | Otomoto bezpośrednio | Tylko Otomoto | Najmniejsza zmiana w `OtomotoModel.php` |

**Wariant A daje ~4x większy reach za podobny wysiłek pracy.** Z drugiej strony, B jest minimalnym fork'iem istniejącego kodu.

## Ścieżka A — OLX Car Parts API (OLX Group)

### Produkty OLX Group dla części samochodowych

| API | Status | Marketplace'y | Komentarz |
|---|---|---|---|
| **Car Parts API** | ✅ Active + supported | Otomoto, Autovit, OLX.pl, OLX.ro | **Idealny dla Desala** |
| Motors API | ⚠️ Stable, no new dev | Otomoto, Autovit, Standvirtual | Dla pełnych aut, nie części |
| Real Estate API | ✅ Active | Otodom, Storia, Imovirtual | Nieadekwatne |
| OLX API | ⚠️ Stable, no support | OLX Europe | Generyczne |

### Rejestracja jako developer OLX Group

1. `https://developer.olxgroup.com/register` — formularz rejestracji
2. Logowanie OAuth z konta OLX (Pan Dariusz lub Auranet)
3. "Connect App" — wypełnienie:
   - Nazwa aplikacji (np. "Desal Parts Integration")
   - Opis projektu (np. "Integracja DuoCMS sklepu części z rozbiórek z Car Parts API")
   - Redirect URI (np. `https://desal.pl/api/Otomoto/code_get`)
4. Weryfikacja zespołu OLX
5. Otrzymanie `client_id` + `client_secret` mailem

### OAuth Flow (z Ukraińskiego OpenAPI YAML — identyczny stack)

```
1. Authorization endpoint:
GET https://www.olx.pl/oauth/authorize?
    response_type=code
    &client_id={CLIENT_ID}
    &redirect_uri={REDIRECT_URI}
    &scope=v2%20read%20write
    &state={CSRF}

2. Token endpoint:
POST https://www.olx.pl/api/open/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&client_id={CLIENT_ID}
&client_secret={CLIENT_SECRET}
&code={CODE}
&redirect_uri={REDIRECT_URI}
```

**Scopes:** `v2`, `read`, `write`.

**Refresh:** `grant_type=refresh_token&refresh_token={...}` (analogicznie do Allegro).

**Format tokenu:** JWT (do 4096 znaków od 2024-2026, wcześniej ~40 znaków). Kod Desala musi obsługiwać dłuższe tokeny.

### Endpointy (z OpenAPI 3.0.2)

Base URL: `https://www.olx.pl/api/partner`

| Endpoint | Metody | Tag | Skill dla Desala |
|---|---|---|---|
| `/users/me` | GET | Users | Kim jestem (pobranie ID Pana Dariusza) |
| `/users/{id}` | GET | Users | Info o sprzedawcy |
| `/regions` | GET | Cities & Districts | Lista regionów (Małopolskie) |
| `/cities` | GET | Cities & Districts | Wojnicz lookup |
| `/districts` | GET | Cities & Districts | |
| `/locations` | GET | Cities & Districts | Combined location search |
| `/categories` | GET | Categories | Drzewo kategorii (części aut) |
| `/categories/{id}` | GET | Categories | Konkretna kategoria |
| `/categories/{id}/attributes` | GET | Categories | Parametry wymagane dla kategorii ← **kluczowe dla mapowania** |
| **`/adverts`** | GET, **POST** | Adverts | **Lista własnych + tworzenie nowego ogłoszenia** |
| **`/adverts/{id}`** | GET, **PUT**, **DELETE** | Adverts | Edycja/usuwanie |
| **`/adverts/{id}/commands`** | **POST** | Adverts | **Aktywacja/dezaktywacja** (analog OtomotoModel::activate_advert) |
| `/adverts/{id}/statistics` | GET | Stats | Views, leads, contacts |
| `/threads` | GET | Threads | Wiadomości od kupujących |
| `/threads/{id}/messages` | GET, POST | Threads | Czytanie + odpowiedź ← **lead handling** |
| `/packets` | GET | Packets | Pakiety promocyjne dostępne |
| `/users/me/packets` | GET, POST | Packets | Aktywne pakiety + zakup |
| `/paid-features` | GET | Paid features | Promowanie ogłoszeń |

### Headers wymagane

```
Authorization: Bearer {access_token}
Version: 2.0                                    # WYMAGANE na każdym request
Content-Type: application/json                  # dla POST/PUT
Accept: application/json
```

### Estymata Auranet (Wariant A)

- Refaktoryzacja `OtomotoModel.php` na Car Parts API: 8-12h
- Refaktoryzacja `controllers/api/Otomoto.php` (callback OAuth): 1-2h
- Stworzenie `duo_shop_otomoto` (audit-trail): 1-2h DDL + integracja
- Test wystawienia 1 ogłoszenia na każdym marketplace'u: 2-4h
- Migracja `duo_otomoto_parameter_bind` (17 rekordów) na nowe ID kategorii: 1-2h

**Łącznie: 13-22h pracy** (1-3 dni).

## Ścieżka B — Otomoto API bezpośrednio (legacy-friendly)

### Rejestracja jako integrator Otomoto

Formularz: `https://www.otomoto.pl/news/rejestracja-api`

Wymagane pola:
- **Dane kontaktowe**: email, telefon, email konta biznesowego Otomoto
- **Dane firmy**: nazwa, NIP, ulica + numer, kod pocztowy, miejscowość
- **Aplikacja**: nazwa, opis projektu
- Akcept regulaminu (m.in. „kluczami API mogą posługiwać się wyłącznie deweloperzy, którzy osobiście tworzą i utrzymują aplikacje" — to **dotyczy Auranet**, nie Desala bezpośrednio)
- Zgoda marketingowa

**Wymóg konta Business Otomoto** — Pan Dariusz musi mieć konto Business (nie zwykłe użytkownika).

**Czas oczekiwania:** nieujawniony przez Otomoto (brak SLA w docs). Z doświadczenia w PL — od kilku dni do kilku tygodni.

### Endpointy (legacy, używane przez obecny OtomotoModel.php)

Base URL: `https://www.otomoto.pl/api/open/`

| Endpoint | Metody | W kodzie Desala |
|---|---|---|
| `/oauth/token` | POST | `OtomotoModel::get_token()` |
| `/categories` | GET | `get_categories()` |
| `/categories/{id}` | GET | `get_category_data()` |
| `/regions` | GET | `get_regions()` |
| `/cities` | GET | (`get_cities()`) |
| `/cities/search` | POST | `search_city()` |
| `/districts/for-city-id/{id}` | GET | `get_district_for_city()` |
| `/imageCollections` | POST | `create_image_collection()` |
| `/adverts/{id}` | GET | `getAdvertInfo()` |
| `/account/adverts/` | GET | `getAllAdverts()` |
| **`/account/adverts`** | **POST** | **`add_advert_from_product()`** |
| **`/account/adverts/{id}/activate`** | **POST** | `activate_advert()` |

### OAuth Flow (legacy — to co teraz w kodzie Desala)

```
POST https://www.otomoto.pl/api/open/oauth/token
Authorization: Basic base64(client_id:client_secret)
Content-Type: application/x-www-form-urlencoded
User-Agent: WebApp                              # WYMAGANE inaczej fail

# Dla client_type=user:
grant_type=password&username={EMAIL}&password={HASLO}

# Dla client_type=dealer (Otomoto Business):
grant_type=partner&partner_code={EMAIL}&partner_secret={SECRET}
```

**Problem dla Desala:** obecne konto Pana Dariusza to `client_type=user` — w 2026 ten flow ma ograniczenia (potwierdzone live: `invalid_grant: Invalid login or password`). Trzeba albo:
1. Zmienić konto na Business + użyć grant_type=partner (jeśli Pan Dariusz ma już konto Business)
2. Sprawdzić czy hasło Pana Dariusza w Otomoto jest aktualne (`koszycem1` może być stare)

### Estymata Auranet (Wariant B)

- Korekta `OtomotoModel::get_token()` (dodanie obsługi błędów + walidacja `User-Agent`): 1-2h
- Zmiana `client_type` z `user` na `dealer` w `duo_options`: 0.5h
- Aktualizacja credentials w bazie: 0.5h
- Test wystawienia 1 ogłoszenia (z nowym client_type): 1-2h
- Refaktoryzacja jeśli payload się zmienił (kategorie, parametry): **niewiadoma, do 4h**

**Łącznie (najlepszy scenariusz): 3-9h pracy.**

**Ryzyko Wariantu B:** legacy API może w przyszłości zostać wyłączone (OLX Group sygnalizuje migrację). Wariant A jest „future-proof".

## FAQ z Otomoto (potwierdzone z dokumentacji)

- **Kto może korzystać:** wyłącznie **Klienci Biznesowi** Otomoto
- **API key uniwersalny:** jeden klucz autoryzuje wiele Business accounts + test accounts (Wariant A — to KLUCZOWE dla Auranet, możemy mieć JEDEN klucz dla wielu klientów)
- **User-Agent header obowiązkowy:** wartość powinna wskazywać email konta Otomoto (np. `User-Agent: desal.tarnow@gmail.com/1.0`)
- **Postman collection:** Otomoto dostarcza, dostęp po rejestracji
- **Format odpowiedzi:** JSON
- **Test przed produkcją:** rekomendowany — Otomoto ma test accounts

## Rekomendacja Auranet

**Wybór Wariantu A (OLX Car Parts API)** z następujących powodów:

1. **4x reach** — jedna integracja wystawia jednocześnie na Otomoto + Autovit + OLX.pl + OLX.ro
2. **Future-proof** — OLX Group jednoznacznie sygnalizuje że to ich strategiczny stack
3. **Active maintenance** — w odróżnieniu od Motors API (no maintenance) i OLX API (no support)
4. **Mniej zmian w bazie** — `duo_shop_otomoto` analogiczne do `duo_shop_allegro`, jasna struktura
5. **Multi-tenant ready** — gdyby kiedyś inny klient Auranet potrzebował tego samego, ten sam klucz API wystarczy

**Estymata całkowita Auranet:** 13-22h pracy = **2-3 dni roboczych**. Plus czas Pana Dariusza na rejestrację konta OLX Group developer (1-2 dni oczekiwania na akcept).

## Linki

- OLX Group Developer Hub: https://developer.olxgroup.com/
- Rejestracja developera: https://developer.olxgroup.com/register
- Products list: https://developer.olxgroup.com/products
- OLX Polska Portal: https://developer.olx.pl/
- OLX Polska Dostęp do API: https://developer.olx.pl/articles/getting-access-to-api
- OLX Polska Swagger: https://developer.olx.pl/api/doc (JS-rendered, do otwarcia w przeglądarce)
- OpenAPI YAML wzorcowy (Ukraina, ten sam stack): https://developer.olx.ua/swagger/v2/partner_api.yaml
- Otomoto Dostęp do API: https://www.otomoto.pl/news/jak-uzyskac-dostep-do-api
- Otomoto Formularz rejestracji: https://www.otomoto.pl/news/rejestracja-api
- Otomoto FAQ: https://www.otomoto.pl/news/faq-api
- Otomoto Swagger UI: https://www.otomoto.pl/api/doc/ (JS-rendered)
- Postman collection OLX Partner: https://www.postman.com/cicada3301cicada/cicada/documentation/1y7fz4t/olx-partner-api
