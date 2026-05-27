# Architektura integracji — Allegro + Otomoto

> Źródło: kompletna lektura `AllegroModel.php` (54 KB) i `OtomotoModel.php` (55 KB), w połączeniu z weryfikacją live API (curl z Elary, 2026-05-27).

## 1. Allegro

### Endpointy

| Co | URL | Skąd |
|---|---|---|
| Token endpoint | `https://allegro.pl/auth/oauth/token` | hardcoded w `AllegroModel::refresh_token()` |
| OAuth redirect | `https://allegro.pl/auth/oauth/authorize?response_type=code&client_id=...&redirect_uri=...` | hardcoded w `AllegroModel::get_login_url()` |
| API REST | `https://api.allegro.pl/` | `admin_modules_allegro_link` z `duo_options` |
| Upload obrazków | `https://upload.allegro.pl/` | `admin_modules_allegro_upload_link` z `duo_options` |

Headers `allegro_query2()`:
```
content-type: application/vnd.allegro.public.v1+json
Accept: application/vnd.allegro.public.v1+json
Authorization: Bearer {access_token}
```

### Flow OAuth (z kodu)

```
User klika "Zaloguj się do Allegro" w panelu admin
  ↓
AllegroModel::get_login_url($product_id, $offer_id, $listing)
  ↓ generuje:
  https://allegro.pl/auth/oauth/authorize?
    response_type=code
    &client_id=4d7d03e1...
    &redirect_uri=https://desal.pl/api/Allegro/code_get
    &state=...
  ↓
User loguje się i daje consent → Allegro redirect do redirect_uri z ?code=XYZ
  ↓
api/Allegro::code_get()  (mam to przeczytane)
  ↓ wywołuje AllegroModel::get_token($code, $product_id)
  ↓ POST https://allegro.pl/auth/oauth/token z grant_type=authorization_code
  ↓ Odpowiedź: { access_token, refresh_token, expires_in, scope, token_type }
  ↓
set_option('admin_modules_allegro_accesstoken', $res->access_token)
set_option('admin_modules_allegro_refreshtoken', $res->refresh_token)
set_option('admin_modules_allegro_token_expiration', time() + $res->expires_in)
```

Codzienny refresh (`Cron::index2()` → `AllegroModel::refresh_token()`):
```
POST https://allegro.pl/auth/oauth/token
Authorization: Basic base64(client_id:client_secret)
grant_type=refresh_token
refresh_token={current_refresh_token}
  ↓
nowy access_token + (czasem) nowy refresh_token
  ↓
zapis ponownie do duo_options
```

**Bug obserwowany w produkcji**: gdy refresh zwróci 4xx/5xx, kod `AllegroModel::refresh_token()` zapisuje pusty `$res->access_token` bez sprawdzenia `if (!empty(...))`. To dokładnie ten stan w którym `duo_options` jest TERAZ — access i refresh puste, expiration ustawione na "expired".

### Wystawianie aukcji (`AllegroModel::add_auction_from_product($product_id)`)

Stary REST API Allegro:
1. Buduje `$offer` z `name`, `category`, `parameters` (z `duo_products.type` JSON), `images`, `sellingMode`, `stock`, `publication`, `delivery`, `payments`, `afterSalesServices`, `location`
2. `POST sale/offers` — utworzenie oferty (status INACTIVE)
3. `PUT sale/offers/{id}` — aktualizacja na status ACTIVE
4. `PUT sale/offer-publication-commands/{uuid}` — publikacja `{action: ACTIVATE}`
5. `INSERT INTO duo_shop_allegro (product_id, allegro_id, allegro_status)`

Cały flow nadal poprawny względem **aktualnego** Allegro REST API. Wymaga tylko żywego tokenu.

### Pobieranie zamówień (`AllegroModel::download_orders()`)

`GET order/events?type=READY_FOR_PROCESSING` → `GET order/checkout-forms/{id}` → mapuje na `duo_orders` z normalizacją adresu (regex polski-friendly), wykrywaniem Paczkomatu/Ruchu.

Również poprawny względem aktualnego API.

## 2. Otomoto

### Endpointy (LEGACY)

| Co | URL | Status w 2026 |
|---|---|---|
| API base | `https://www.otomoto.pl/api/open/` | redirect 301 do nowych ścieżek |
| Token | `https://www.otomoto.pl/api/open/oauth/token` | DZIAŁA (client_id 1169 rozpoznawany) ale grant types są ograniczone |
| Sandbox | `https://sbotomotopl.playground.lisbontechhub.com/api/open/` | nieaktywne |
| Developer panel | `https://developer.otomoto.pl/` | **403 CloudFront — zlikwidowany** |

### Endpointy (NOWE, OLX Group Partner)

| Co | URL | Status |
|---|---|---|
| API base | `https://api.olx.pl/partner/` | HTTP 200 — żywy |
| Token | `https://api.olx.pl/partner/oauth/token/` | 301 → wymaga auth |
| Developer panel | `https://www.olx.pl/site/partner/` | live, wymaga konta |

