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

> **AKTUALIZACJA 2026-05-27 v2** — po pełnym research API (patrz [API_REFERENCE_OTOMOTO.md](API_REFERENCE_OTOMOTO.md)) okazało się że stary Otomoto API **dalej działa**, a OLX Group oferuje **Car Parts API** z 4 marketplace'ami w jednej integracji. Warianty zaktualizowane.

#### Wariant A — OLX Car Parts API (REKOMENDOWANE — 4x reach za 1 integrację)

OLX Group Car Parts API to **Active + supported** API obejmujący 4 marketplace'y: **Otomoto + Autovit + OLX.pl + OLX.ro**. Jedna integracja wystawia wszędzie.

**Krok 1 — rejestracja (po stronie Auranet lub Pan Dariusz):**
1. Logowanie do https://developer.olxgroup.com/register (konto OLX OAuth)
2. "Connect App" — wypełnienie:
   - Nazwa aplikacji: np. "Desal Parts Integration"
   - Opis: „Integracja DuoCMS sklepu części z rozbiórek z Car Parts API"
   - Redirect URI: `https://desal.pl/api/Otomoto/code_get`
3. Weryfikacja zespołu OLX (czas: 1-2 dni roboczych typowo)
4. Otrzymanie `client_id` + `client_secret` mailem

**Krok 2 — refaktoryzacja (Auranet):**
- Przepisanie `OtomotoModel.php`: zmiana endpointu na `api.olx.pl/api/partner/`, zmiana OAuth z password → authorization_code, dodanie callback `api/Otomoto::code_get()` (obecnie stub 374B), aktualizacja JSON struktur (header `Version: 2.0`)
- Stworzenie `duo_shop_otomoto` (audit-trail jak `duo_shop_allegro`)
- Test wystawienia 1 produktu na każdym z 4 marketplace'ów

**Estymacja Auranet (Wariant A):** 13-22h pracy = **2-3 dni**.

**Plus:** wiadomości od kupujących (`GET /threads/{id}/messages`) i statystyki ogłoszeń (`GET /adverts/{id}/statistics`) dostępne automatycznie.

#### Wariant B — Otomoto bezpośrednio (legacy-friendly, najszybszy)

Otomoto **nadal akceptuje rejestracje przez własny formularz** `otomoto.pl/news/rejestracja-api`. Wymaga konta Business Otomoto + danych firmy IT (Auranet aplikuje jako developer aplikacji, nie Pan Dariusz).

**Krok 1 — rejestracja (Auranet):**
1. Wypełnienie formularza na https://www.otomoto.pl/news/rejestracja-api
2. Akcept regulaminu („kluczami API mogą posługiwać się wyłącznie deweloperzy, którzy osobiście tworzą i utrzymują aplikacje")
3. Czas oczekiwania: niezdefiniowany (od kilku dni do kilku tygodni)
4. Klucz API uniwersalny — autoryzuje wiele Business accounts (Pan Dariusz musi mieć konto Business)

**Krok 2 — minor fix kodu (Auranet):**
- Sprawdzenie aktualności hasła Pana Dariusza w Otomoto, ewentualny reset
- Zmiana `admin_modules_otomoto_client_type` z `user` na `dealer` w `duo_options`
- Korekta `OtomotoModel::get_token()`: walidacja `User-Agent` header (Otomoto wymaga formatu `{email_konta}/1.0`)
- Test wystawienia 1 ogłoszenia

**Estymacja Auranet (Wariant B):** 3-9h pracy (zależnie od tego ile zmieniło się w payloadach Otomoto między 2021 a 2026).

**Ryzyko Wariantu B:** OLX Group sygnalizuje strategiczną migrację — legacy Otomoto API może być wycofany w nieokreślonej przyszłości.

#### Wariant C — minimalny (klient wystawia ręcznie)

Wyłączamy Otomoto w pipeline DuoCMSa. Klient wystawia przez panel Otomoto Pro. Usuwamy z UI opcje Otomoto żeby nie kusiły do klikania w niedziałającą funkcję.

**Estymacja Auranet (Wariant C):** 2-3h pracy (czyszczenie UI).

#### Wariant D — odroczone

Najpierw tylko Allegro. Otomoto omawiamy po 1-2 miesiącach.

**Estymacja Auranet (Wariant D):** 0h teraz.

### Rekomendacja Auranet (po pełnym research)

**Wariant A (OLX Car Parts API)** — najlepszy ROI bo:
1. **4x reach** — jedna integracja → Otomoto + Autovit + OLX.pl + OLX.ro
2. **Future-proof** — strategiczny stack OLX Group
3. **Active maintenance** — w odróżnieniu od stable-no-support innych OLX API
4. **Mniej zmian w bazie** — `duo_shop_otomoto` analogiczne do `duo_shop_allegro`
5. **Multi-tenant ready** — jeden klucz Auranet może obsłużyć wielu klientów (jeśli pojawi się kolejny klient w branży)

Czas Auranet zbliżony do Wariantu pierwotnego A (16-24h), ale **rezultat 4x lepszy**.

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
