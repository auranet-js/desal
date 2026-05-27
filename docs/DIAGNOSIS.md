# Diagnoza — co popsute i jak naprawić

**Data analizy:** 2026-05-27
**Wykonał:** Auranet (Jan Schenk) na zlecenie Desal sp. z o.o.
**Status:** diagnoza zamknięta, plan naprawy do akceptu Pana Dariusza

## Stwierdzony stan

Obie integracje (Allegro + Otomoto) są **nieczynne**, ale z różnych przyczyn:

| Kanał | Status faktyczny | Ostatnia aktywność |
|---|---|---|
| Allegro — nowe aukcje | nie wystawiane | 2021-04-16 |
| Allegro — odświeżanie tokenu | wykonywane codziennie, **nie udaje się** | dziś 2026-05-27 08:00 UTC |
| Allegro — pobieranie zamówień | nie potwierdzone (zależy od tokenu) | — |
| Otomoto — nowe ogłoszenia | **nigdy nie wystawione** (zero rekordów) | — |
| Otomoto — mapowanie parametrów | porzucone | 2021-07-07 |
| Cron (`Cron::car_timetable`) | działa, ale nie ma co przetwarzać | dziś 09:00 |
| Cron (część → produkt) | DZIAŁA codziennie | dziś 09:00 |

## ROOT CAUSE — Allegro

### Co wykazaliśmy live

Próba uwierzytelnienia z client_id/client_secret z bazy klienta:
```
POST https://allegro.pl/auth/oauth/token
Authorization: Basic base64(4d7d03e1...:61kR3MhUpM6X...)
grant_type=client_credentials
```

Odpowiedź serwera Allegro:
```json
{"error":"invalid_client","error_description":"Client authentication failed"}
```

### Wnioski

1. **Aplikacja w panelu Allegro Developer już NIE ISTNIEJE** (lub jest dezaktywowana). To NIE jest sprawa wygaśnięcia tokenu — to sprawa unieważnienia całej aplikacji.
2. Najpewniejsza przyczyna: Allegro od 2024 wymusiło **migrację aplikacji do nowego panelu** (stary `apps.developer.allegro.pl` → nowy ekosystem). Aplikacje, które nie zostały zmigrowane, zostały usunięte.
3. **Drugi bug**: nawet gdyby aplikacja żyła, w `AllegroModel::refresh_token()` jest defekt — gdy refresh zwraca błąd, kod nadpisuje `access_token` w `duo_options` pustą wartością z `$res->access_token` (które jest `null`). To dlaczego token jest pusty w bazie.

### Plan naprawy Allegro

**Krok 1 — po stronie Pana Dariusza (wymagana akcja Klienta):**
1. Logowanie do panelu **Allegro Developer**: https://apps.developer.allegro.pl/
2. (jeśli aplikacja nie istnieje) Rejestracja **nowej aplikacji** typu „aplikacja Web":
   - Nazwa: "Desal DuoCMS Integration" (dowolna)
   - Redirect URI: **`https://desal.pl/api/Allegro/code_get`**
   - Scope: minimum `allegro:api:sale:offers`, `allegro:api:sale:orders`, `allegro:api:sale:offers:read`, `allegro:api:profile`
3. Otrzymanie nowego `client_id` + `client_secret`
4. Akceptacja regulaminu sprzedawcy (wymóg od 2024)
5. Przekazanie credentials do Auranet

**Krok 2 — po stronie Auranet (godziny pracy):**
1. Aktualizacja `duo_options`:
   - `admin_modules_allegro_client_id` ← nowy
   - `admin_modules_allegro_client_secret` ← nowy
2. Naprawa bugu w `AllegroModel::refresh_token()`:
   - Dodanie `if (!empty($res->access_token))` przed zapisem do `set_option`
   - Logowanie błędu refresh do `duo_allegro_logs` (type=-2)
   - Opcjonalnie email alert do administratora gdy refresh fail