### Flow OAuth (LEGACY — w kodzie)

```php
// OtomotoModel::get_token()
POST https://www.otomoto.pl/api/open/oauth/token
Authorization: Basic base64(client_id:client_secret)
grant_type=password  (dla client_type='user')
username=desal.tarnow@gmail.com
password=koszycem1
```

**Resource Owner Password Flow** — zabronione w nowym OAuth 2.1. Otomoto/OLX nie usunęło endpointu, ale credentials Pana Dariusza w starym systemie są nieaktualne (`Invalid login or password`).

Headers `otomoto_query()`:
```
User-Agent: WebApp
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Wystawianie ogłoszenia (`OtomotoModel::add_advert_from_product($product_id)`)

```php
1. POST account/adverts/  z payload:
   {
     "title": substr(name, 0, 50),
     "description": "<p>Przedmiotem sprzedaży jest:</p>...",
     "category_id": 163,                            // HARDCODED "Części samochodowe"
     "region_id": get_option(...region_id),          // 4 = Małopolskie
     "city_id": get_option(...city_id),              // 19453 = Wojnicz
     "coordinates": {latitude: 49.969218, longitude: 20.874152},
     "contact": {person: "DeSal - Zakład Złomowania Pojazdów"},
     "params": json_decode(duo_products.type2),       // atrybuty z sketch_item
     "image_collection_id": ...,                       // uzyskane z POST imageCollections
     "advertiser_type": "business",
     "brand_program_id": null
   }
2. POST account/adverts/{id}/activate
3. UPDATE duo_products SET otomoto_id={id}, otomoto_url={url} WHERE id=...
```

W nowym OLX Partner API struktura JSON wystawiania jest INNA:
- pole `attributes` zamiast `params`, format obiektu
- `category_id` jako string ID
- inny endpoint `POST /partner/adverts`
- `description` z innym schematem markupu
- różnice w zarządzaniu zdjęciami (multipart upload zamiast `imageCollections`)

Wymaga pełnej refaktoryzacji.

## 3. Pipeline w Cron

```
Cron::index2()  → AllegroModel::refresh_token()   // codziennie (5x dziennie?)
Cron::index3()  → AllegroModel::download_orders() // pobieranie zamówień Allegro
Cron::car_timetable()                              // co N minut, batch 5
  ↓ FOREACH duo_allegro_timetable LIMIT 5:
  ↓   CarModel::product_from_sketch(sketch_id) → tworzy duo_products
  ↓   IF product.status=1:  AllegroModel::add_auction_from_product(product_id)
  ↓   IF product.status2=1: OtomotoModel::add_advert_from_product(product_id)
  ↓   DELETE FROM duo_allegro_timetable WHERE id=...
```

Cron wstawiany do `crontab` na hostingu **przez panel dmkhosting** (nie czytamy go z shell — zablokowane). Wnioski z logów: cron lata wielokrotnie dziennie. Z aktywności w `duo_allegro_logs`:
- najczęstsze godziny: 14:00-16:00 (mass batch produkty)
- też 09:00 (poranne)

`duo_allegro_timetable` jest wypełniane przez panel admin `duocms/Cars.php` gdy klient klika "wystaw produkty z auta" (do potwierdzenia w lekturze Cars.php).

## 4. Hardcoded values do uwagi w przyszłości

W `OtomotoModel::add_advert_from_product`:
- `category_id = 163` — "Części samochodowe" w starym Otomoto
- `coordinates = {49.969218, 20.874152}` — lokalizacja Łukanowice/Wojnicz
- `contact.person = "DeSal - Zakład Złomowania Pojazdów"` — nazwa firmy

W `AllegroModel`:
- `link = api.allegro.pl/`, `login_link = allegro.pl/`, `upload_link = upload.allegro.pl/` — w `duo_options` (override możliwy)
- `seller_id = 6265081` — w `duo_options`
- redirect_uri w `get_login_url()` hardcoded → `https://desal.pl/api/Allegro/code_get` (do sprawdzenia w pełnej lekturze AllegroModel)

## 5. Co już potwierdzono live

| Test | Wynik | Implikacja |
|---|---|---|
| `POST allegro.pl/auth/oauth/token client_credentials` z `client_id:secret` z DB | `invalid_client` | Aplikacja w panelu Allegro Developer **NIE ISTNIEJE lub jest dezaktywowana** |
| `POST otomoto.pl/api/open/oauth/token password` z `desal.tarnow@gmail.com:koszycem1` | `invalid_grant: Invalid login or password` | Hasło Pana Dariusza nieaktualne w Otomoto |
| `GET api.olx.pl/partner/categories/` | HTTP 200 (empty body bez auth) | Nowe API żywe |
| `GET developer.otomoto.pl/` | 403 CloudFront | Stary panel deweloperski **zlikwidowany** |
| Cron `index2()` żyje | tak — token_expiration zapisane DZIŚ 08:00 UTC | Cron działa, ale refresh fail bo aplikacja `invalid_client` |
