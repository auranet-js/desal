# Onboarding Klienta — co Janek dostaje od Pana Dariusza i co z tym dalej robi

> **Założenie:** klienci Auranet to właściciele firm, nie personel techniczny. Pan Dariusz daje **swój login + hasło** do paneli Allegro/Otomoto/OLX (jako sprzedawca), Janek sam wchodzi i robi rejestracje aplikacji Developer.

## 1. Co konkretnie prosimy Pana Dariusza o przesłanie

**Email lub SMS do Janka (`js@auranet.com.pl`):**

```
Konto sprzedawcy Allegro:
- login: ___
- hasło: ___
- email konta (jeśli inny niż login): ___

Konto Otomoto/OLX:
- login: ___ (najpewniej desal.tarnow@gmail.com lub powiązany)
- hasło: ___

Pytanie kontekstowe (krótkie):
- Czy konto Otomoto jest "Business" czy zwykłe?
  (sprawdza Pan na otomoto.pl po zalogowaniu → ustawienia)
```

**NIE prosimy o:** dane do panelu Developer (Pan Dariusz nie ma), tokeny, klucze, NIP (mamy z duo_options), żadnej decyzji technicznej.

**Dane wrażliwe — przekazanie:**
- Najlepiej Signal / Whatsapp encrypted message
- Albo z jednego maila usuwa po zapisaniu Janka
- Dane od razu odkładamy do `~/secrets/desal/client-credentials-2026-MM-DD.env` (chmod 600), NIGDY w repo

## 2. Allegro — krok-po-kroku rejestracja aplikacji Developer

### 2.1 Logowanie do panelu Developer

1. Janek loguje się **danymi Pana Dariusza** (te z punktu 1) na: https://allegro.pl/
2. Następnie wchodzi na: https://apps.developer.allegro.pl/
3. Powinien być automatycznie zalogowany jako Pan Dariusz

**Pułapka:** Allegro może wymagać 2FA (SMS na numer Pana Dariusza). Jeśli tak:
- Poproś Pana Dariusza o **kod SMS** przez Signal (czytanie SMS-em)
- Albo poproś o tymczasowe wyłączenie 2FA na 30 minut
- **NIE** trzymaj danych dłużej niż 1 sesja

### 2.2 Rejestracja aplikacji

W panelu apps.developer.allegro.pl:

1. **"Zarejestruj aplikację"** (przycisk u góry)
2. Wypełnij:
   - **Nazwa**: `Desal DuoCMS Integration`
   - **Typ aplikacji**: `Aplikacja webowa (Standard application)`
   - **Redirect URI**: `https://desal.pl/api/Allegro/code_get` ← **dokładnie tak, bez slash na końcu**
   - **Opis** (opcjonalny): „Automatyczne wystawianie części używanych z bazy DuoCMS na Allegro"
3. **Zaakceptuj regulamin programu Allegro Developer** (wymagane od 2024)
4. **"Utwórz"** → otrzymujemy:
   - `client_id` (32 znaki hex)
   - `client_secret` (64 znaki hex, **POKAZUJE SIĘ TYLKO RAZ** — kopiuj od razu!)

### 2.3 Co Janek robi z client_id + client_secret

1. Wpisuje do **`~/secrets/desal/api-options-snapshot-YYYY-MM-DD.env`** (zastąpienie starych wartości)
2. Następnie Auranet (Janek + Claude) aktualizuje `duo_options` przez MCP:
   ```sql
   UPDATE duo_options SET `value`='{nowy_client_id}' WHERE `key`='admin_modules_allegro_client_id';
   UPDATE duo_options SET `value`='{nowy_client_secret}' WHERE `key`='admin_modules_allegro_client_secret';
   UPDATE duo_options SET `value`='' WHERE `key`='admin_modules_allegro_accesstoken';
   UPDATE duo_options SET `value`='' WHERE `key`='admin_modules_allegro_refreshtoken';
   ```
3. **NIE wylogowuj się** z konta Pana Dariusza od razu — będzie potrzebne logowanie OAuth (ekran consent) w kroku 2.4

### 2.4 OAuth consent (autoryzacja aplikacji)

1. Janek loguje się do panelu admin Desal: https://desal.pl/duocms (kontem admina Desala — to mamy)
2. Wchodzi w sekcję Allegro
3. Klika **"Zaloguj się do Allegro"** (link z `AllegroModel::get_login_url()`)
4. Allegro przekierowuje do logowania → już zalogowany jako Pan Dariusz → **klika "Zezwalam"**
5. Allegro przekierowuje do `https://desal.pl/api/Allegro/code_get?code=...`
6. Kod jest automatycznie wymieniany na `access_token` + `refresh_token` (przez `api/Allegro::code_get()`)
7. Tokeny zapisują się w `duo_options`

**Jeśli się NIE udaje** (błąd "redirect_uri mismatch" itp.) — sprawdź:
- Czy redirect_uri w panelu apps.developer.allegro.pl jest **dokładnie taki sam** jak w kodzie (bez slash na końcu)
- Czy aplikacja w panelu ma scope `allegro:api:sale:offers:write` itd. (sprawdź sekcję "Uprawnienia")

### 2.5 Co Pan Dariusz ROBI sam (w panelu sprzedawcy, nie Developer)

- **Polityka zwrotów**: https://allegro.pl/moje-allegro/sprzedaz/warunki-sprzedazy/polityki-zwrotow
  - Wymóg: minimum 1 polityka zdefiniowana
- **Warunki reklamacji**: https://allegro.pl/moje-allegro/sprzedaz/warunki-sprzedazy/warunki-reklamacji
  - Wymóg: minimum 1