3. Test ręcznej autoryzacji: panel admin → "Zaloguj się do Allegro" → consent → tokeny zapisane
4. Wystawienie testowej aukcji z 1 produktu → potwierdzenie że pipeline action publikuje na Allegro
5. Wymuszenie batch wystawienia narastającej kolejki: `duo_allegro_timetable` wypełnić wybranymi sketch_id → poczekać na cron

**Estymacja Auranet (Allegro):** 2-4h pracy programistycznej + 1-2h testów (po dostarczeniu nowych credentials przez klienta).

**Ryzyka:**
- (R1) Konto sprzedażowe Allegro Klienta mogło wymagać dodatkowej weryfikacji przez Allegro Compliance po 4 latach bezczynności. Jeśli tak — proces 2-7 dni roboczych.
- (R2) Allegro mógł zmienić wymagania dotyczące "after-sales services" (impliedWarranty, returnPolicy) — być może trzeba zaktualizować polityki w panelu sprzedawcy.

## ROOT CAUSE — Otomoto

### Co wykazaliśmy live

**Próba 1** (client_credentials z starymi credentials):
```
POST https://www.otomoto.pl/api/open/oauth/token
Authorization: Basic base64(1169:ff2d8f46...)
```
Odpowiedź:
```json
{"error":"unauthorized_client","error_description":"The authenticated client is not authorized to use this authorization grant type."}
```
→ client_id 1169 jest **rozpoznawany** ale ma ograniczone uprawnienia.

**Próba 2** (password grant z hasłem z bazy):
```
grant_type=password&username=desal.tarnow@gmail.com&password=koszycem1
```
Odpowiedź:
```json
{"error":"invalid_grant","error_description":"Invalid login or password"}
```
→ hasło `koszycem1` jest **nieaktualne**.

**Próba 3** (sprawdzenie nowego API):
```
GET https://api.olx.pl/partner/categories/
→ HTTP 200 (pusty body bez auth)
GET https://api.olx.pl/partner/oauth/token
→ HTTP 301 (endpoint istnieje)
GET https://developer.otomoto.pl/
→ HTTP 403 CloudFront (panel zlikwidowany)
```

### Wnioski

1. **Otomoto przeniosło developer relations do OLX Group Partner** (od 2022-2023):
   - Stary panel `developer.otomoto.pl` zlikwidowany
   - Stara aplikacja Pana Dariusza (`client_id=1169`) wciąż istnieje w starym systemie, ale ograniczona — nie może już wystawiać nowych ogłoszeń przez stare API.
2. Hasło Pana Dariusza w Otomoto najpewniej zmienione (reset wymuszony przez OLX Security) lub konto przeniesione do OLX Group SSO bez przeniesienia auth do legacy API.
3. **Nawet po reset password — nie naprawi.** Bo Otomoto **od 2024 nie pozwala na nowe wystawienia przez legacy API**. Wymaga przejścia na OLX Partner.
4. Pipeline `OtomotoModel.php` w obecnym kształcie **NIE BĘDZIE działał na nowym API**. Wymaga refaktoryzacji.

### Plan naprawy Otomoto

Tutaj proponujemy **decyzję strategiczną** — pełne lub minimalne podejście.

#### Wariant A — pełna naprawa (rekomendowane jeśli klient sprzedaje też przez Otomoto regularnie)

**Krok 1 — po stronie Pana Dariusza:**
1. Logowanie do panelu OLX (konto: `desal.tarnow@gmail.com`)
2. Rejestracja jako **OLX Group Partner**: https://www.olx.pl/site/partner/
3. Złożenie wniosku o dostęp do **OLX Partner API** (proces weryfikacji — typowo 1-4 tygodnie)
4. Po akceptacji — otrzymanie nowych credentials (client_id, client_secret, dokumentacja)
5. Przekazanie credentials do Auranet

