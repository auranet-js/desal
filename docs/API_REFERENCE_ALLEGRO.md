# Allegro REST API — referencja aktualna

**Źródło:** https://developer.allegro.pl/documentation + https://developer.allegro.pl/tutorials/uwierzytelnianie-i-autoryzacja-zlq9e75GdIR
**Fetched:** 2026-05-27
**Wersja API:** `public.v1` (current)

## TL;DR dla naprawy Desala

- Endpointy **identyczne ze starym kodem** — `AllegroModel.php` jest poprawny w zarysie. Wymaga jedynie nowych credentials (po re-rejestracji aplikacji) + drobnej łatki w `refresh_token()` (sprawdzenie czy `$res->access_token` nie pusty przed `set_option`).
- Refresh token żyje **3 miesiące** — od ostatniej autoryzacji 2021-04-16 minęło ~60 wygaśnięć.
- Access token żyje **12 godzin** — nie wymaga ręcznej akcji, cron `index2` robi to automatycznie (jak działa).

## OAuth 2.0 — 4 flow

### Authorization Code (Desal używa tego)

```
1. Redirect user do:
GET https://allegro.pl/auth/oauth/authorize?
    response_type=code
    &client_id={CLIENT_ID}
    &redirect_uri={REDIRECT_URI}
    &scope=allegro:api:sale:offers%20allegro:api:sale:orders%20...
    &state={CSRF_TOKEN}

2. User loguje się + daje consent → Allegro redirect:
{REDIRECT_URI}?code={CODE}&state={CSRF_TOKEN}

3. Exchange code → tokens:
POST https://allegro.pl/auth/oauth/token
Authorization: Basic base64(client_id:client_secret)
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&code={CODE}&redirect_uri={REDIRECT_URI}

Response:
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "refresh_token": "eyJ...",
  "expires_in": 43199,
  "scope": "allegro:api:sale:offers ...",
  "jti": "..."
}
```

### Refresh Token (cron Desala)

```
POST https://allegro.pl/auth/oauth/token
Authorization: Basic base64(client_id:client_secret)
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token&refresh_token={REFRESH_TOKEN}
```

**Refresh token validity:** 3 miesiące. Po pierwszym użyciu stary token ważny dodatkowe 60 sekund (grace period).

### Device Flow

Dla aplikacji bez GUI. Polling endpoint. Nie używane w Desalu.

### Client Credentials

Bez user consent. Dostęp tylko do publicznych zasobów. Nie używane do wystawiania aukcji.

### Dynamic Client Registration (DCR)

Programowa rejestracja aplikacji bez manualnego klikania w panelu. Dla operator-grade integracji.

## Endpointy do sprzedaży

| Metoda | Endpoint | Skill |
|---|---|---|
| POST | `/sale/product-offers` | Tworzenie oferty |
| PATCH | `/sale/product-offers/{offerId}` | Edycja oferty |
| GET | `/sale/product-offers/{offerId}` | Pobranie oferty |
| GET | `/sale/product-offers/{offerId}/parts` | Wybrane sekcje oferty |
| GET | `/sale/offers` | Lista ofert (z filtrami) |
| PUT | `/sale/offer-publication-commands/{commandUuid}` | Publikacja/aktywacja |
| GET | `/sale/categories` | Drzewo kategorii |
| GET | `/sale/categories/{categoryId}/parameters` | Parametry kategorii (wymagane atrybuty) |
| GET | `/sale/shipping-rates` | Cenniki wysyłki |
| GET | `/sale/shipping-rates/{id}` | Szczegóły cennika |
| POST | `/sale/images` | Upload zdjęcia (binary, host: `upload.allegro.pl`) |
| GET | `/sale/delivery-methods` | Metody dostawy |
| GET | `/after-sales-service-conditions/return-policies` | Polityki zwrotów |
| GET | `/after-sales-service-conditions/implied-warranties` | Warunki reklamacji |

Dla zamówień:
| Metoda | Endpoint | Skill |
|---|---|---|
| GET | `/order/events` | Stream nowych zdarzeń (READY_FOR_PROCESSING etc.) |
| GET | `/order/checkout-forms` | Lista zamówień |
| GET | `/order/checkout-forms/{id}` | Szczegóły zamówienia |

## 22 scope dla sprzedawcy

| Scope | Operacje |
|---|---|
| `allegro:api:profile:read` | Konto, email, ratingi |
| `allegro:api:profile:write` | Edycja email |
| `allegro:api:sale:offers:read` | Read offers, products, categories |
| `allegro:api:sale:offers:write` | Create/edit/delete offers, discounts |
| `allegro:api:orders:read` | Read order details, shipments |
| `allegro:api:orders:write` | Add tracking, change status |
| `allegro:api:shipments:read` | Read shipment data, labels |
| `allegro:api:shipments:write` | Create shipments, request pickups |
| `allegro:api:ratings` | Manage ratings and comments |
| `allegro:api:disputes` | Manage transaction disputes |
| `allegro:api:bids` | Place bids, view offers |
| `allegro:api:messaging` | Manage Message Center |
| `allegro:api:billing:read` | View balance and fees |
| `allegro:api:payments:read` | View payment history |
| `allegro:api:payments:write` | Issue refunds |
| `allegro:api:sale:settings:read` | Read size tables, pickup points |
| `allegro:api:sale:settings:write` | Edit delivery/return policies |
| `allegro:api:campaigns` | Manage promotions and campaigns |
| `allegro:api:fulfillment:read` | Read fulfillment info |
| `allegro:api:fulfillment:write` | Manage fulfillment operations |
| `allegro:api:affiliate:read` | Read affiliate data |
| `allegro:api:affiliate:write` | Manage affiliate data |