- **Cennik dostawy**: https://allegro.pl/moje-allegro/sprzedaz/dostawa-i-platnosci/cenniki-dostawy
  - Wymóg: minimum 1 cennik z metodą dostawy odpowiednią dla części aut (paczkomat, kurier, odbiór osobisty)

To jest **konfiguracja sprzedawcy**, nie API. Pan Dariusz może mieć już zdefiniowane — sprawdzimy po pierwszej próbie wystawienia.

---

## 3. OLX Group — krok-po-kroku rejestracja aplikacji Car Parts API

### 3.1 Logowanie do Developer Hub

1. Janek loguje się **danymi Pana Dariusza** (te z punktu 1) na: https://www.olx.pl/
   - (alternatywnie: jeśli Otomoto SSO zintegrowane — przez konto Otomoto)
2. Wchodzi na: https://developer.olxgroup.com/
3. Klika **"Get Started"** lub **"Register"**

### 3.2 Rejestracja aplikacji

1. **"Connect App"** (przycisk po zalogowaniu)
2. Wypełnia:
   - **Nazwa**: `Desal Parts Integration`
   - **Opis projektu**: „Integracja DuoCMS sklepu części używanych z OLX Car Parts API — automatyczne wystawianie ogłoszeń na Otomoto, Autovit, OLX.pl i OLX.ro"
   - **Product**: `Car Parts API` (NIE Motors API, NIE OLX API)
   - **Redirect URI**: `https://desal.pl/api/Otomoto/code_get`
3. Wysyła wniosek
4. Czeka 1-2 dni roboczych na **email z decyzją**

### 3.3 Po akceptacji

OLX wysyła mailem `client_id` + `client_secret`. Janek:
1. Zapisuje do `~/secrets/desal/api-options-snapshot-YYYY-MM-DD.env`
2. (Po napisaniu nowej wersji `OtomotoModel.php` przez Auranet) wpisuje do `duo_options`:
   ```sql
   UPDATE duo_options SET `value`='{olx_client_id}' WHERE `key`='admin_modules_otomoto_client_id';
   UPDATE duo_options SET `value`='{olx_client_secret}' WHERE `key`='admin_modules_otomoto_client_secret';
   -- Nowe pola (do dodania jak będziemy implementować):
   INSERT INTO duo_options (`name`,`key`,`value`,`category`,`order`,`visible`) VALUES
     ('OLX redirect_uri','admin_modules_otomoto_redirect_uri','https://desal.pl/api/Otomoto/code_get','admin_module_otomoto',0,1),
     ('OLX scope','admin_modules_otomoto_scope','v2 read write','admin_module_otomoto',0,1);
   ```
3. Janek loguje się do panelu admin Desal → sekcja Otomoto → **"Zaloguj się do OLX"** (link generowany przez nowy kod) → consent → tokeny zapisane

---

## 4. Alternatywa Otomoto (Wariant B z DIAGNOSIS.md, jeśli Wariant A nie akceptowany)

Jeśli OLX odrzuci wniosek (mało prawdopodobne ale możliwe) lub Janek chce szybszej drogi:

1. Janek wypełnia formularz: https://www.otomoto.pl/news/rejestracja-api
2. Wymagane dane (Janek dostarcza Auranet jako "firma developer aplikacji"):
   - Email: `js@auranet.com.pl`
   - Telefon: 605 335 559
   - Email konta biznesowego Otomoto: ← **od Pana Dariusza** (musi mieć Business)
   - Nazwa firmy: `Auranet Jan Schenk`
   - NIP: ← Auranet NIP (Pan Janek wpisuje)
   - Adres: ← Auranet siedziba
   - Nazwa aplikacji: `Desal DuoCMS Otomoto Integration`
   - Opis: jak wyżej dla OLX
3. Akcept regulaminu, zgoda marketingowa
4. Czekamy na email z kluczem API (nieokreślony czas)

Klucz Otomoto jest uniwersalny — autoryzuje wiele Business accounts. To znaczy że jeśli pojawi się kolejny klient Auranet z Otomoto Business, ten sam klucz wystarczy (tylko inne tokeny user-side per klient).

---

## 5. Checkpoint po onboardingu

**Po zakończeniu kroku 2 (Allegro):**
- [ ] `duo_options.admin_modules_allegro_client_id` zaktualizowane
- [ ] `duo_options.admin_modules_allegro_client_secret` zaktualizowane
- [ ] `duo_options.admin_modules_allegro_accesstoken` niepuste (po consent)
- [ ] `duo_options.admin_modules_allegro_refreshtoken` niepuste (po consent)
- [ ] Próbne wystawienie 1 produktu — OK

**Po zakończeniu kroku 3 (OLX Car Parts API):**
- [ ] OLX zatwierdził aplikację (email)
- [ ] `duo_options.admin_modules_otomoto_client_id` zaktualizowane (nowy ID OLX, nie 1169)
- [ ] `duo_options.admin_modules_otomoto_client_secret` zaktualizowane
- [ ] `OtomotoModel.php` przepisany na Car Parts API (Etap 4 Auranet)
- [ ] `duo_shop_otomoto` tabela utworzona
- [ ] Próbne wystawienie 1 ogłoszenia — OK (sprawdzić że pojawiło się na wszystkich 4 marketplace'ach)

---

## 6. Sygnał z firewalla

Po onboardingu czyścimy w `~/secrets/desal/`:
- `client-credentials-2026-MM-DD.env` (z loginami Pana Dariusza) — **USUWAMY** po skończeniu prac
- Trzymamy tylko: `db.env`, `ftp.env`, `api-options-snapshot-YYYY-MM-DD.env` (z aktualnymi kluczami aplikacji, NIE z loginami sprzedawcy)
