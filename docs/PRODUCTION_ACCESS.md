# Dostępy do produkcji — ściąga

> **Sekrety w `~/secrets/desal/` na Elarze, NIE w tym repo.**
> Ten plik trzyma tylko *gdzie* i *jak*, NIE *co* (żadnych haseł, tokenów, kluczy).

## Strona klienta

- **Domena:** https://desal.pl
- **Firma:** Desal sp. z o.o. — Zakład Złomowania Pojazdów
- **Lokalizacja:** Łukanowice 214, 32-830 Wojnicz
- **Właściciel kontaktowy:** Pan Dariusz
- **Branża:** rozbiórki aut + sprzedaż używanych części (sklep WWW + Allegro + Otomoto)

## Hosting

| Co | Wartość |
|---|---|
| Provider | dmkhosting |
| Serwer | srv3.anyservers.com (LiteSpeed) |
| Site root | `/home6/desal/public_html` |
| Konto Linux | `desal` (home6) |
| SSH | **brak** |
| Panel | dmkhosting (przeglądarka), tam phpMyAdmin |

## MCP desal-duocms (PREFEROWANE)

- **Endpoint:** `https://desal.pl/kLADte2rC4ckDP8m/mcp.php`
- **Token:** w konfigu Claude Code MCP (`claude mcp get` lokalnie na Elarze)
- **14 tooli:** `status`, `stats`, `tables`, `list_dir`, `read_file`, `write_file`, `backup_file`, `rm`, `query_db`, `logs`, `cron`, `listings`, `integrations_status`, `ping`
- **Limity:**
  - `read_file` cap 512 KB
  - `query_db` SELECT cap 200 wierszy; INSERT/UPDATE/DELETE/DDL wymagają `confirm=true` + tabela na whiteliście `MCP_DESTRUCTIVE_TABLES` (obecnie pusta — patrz [Zmiany destruktywne DB](#zmiany-destruktywne-db))

## FTP (FALLBACK)

> Używaj wyłącznie gdy MCP nie wystarczy: pliki >512KB, masowy transfer, awarie MCP.

- **Host:** `ftp.desal.dmkhosting.net`
- **Protokół:** FTP plain (bez TLS — legacy hosting)
- **Port:** 21
- **User:** `auranet@desal.pl`
- **Hasło:** w `~/secrets/desal/ftp.env`
- **Klient:** WinSCP (Janek lokalnie) lub `lftp` na Elarze

## phpMyAdmin

- Dostępny przez panel dmkhosting
- Używaj **wyłącznie do wglądu**, gdy `query_db` coś sygnalizuje a chcesz potwierdzić wzrokowo
- Wszystkie modyfikacje DB → MCP `query_db`

## Baza danych

| Co | Wartość |
|---|---|
| Silnik | MariaDB 10.11.16 |
| Nazwa | `desal_duonet` |
| User | `desal_duonet` |
| Host | `localhost` (socket UNIX) |
| Prefix | `duo_` |
| Hasło | `~/secrets/desal/db.env` |

## Zewnętrzne konta — które klient (Pan Dariusz) musi mieć

**Allegro:**
- Konto sprzedażowe Allegro (seller_id `6265081`, miasto Tarnów, woj. MALOPOLSKIE, kod 33-100)
- Aplikacja w panelu Allegro Developer (client_id `4d7d03e1...`) — musi mieć ważny consent OAuth
- Login: do dopytania Pana Dariusza (najpewniej `desaltarnow@gmail.com` lub powiązany)

**Otomoto / OLX Group:**
- Konto sprzedawcy: `desal.tarnow@gmail.com` (z `duo_options`)
- Aplikacja partnerska w panelu OLX Group Partner (NIE istnieje — stara była tylko na legacy api.otomoto.pl, trzeba zarejestrować nową w panelu Otomoto Pro / OLX Partner)

**PayU:** POS ID `934436`, klucze w `duo_options` (production)
**P24:** Sandbox URL — najpewniej nieaktywny w prod
**Google Maps:** klucz `AIzaSyCD5DDEbpaLOU8lUFALxmUlO5XTjPXRDCE` (publiczny site-key — nie wrażliwe)
**reCAPTCHA v2/v3:** klucze w `duo_options`
**InPost:** sandbox JWT (wygasły 2018) — do regeneracji
**SMTP:** `desal.pl:587` user `noreply@desal.pl`

## Sekrety lokalnie

`~/secrets/desal/` (700, pliki 600):

| Plik | Co |
|---|---|
| `db.env` | hasło DB MariaDB |
| `ftp.env` | dane FTP |
| `api-options-snapshot-2026-05-27.env` | snapshot wszystkich kluczy API z `duo_options` |

## Zmiany destruktywne DB

`duo_options.value.MCP_DESTRUCTIVE_TABLES` w `kLADte2rC4ckDP8m/mcp.php` jest obecnie **pusty**. Aby wykonać UPDATE/INSERT/DELETE na produkcji:

1. Edytuj lokalnie `kLADte2rC4ckDP8m/mcp.php` (przez MCP `read_file` → modyfikacja → MCP `write_file` — robi auto-backup `.bak-*`)
2. Dodaj tabelę do `MCP_DESTRUCTIVE_TABLES`
3. Wykonaj `query_db` z `confirm=true`
4. Usuń tabelę z whitelist po zakończeniu (defense in depth)
5. Commit lokalny

## Konwencja backupów

- MCP `write_file` zapisuje `.bak-YYYYMMDD-HHiiss` obok edytowanego pliku
- MCP `rm` zapisuje `.bak-rm-YYYYMMDD-HHiiss`
- Przed znaczącą zmianą DB → `mysqldump` via panel dmkhosting do `~/backups/desal/<data>/`
- Backupy MCP są w `.gitignore` (wzorzec `*.bak-*`)

## Co możesz robić bez pytania

- Read tylko: `read_file`, `list_dir`, `query_db` SELECT, `logs`, `stats`, `tables`, `status`, `integrations_status`
- Backupy ręczne: `backup_file`

## Co MUSISZ potwierdzić z Jankiem PRZED wykonaniem

- `write_file` na pliki produkcyjne w `application/` (admin / cron / models / configi)
- `rm` cokolwiek
- `query_db` z `confirm=true` (UPDATE/DELETE/INSERT/DDL)
- Zmiany w `kLADte2rC4ckDP8m/mcp.php` (rozszerzenie destructive whitelist)
- Modyfikacje `.htaccess`
- Tworzenie/usuwanie userów DB lub WP
- Restart usług (nawiasem — przez panel hostingu, nie shell)

## Co MUSISZ poprosić Pana Dariusza

- Login do panelu Allegro Developer
- Założenie aplikacji w panelu **Otomoto Pro / OLX Group Partner** (nowy schemat — patrz `DIAGNOSIS.md`)
- Login do panelu Otomoto/OLX
- Potwierdzenie że konto Allegro w Tarnowie nadal istnieje i jest aktywne sprzedażowo