**Krok 2 — po stronie Auranet:**
1. Refaktoryzacja `OtomotoModel.php`:
   - Zmiana endpointu: `www.otomoto.pl/api/open/` → `api.olx.pl/partner/`
   - Zmiana OAuth: Resource Owner Password Flow → Authorization Code Flow
   - Dodanie callback'a `api/Otomoto::code_get()` (obecnie stub 374B) — analogiczny do `api/Allegro::code_get()`
   - Aktualizacja struktur JSON ogłoszenia (kategorie, atrybuty, lokalizacja w nowym formacie)
   - Aktualizacja `add_advert_from_product()`, `activate_advert()`, `deactivate_advert()`, `getAdvertInfo()`, `get_categories()` itd.
2. Stworzenie tabeli `duo_shop_otomoto` (analogicznej do `duo_shop_allegro`) z audit-trailem ogłoszeń
3. Test wystawienia 1 produktu
4. Migracja istniejących mapowań parametrów (`duo_otomoto_parameter_bind` — 17 rekordów, prawdopodobnie wymaga aktualizacji ID kategorii)

**Estymacja Auranet (Otomoto wariant A):** 16-24h pracy programistycznej (2-3 dni) + 4-6h testów.

#### Wariant B — minimalny (rekomendowane jeśli klient i tak wystawia na Otomoto ręcznie, niska skala)

Pozostawiamy Otomoto wyłączone w pipeline DuoCMSa. Klient wystawia ręcznie przez panel Otomoto Pro (nowy panel OLX). Usuwamy z UI panelu admin Desala opcje Otomoto, żeby nie kusiły do klikania w niedziałającą funkcję.

**Estymacja Auranet (Otomoto wariant B):** 2-3h pracy (czyszczenie UI).

#### Wariant C — odroczone (rekomendowane jeśli klient chce skupić się na Allegro)

Najpierw naprawiamy tylko Allegro. Otomoto omawiamy osobno po obserwacji jak Allegro działa.

**Estymacja Auranet (Otomoto wariant C):** 0h teraz, decyzja po 1-2 miesiącach od naprawy Allegro.

## Czego potrzebujemy od Pana Dariusza

### Konieczne (bez tego nie ruszymy z Allegro):
1. Login do panelu Allegro Developer (`apps.developer.allegro.pl`) **albo** zgoda na założenie nowej aplikacji w jego imieniu (przekażemy nazwę użytkownika do podstawienia)
2. Potwierdzenie że konto sprzedażowe Allegro nadal istnieje i ma aktualną politykę zwrotów + warunki reklamacji
3. Decyzja: **który scope** integracji Allegro włączamy (sprzedaż, zarządzanie, ofertami, zamówieniami)

### Konieczne dla Otomoto (jeśli wariant A):
4. Login do konta OLX/Otomoto (`desal.tarnow@gmail.com` lub zmieniony)
5. Złożenie wniosku partnerskiego w panelu OLX Partner — Auranet może pomóc, ale wniosek musi być z konta Klienta
6. Akceptacja regulaminu OLX Group Partner

### Dodatkowe odkrycia (warto się ustosunkować):
- **Dane innego klienta w konfiguracji**: `duo_options` zawiera wpisy z poprzedniego projektu klienta o nazwie "Septem" — np. login Facebooka, email Sendit, eNadawca login (`info@septemonline.com`, `jakub.o@septemonline.com`). Te konfigi są martwe. Czy klient chce żeby Auranet to wyczyściło?
- **InPost sandbox z 2018**: token JWT wygasł, link API to sandbox (`sandbox-api-shipx-pl.easypack24.net`). Czy klient korzysta z InPost? Jeśli tak — wymaga aktualizacji na produkcyjny token.

## Decyzja do podjęcia

Najmniejszy sensowny next step: **Wariant Allegro pełen + Otomoto wariant C (odroczone)**. To uruchamia mostek do największego kanału sprzedażowego (Allegro) i daje czas na ocenę czy Otomoto się jeszcze opłaca.

Czekamy na decyzję Pana Dariusza co do zakresu i terminu.