**Dla Desala minimum:** `allegro:api:sale:offers:write` + `allegro:api:orders:read` + `allegro:api:orders:write` + `allegro:api:sale:settings:read` + `allegro:api:profile:read`.

**Format żądania:** scope separated `%20` (URL-encoded space).

## Headers wymagane

```
Authorization: Bearer {access_token}
Accept: application/vnd.allegro.public.v1+json
Content-Type: application/vnd.allegro.public.v1+json   # dla POST/PUT/PATCH
Accept-Language: pl-PL   # opcjonalnie (pl-PL, en-US, uk-UA, sk-SK, cs-CZ, hu-HU)
User-Agent: {AppName}/{Version} (+{URL})              # WYMAGANE, format strict
```

## Token TTLs i limity

| Element | Wartość |
|---|---|
| Access token | 12 godzin |
| Refresh token | 3 miesiące |
| Authorization code | 10 sekund |
| DCR code | 2 minuty |
| Max sesji równoczesnych per user | 20 |
| Refresh after first use grace | 60 sekund |
| Rate limit | 429 Too Many Requests gdy przekroczony |

**Access token traci ważność gdy:**
- 12h pass
- User logs out from all devices
- User changes password
- User changes email
- User's seller account blocked
- User removes app connection
- User exceeds 20 active sessions

## PKCE (rekomendowane od 2024)

Dodaje do Authorization Code Flow:
```
code_verifier: 43-128 random chars
code_challenge_method: S256
code_challenge = BASE64URL(SHA256(code_verifier))
```

Authorize:
```
GET .../authorize?...&code_challenge={CHALLENGE}&code_challenge_method=S256
```

Token exchange:
```
POST .../token
grant_type=authorization_code&code={CODE}&redirect_uri={URI}&code_verifier={VERIFIER}&client_id={ID}
```

## Sandbox

| Produkcja | Sandbox |
|---|---|
| `https://allegro.pl/auth/oauth/` | `https://allegro.pl.allegrosandbox.pl/auth/oauth/` |
| `https://api.allegro.pl/` | `https://api.allegro.pl.allegrosandbox.pl/` |
| `https://apps.developer.allegro.pl/` | `https://apps.developer.allegro.pl.allegrosandbox.pl/` |

## Plan migracji Desala (dla Etapu 4)

1. **Pan Dariusz** loguje do `https://apps.developer.allegro.pl/`
2. (jeśli aplikacja nie istnieje) Rejestracja **nowej aplikacji "Web"**:
   - Nazwa: "Desal DuoCMS Integration"
   - Redirect URI: `https://desal.pl/api/Allegro/code_get`
   - Type: "Standard application" (Web)
3. Akcept regulaminu sprzedawcy (wymóg od 2024)
4. Otrzymanie `client_id` + `client_secret`
5. **Auranet** aktualizuje `duo_options` przez MCP query_db (z confirm + whitelist):
   ```sql
   UPDATE duo_options SET `value`='{new_client_id}' WHERE `key`='admin_modules_allegro_client_id';
   UPDATE duo_options SET `value`='{new_client_secret}' WHERE `key`='admin_modules_allegro_client_secret';
   UPDATE duo_options SET `value`='' WHERE `key`='admin_modules_allegro_accesstoken';
   UPDATE duo_options SET `value`='' WHERE `key`='admin_modules_allegro_refreshtoken';
   ```
6. **Łatka** w `AllegroModel::refresh_token()` (do napisania w Etapie 4):
   ```php
   // PRZED zapisem:
   if (empty($res->access_token)) {
       $this->load->model('LogModel');
       $this->LogModel->add_log(-2, 0, 'Allegro refresh fail: '.json_encode($res));
       return false;
   }
   // ZAPIS:
   set_option('admin_modules_allegro_accesstoken', $res->access_token);
   ...
   ```
7. Klient w panelu admin DuoCMSa klika "Zaloguj się do Allegro" → consent → tokeny zapisane
8. Test: wystawienie 1 produktu via `AllegroModel::add_auction_from_product()`
9. Wymuszenie batch: `INSERT INTO duo_allegro_timetable (product_id) SELECT id FROM duo_car_sketches WHERE allegro=1 AND ...`
10. Czeka na cron `car_timetable`

**Estymata pracy Auranet:** 3-5h kodu + 1-2h testów po dostarczeniu credentials.

## Linki

- Główna dokumentacja: https://developer.allegro.pl/documentation
- Tutorial Auth: https://developer.allegro.pl/tutorials/uwierzytelnianie-i-autoryzacja-zlq9e75GdIR
- Panel rejestracji: https://apps.developer.allegro.pl/
- Sandbox: https://apps.developer.allegro.pl.allegrosandbox.pl/
- Connected Apps user-side: https://allegro.pl/moje-allegro/moje-konto/powiazane-aplikacje
- Generate DCR Code: https://allegro.pl/uzytkownik/bezpieczenstwo/wygeneruj-kod
- Postman collection: dostępna z poziomu developer.allegro.pl/documentation
- OpenAPI YAML: prawdopodobnie pod `https://developer.allegro.pl/api/v1/openapi.yaml` (do potwierdzenia)
