# SECURITY_INCIDENT_2026-05-27

**Status:** w naprawie
**Wykryto:** 2026-05-27 ~12:50 UTC (GitHub Secret Scanning alert)
**Rozwiązano w repo (lokalnie):** 2026-05-27 ~12:55 UTC (`git commit --amend`)
**Rotacja klucza Google:** OCZEKUJEMY na Pana Dariusza
**Force push do origin:** OCZEKUJEMY na decyzję Janka

## Co się stało

W commicie `d83f4a1` (root commit repo `auranet-js/desal`, push 2026-05-27 ~12:46 UTC) znalazł się w pliku `docs/PRODUCTION_ACCESS.md` linia 75:

```
**Google Maps:** klucz `AIzaSy***` (publiczny site-key — nie wrażliwe)
```

Plus fragment Allegro client_id w linii 66 (`4d7d03e1...` — częściowo).

GitHub Secret Scanning rozpoznał wzorzec `AIza*` (Google API Key) i wystawił alert.

## Co już zrobiliśmy (Auranet, lokalnie)

1. `docs/PRODUCTION_ACCESS.md` — wartości sekretów zastąpione pointerami do `~/secrets/desal/api-options-snapshot-YYYY-MM-DD.env`
2. `git commit --amend` — nowy hash commita `12d2afc` (zastąpił `d83f4a1`)
3. Stan repo lokalnie: `working tree clean`, branch zdivergowany od `origin/main` (1 commit ahead, 1 behind)
4. Re-scan repo grep'em: brak innych sekretów (`AIza*`, `[a-f0-9]{32,64}` w docs/ i src/)

## Co JESZCZE trzeba zrobić

### Po stronie Auranet (Janek):
1. **Akcept force push** do `origin/main` — niezbędny do usunięcia `d83f4a1` z historii zdalnej
2. Komenda gotowa: `git push --force-with-lease origin main`

### Po stronie Pana Dariusza:
1. **Rotacja klucza Google Maps API** (jedyna realna obrona przed leak'iem):
   - Login do Google Cloud Console: https://console.cloud.google.com/
   - Projekt: ten, do którego należy klucz `gmap_key` w `duo_options`
   - "APIs & Services" → "Credentials"
   - Wybierz stary klucz → **Delete** (lub **Regenerate**)
   - Wygeneruj nowy klucz z **HTTP referrer restriction** ograniczonym do `https://desal.pl/*`, `https://*.desal.pl/*`
   - Wpisać nowy klucz do `duo_options.gmap_key` przez MCP query_db (whitelist + confirm=true)
2. **Sprawdzenie billingu** w Google Cloud — czy stary klucz nie był nadużywany (geocoding API ma koszt po przekroczeniu free tier ~ $200/miesiąc)

## Dlaczego "publiczny" Google Maps API key też jest sekretem

Frontendowe klucze Google Maps są **technicznie publiczne** (wchodzą do HTML/JS źródła strony), ALE:
- Jeśli klucz **nie ma HTTP Referrer restriction**, każdy może go używać → naliczanie kosztów Pana Dariusza
- Klucz w niewłaściwych rękach + brak kwoty/limitu = potencjalne $1000+ rachunek
- Best practice: nigdy w git, nawet w docs/. Zawsze placeholder + sekret poza repo.

## Prewencja na przyszłość

1. **Pre-commit hook** — sprawdzenie `grep -rE 'AIza[0-9A-Za-z_-]{35}|sk_live_|pk_live_'` przed każdym commit'em
2. **`.gitignore` rozszerzony** o wzorce sekretów (jeśli przypadkiem ktoś założy taki plik)
3. **Konwencja**: wszystkie sekrety projektu — `~/secrets/<projekt>/` (sekcja 15 globalnego CLAUDE.md). docs/ trzyma **pointery**, NIE wartości.
4. **Code review** dokumentów przed commit'em — grep secrets

## Co to oznacza dla integracji Allegro/Otomoto

Klucze Allegro/Otomoto w `duo_options` (client_id, client_secret) **NIE były ujawnione w repo** — siedzą w `~/secrets/desal/api-options-snapshot-2026-05-27.env` (700/600, poza repo). Bez ingerencji.

Klucz Google Maps **nie jest powiązany z Allegro/Otomoto** — wykorzystywany na froncie strony Desal.pl do mapy lokalizacji (Łukanowice).

Jedyny realny wpływ: koszt naliczany Panu Dariuszowi za fałszywe użycie klucza zanim nie zrotuje. Skala — zależy od limitu/quotaego klucza i czasu między leakiem (push 12:46) a rotacją.

## Linki do alertu

- GitHub repo: `git@github.com:auranet-js/desal.git`
- Alert: `docs/PRODUCTION_ACCESS.md#L75` przy commicie `d83f4a1e` (zostanie usunięty po force push)
- Nowy commit lokalnie: `12d2afc`
