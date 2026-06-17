# 2026-06-17 — Otomoto pkt 3 (synchronizacja kasowania): akcept pakietu 2000 + analiza delete-sync

**Status:** pakiet 2000 zł netto zaakceptowany przez klienta; delete-sync = osobny temat, mail z pytaniem do klienta wysłany, czeka na odpowiedź.
**Kontekst poprzedni:** [[2026-06-15-otomoto-runda2-counteroffer-2000]].

## Sytuacja

Pan Darek odpisał 16.06 (mail [76], 11:21): *„Tak. Proszę o kontynuację w tym zakresie 2000 netto."* — akcept pełnego zakresu kontroferty (multi-foto + auto-downscale + samodzielna edycja kategorii z API) za **2000 zł netto**.

Doprecyzował też uwagę #3, której wcześniej nie zakładaliśmy: *„bym tylko w jednym miejscu (albo u nas, albo w Otomoto) po sprzedaży elementu skasować ogłoszenie. Teraz muszę w dwóch miejscach kasować ogłoszenia."*

To **nie** sprzedaż jednoczesna, tylko **synchronizacja kasowania** — po sprzedaży części (która i tak następuje offline/telefonicznie, model rozbiórki, brak transakcji online) chce dezaktywować ogłoszenie w jednym kanale, a żeby zeszło z drugiego automatycznie.

## Analiza techniczna (stan faktyczny kodu + live API test)

Sprzedaż jest offline — w Otomoto nie ma zdarzenia „sprzedano", jest tylko status ogłoszenia (active/inactive) przełączany ręcznie. Stąd „obie strony" = dwa kierunki dezaktywacji:

**Kierunek 1 — panel DuoCMS → Otomoto (JUŻ ISTNIEJE jako hook synchroniczny):**
- Przycisk „SPRZEDAJ" w `application/views/duocms/Products/index.php` → `Products::stationary_sell($id)` ustawia `sold=1`/`active=1`/`quantity=0`, kończy aukcję Allegro i woła `OtomotoModel::deactivate_advert_from_product()` → `POST /account/adverts/{id}/activate` z reason „Część została sprzedana". Odwrotność: `product_return()`.

**Kierunek 2 — Otomoto → panel DuoCMS (martwy, wymaga nowego crona):**
- API Otomoto/OLX **nie ma webhooków** dla statusu ogłoszenia (`API_REFERENCE_OTOMOTO.md`) — tylko polling (`GET /adverts/{id}`, `GET /account/adverts/`).
- Logika porównania istnieje (`OnSellModel::checkAdvert` → `status != active` → `sellForOtomoto()`), ale odpala się **tylko** z `Zamowienie.php:249` przy zamówieniu na stronie, dla produktów z koszyka — nie jest scheduled. Realnie nieaktywna.
- Skala pollingu: **~11,5 tys.** ogłoszeń oznaczonych lokalnie jako żywe (`active=0` = widoczne — odwrócona konwencja; `active=1` ≈ sold ≈ 1,7 tys.). Reconcyliacja z paginacją, limitami API, obsługą błędów.

**Weryfikacja na żywym API (read-only, 2026-06-17):**
- Auth ŻYJE: OAuth password grant → HTTP 200, scope `read write` (`client_type=user`, token legacy 40-zn. w `duo_options`, ważność 12h z auto-refresh).
- Odczyt statusu DZIAŁA: `GET /account/adverts/6148682452` → 200, `"status":"active"` → **kierunek 2 wykonalny**. (Przy okazji potwierdzone: ogłoszenia mają dziś 1 zdjęcie — `photos:{"1":...}` — sens funkcji multi-foto.)
- NIEPOTWIERDZONA = ścieżka **zapisu** dezaktywacji: endpoint reuse `/activate`+reason (semantycznie podejrzany), `otomoto_query` ignoruje kod HTTP, brak logowania wyniku → ciche awarie prawdopodobne. To może tłumaczyć ból klienta („kasuję ręcznie w dwóch miejscach"). Dowód wymaga **kontrolowanego testu zapisu** na 1 ogłoszeniu (prod write, po 27.06, za zgodą Janka).

## Decyzja

1. **Delete-sync = osobna wycena, NIE w 2000** (ustalone z Jankiem 2026-06-17). Pierwotnie rzuciłem „w cenie" bez zbadania wykonalności — błąd; to godziny, nie dorzucenie funkcji.
2. **Rekomendacja architektoniczna: robić to z jednego miejsca — z panelu (kierunek 1).** Synchroniczny, panel = źródło prawdy, jedno kliknięcie zdejmuje ze wszystkich kanałów. Kierunek odwrotny (cron polling) cięższy i mniej niezawodny.
3. **Wariant + wycena zależą od tego, jak klient dziś pracuje** — dlatego najpierw pytamy, w którym systemie najpierw zdejmuje ogłoszenie po sprzedaży. Jeśli startuje z panelu → wariant minimalny (utwardzić istniejący hook: potwierdzić endpoint + obsługa błędów + logowanie + odporny token). Jeśli z Otomoto → wariant pełny dwukierunkowy (cron reconcyliacyjny).
4. **Mail do klienta wysłany 2026-06-17** (HTML, przez `send-to-jan` do Janka jako gotowiec): potwierdza pakiet 2000 (odbiór po 27.06), pyta o znajomość/użycie/działanie funkcji „Sprzedaj" w panelu oraz w którym miejscu najpierw zdejmuje ogłoszenie. Janek wysyła do Darka z DW do Claude (kopia co/kiedy).

## Konsekwencje

- Pakiet 2000 do realizacji wg diagnozy z 12.06 + picker kategorii z API, start po urlopie Janka (20–27.06); termin kalendarzowy do potwierdzenia.
- Delete-sync zablokowany do odpowiedzi klienta na pytanie o workflow.
- Otwarty dług techniczny niezależny od klienta: hook kierunku 1 bez obsługi błędów i logowania — przy okazji utwardzenia warto dograć, bo dotyczy też zwykłej sprzedaży stacjonarnej.
- Lekcja reusable cross-DuoCMS: brak weryfikacji kodu źródłowego ≠ „funkcja nie istnieje"; przed wyceną sprawdzić, co już jest spięte (tu: kompletny hook + UI istniał, tylko niepewny zapis).
