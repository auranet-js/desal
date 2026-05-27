# desal

Diagnostyka i naprawa integracji **Otomoto + Allegro** w starym DuoCMSie klienta **Desal.pl** (Zakład Złomowania Pojazdów, Łukanowice/Wojnicz).

Klient prowadzony przez **Auranet** (Jan Schenk). Pan Dariusz — właściciel Desala.

## Status

Projekt zainicjowany: 2026-05-27. Repo: GitHub `auranet-js/desal`.

- Etap 1 (rekonesans READ-ONLY) — zamknięty. `docs/RECONNAISSANCE.md`
- Etap 2 (mirror kodu integracji) — w toku
- Etap 3 (diagnoza) — wstępna w `docs/RECONNAISSANCE.md`, pełna w `docs/DIAGNOSIS.md`
- Etap 4 (naprawa) — czeka na decyzje

## Dostępy

`docs/PRODUCTION_ACCESS.md` — ściąga (bez haseł).
Sekrety: `~/secrets/desal/` (700/600).

## Stack produkcyjny

- DuoCMS na CodeIgniter (PHP 7.3.33 LiteSpeed)
- MariaDB 10.11.16 baza `desal_duonet`, prefiks `duo_`
- Hosting dmkhosting / srv3.anyservers.com
- Site root `/home6/desal/public_html`

## Narzędzia

- **MCP desal-duocms** (`mcp__claude_ai_Desal__*`) — preferowane. 14 tooli.
- **FTP** — `ftp.desal.dmkhosting.net`, fallback dla plików >512KB.
- **phpMyAdmin** — wgląd, panel dmkhosting.
- Brak SSH.

## Struktura

```
desal/
├── docs/
│   ├── RECONNAISSANCE.md     Etap 1 — stan wyjściowy
│   ├── INTEGRATIONS.md       Architektura Allegro + Otomoto
│   ├── SCHEMA.md             DDL tabel związanych
│   ├── DIAGNOSIS.md          Etap 3 — root cause + plan
│   ├── PRODUCTION_ACCESS.md  Ściąga dostępów (bez haseł)
│   └── MCP_ROADMAP.md        Rozbudowa MCP desal-duocms
├── src/
│   ├── config/               Kopia application/config/* (read-only mirror)
│   ├── controllers/          Kopia application/controllers/* (read-only mirror)
│   │   ├── api/
│   │   ├── cron/
│   │   └── duocms/
│   │       └── Otomoto/
│   ├── libraries/
│   └── models/
├── .gitignore
└── README.md
```

## Workflow

1. `git pull` na początku każdej sesji
2. Czytamy/edytujemy lokalnie w `src/`
3. Deploy przez MCP `write_file` (auto-backup `.bak-*` na produkcji)
4. Commit po deploy
5. Destruktywne DB: edycja `MCP_DESTRUCTIVE_TABLES` w `kLADte2rC4ckDP8m/mcp.php` → diff → wgrywka → `query_db` z `confirm=true`

## Konwencja commitów

`[obszar] krótki opis` — `init`, `docs`, `mirror`, `feat`, `fix`, `chore`.
Przykład: `[mirror] AllegroModel + OtomotoModel`, `[fix] Otomoto OAuth na OLX Partner API`.
