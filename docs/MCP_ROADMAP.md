# MCP `desal-duocms` — Roadmap rozbudowy

**Bieżąca wersja:** 1.0.0 (14 tooli, mtime 2026-05-27 09:54)
**Cel:** wersja 1.1.0 (+6 tooli z grup A+B)

## Nowe tooly

### Grupa A — Diagnostyka głębsza (read-side)

| Tool | Status | Args | Cel |
|---|---|---|---|
| `code_search` | TODO | `pattern, path, case_sensitive, max_results, exclude` | grep PCRE po `application/` (exclude `system/`, `cache/`, `logs/`) — szybkie szukanie wystąpień w 27k linii kodu |
| `file_tree` | TODO | `path, max_depth, include_files, max_entries` | rekursywne drzewo katalogów z limit głębokości — lepiej niż wiele `list_dir` |
| `db_schema` | TODO | `tables (array lub pattern)` | bulk `SHOW CREATE TABLE` dla wielu tabel jednorazowo |
| `log_tail_grep` | TODO | `table, pattern, column=message, limit=50, types=[]` | tail z regex filter na `duo_allegro_logs.message` — szukanie iglej w 44k wierszy logów |
| `php_info` | TODO | brak | structured `phpinfo()` jako JSON (loaded modules, ini, PHP_OS, server_signature) |

### Grupa B — Integracje sandbox

| Tool | Status | Args | Cel |
|---|---|---|---|
| `curl_proxy` | TODO | `url, method, headers, body, auth_basic, bearer, timeout, follow_redirects` | curlowy wrapper z **whitelist domen** (allegro.pl, otomoto.pl, olx.pl, api.olx.pl, developer.olxgroup.com itd.) — testowanie żywych API z IP serwera Desala (a nie naszego Elary). Kluczowe dla Etapu 4: weryfikacja czy odbywający się refresh_token Allegro skutkuje błędem `invalid_client` z perspektywy serwera klienta. |

## Architektura zmian w `mcp.php`

1. `MCP_VERSION = '1.1.0'`
2. Nowa stała: `$GLOBALS['MCP_CURL_WHITELIST']` — lista dozwolonych domen
3. Nowe helpery:
   - `mcp_url_in_whitelist($url, $whitelist)`
   - `mcp_recursive_scan($dir, $exclude_patterns, $max_files)` (dla `code_search`/`file_tree`)
4. Nowe funkcje `tool_*` (6 sztuk)
5. Aktualizacja `mcp_tools_list()` — dodanie 6 wpisów
6. Aktualizacja `mcp_dispatch_tool()` — dodanie 6 cases w switch

## Pułapka: anti-suicide

Aktualny mcp.php blokuje sam siebie przed nadpisaniem przez `write_file`:
```php
$GLOBALS['MCP_BLOCKED_WRITE'] = ['mcp.php', '.htaccess'];
```

→ **Nie można wgrać nowej wersji przez MCP** (write_file zwróci "Blocked file").

**Rozwiązanie:** Janek wgrywa nową wersję **przez FTP** (WinSCP):
- Source na Elarze: `~/projekty/desal/kLADte2rC4ckDP8m/mcp.php` (mirror lokalny, do commitu)
- Cel produkcji: `/home6/desal/public_html/kLADte2rC4ckDP8m/mcp.php`
- Połączenie: dane w `~/secrets/desal/ftp.env`
- Auto-backup MCP nie zadziała (write_file zablokowany) → Janek **kopiuje stary plik na disk lokalny pod nazwą `mcp.php.bak-2026-05-27-AUTORWGRANIE`** przed nadpisaniem produkcji, na wypadek rollback'a

## Testy po wgraniu

Po wgraniu nowego mcp.php Janek/Claude testuje przez MCP każdy nowy tool:

```
ping                                              # potwierdzenie że stary endpoint żyje
tools/list (JSON-RPC)                             # potwierdzenie że nowych 6 widać
code_search pattern="add_advert_from_product"      # powinno znaleźć w OtomotoModel.php + Cron.php
file_tree path="application/controllers" max_depth=2
db_schema tables=["duo_shop_allegro","duo_otomoto_parameter_bind"]
log_tail_grep table="duo_allegro_logs" pattern="błąd|error" limit=20
php_info                                          # pełen phpinfo() jako JSON
curl_proxy url="https://allegro.pl/auth/oauth/token" method=POST headers={"Content-Type":"application/x-www-form-urlencoded"} body="grant_type=client_credentials" auth_basic="{NEW_CLIENT_ID}:{NEW_CLIENT_SECRET}"
```

Po sukcesie — commit lokalnego mirror'a + push.

## Grupa C (do rozważenia w przyszłej iteracji 1.2.0)

| Tool | Cel |
|---|---|
| `patch_file` | unified diff zamiast `write_file` (audit-friendly) |
| `git_local_diff` | porównanie pliku w repo vs produkcji |
| `deploy_from_repo` | bezpieczny deploy z repo z mapping `src/**` → `application/**` |
| `db_dry_run` | EXPLAIN + `BEGIN; ...; ROLLBACK` żeby pokazać efekt UPDATE/DELETE bez commit'u |
| `destructive_lockfile` | whitelisting tabel jako plik JSON modyfikowalny przez MCP (zamiast edit mcp.php) |
| `cron_register` / `cron_runner` | uruchamianie pojedynczego CI cron-job on-demand (omija zablokowany shell_exec przez wbudowane wywołanie CI router-a) |
| `events_stream` | SSE z najświeższymi wpisami z `duo_allegro_logs` co N sekund (live tail w trakcie wymuszonego cron-runu) |
| `metrics_snapshot` | gęstość logów per godzina, error rate per typ, top-N błędów ostatnie 7 dni |

To duża inwestycja (5-10h pracy nad MCP), realna gdy Etap 4 (naprawa) będzie wymagał deploy ciężkich zmian w `OtomotoModel.php`.

## Plan wgrania w sesji

1. Auranet (Claude) przygotuje pełny nowy `mcp.php` v1.1.0 w `~/projekty/desal/kLADte2rC4ckDP8m/mcp.php` (mirror)
2. Plus drop na `https://auratest.pl/fe4f58fec53ctmp/desal-mcp-php-v1.1.0.php` dla łatwego download'u
3. Janek wgrywa przez WinSCP do `/home6/desal/public_html/kLADte2rC4ckDP8m/mcp.php` (overwrite)
4. Test przez MCP w Claude Code: `tools/list` powinno pokazać 20 tooli (14 + 6)
5. Test każdego nowego toola
6. Commit lokalnego mirror'a + push

**Token MCP w pliku** — zostaje ten sam (`ZywxF79Q84Q7W9tv`). Konfiguracja MCP po stronie Claude Code nie wymaga zmian.
