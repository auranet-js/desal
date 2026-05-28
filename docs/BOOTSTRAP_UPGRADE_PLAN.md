# Bootstrap upgrade plan — Desal.pl admin panel

> **Data audytu:** 2026-05-28
> **Stan obecny:** Bootstrap 3.3.7 (EOL 2019-07-24) + jQuery 1.11 + 3.2 ładowane razem + FontAwesome 4.3.0
> **Stan docelowy:** Bootstrap 5.3.x + jQuery 3.7 (tylko jeśli konieczne, BS5 nie wymaga jQuery) + FA6 + brandbook Auranet (opcjonalnie — patrz sekcja 4)
> **Powiązane:** `docs/STACK_INVENTORY.md` sekcja 3, memory `project_auraadmin_rebrand_plan.md`

## 1. Inwentaryzacja BS3 markerów w views

`code_search` w `application/views/` (capped na 30 matches) pokazuje następujące deprecated wzorce BS3:

### Layouty główne

| Plik | Rola | BS3 markery |
|---|---|---|
| `layouts/admin.php` | Main admin layout | `navbar-inverse`, `navbar-fixed-top`, `navbar-toggle`, `icon-bar`, `data-toggle="collapse"`, `col-sm-12 col-md-3` (kompatybilne), `openKCFinderImage()` callback do KCFindera |
| `partials/admin/left-col.php` | Sidebar nav (do przeczytania) | typowo MetisMenu + glyphicon |
| `duocms/Login/index.php` | Login page (osobny od main layout) | ładuje `bootstrap.min.css` lokalnie linia 6 |

### Wzorce per moduł

| Plik | BS3 wzorce wykryte |
|---|---|
| `duocms/Users/rebate_groups.php` | `panel panel-default` |
| `duocms/Recruitment/index.php` | `panel panel-default` (×2), `btn-default`, `data-toggle="modal"`, `data-dismiss="modal"` |
| `duocms/Newsletter/mails.php` | `data-toggle="modal"` (×4), `data-dismiss="modal"` (×6), `btn-default` (×4) |
| `duocms/Newsletter/menu.php` | `glyphicon glyphicon-envelope`, `glyphicon-th-list`, `glyphicon-wrench` (sidebar nav) |
| `duocms/Newsletter/config.php` | `panel panel-default` |
| `duocms/Configuration/manager.php` | `data-toggle="modal"`, `data-dismiss="modal"`, **`btn-secondary` (!)** — *częściowy refactor zaczęty, mieszany stan BS3+BS4* |
| `duocms/Products/edit.php` | `panel panel-default` |
| `duocms/Products/form.php` | `panel panel-default` (×2) |

> **Uwaga:** grep capped at 30 matches — realna liczba `panel-default`, `data-toggle`, `glyphicon` w widokach jest **prawdopodobnie 2-3× większa**. Pełna lista wymaga uncapped grep przy implementacji.

## 2. Breaking changes BS3 → BS5

### Komponenty całkowicie usunięte / przemianowane

| BS3 | BS5 | Effort per użycie |
|---|---|---|
| `panel panel-default` | `card` + `card-header`/`card-body`/`card-footer` | 5-10 linii markup do przepisania per komponent |
| `glyphicon glyphicon-*` | FontAwesome 6 lub Bootstrap Icons (oddzielna lib) | 1 linia per ikona |
| `navbar-inverse` | `navbar-dark bg-dark` (lub kolory brandbook Auranet) | 1 linia |
| `navbar-fixed-top` | `fixed-top` jako osobna klasa | 1 linia |
| `navbar-toggle` + `icon-bar` | `navbar-toggler` + `navbar-toggler-icon` | 5 linii |
| `pull-left` / `pull-right` | `float-start` / `float-end` | 1 linia |
| `btn-default` | `btn-secondary` | 1 linia (regex replace) |
| `hidden-xs` / `visible-md` etc. | `d-none d-md-block` etc. (display utilities) | 1 linia |

### Atrybuty data-* (BS5 prefix)

| BS3 / BS4 | BS5 |
|---|---|
| `data-toggle="modal"` | `data-bs-toggle="modal"` |
| `data-dismiss="modal"` | `data-bs-dismiss="modal"` |
| `data-target="#foo"` | `data-bs-target="#foo"` |
| `data-toggle="collapse"` | `data-bs-toggle="collapse"` |
| `data-toggle="dropdown"` | `data-bs-toggle="dropdown"` |

Regex replace, ale **musi być całościowy** — partial state wprowadzi cichy fail (modale przestają działać).

### JS dependencies

| BS3 wymagał | BS5 wymaga |
|---|---|
| jQuery 1.9+ | **Vanilla JS** (jQuery opcjonalny, większość API standalone) |
| `bootstrap.min.js` (single file) | `bootstrap.bundle.min.js` (zawiera Popper.js dla dropdowns/tooltips) |
| n/a | Popper.js v2 (bundled) |

**Konsekwencja:** możemy całkowicie usunąć jQuery 1.11.1 + jQuery Migrate 1.2.1 z layout admin. Zostawić jQuery 3.7 tylko jeśli inne pluginy (DataTables, Chosen, Slick) tego wymagają — sprawdzić per użycie.

## 3. Zakres effortu

| Sub-zadanie | Pliki (~) | Effort |
|---|---|---|
| Layout main admin (navbar BS3→BS5) | 1 (`admin.php`) | 2h |
| Sidebar layout (`left-col.php`) | 1 | 1h |
| Login page (osobny layout) | 1 | 0.5h |
| `panel panel-default` → `card` refactor | 10-15 | 3-5h |
| `data-toggle`/`data-dismiss` → `data-bs-*` regex | 20-30 | 1-2h |
| `glyphicon` → FA6 lub BS Icons | 5-10 | 1-2h |
| `btn-default` → `btn-secondary` regex | 10-15 | 0.5h |
| `pull-left`/`pull-right` → `float-start`/`float-end` regex | nieznane | 0.5h |
| `hidden-*` / `visible-*` → `d-*` utilities regex | nieznane | 1h |
| Bootstrap-multiselect (BS3-specific widget) → BS5 replacement | 1-3 użyć | 2-3h |
| Bump `bootstrap.min.css` + `bootstrap.bundle.min.js` 3.3.7 → 5.3.x | assets | 0.5h |
| jQuery cleanup 1.11 + 3.2 + Migrate → 3.7 only (gdzie potrzebny) | assets + ~5 plików | 1-2h |
| FontAwesome 4.3 → 6 (przy okazji `glyphicon` cleanup) | assets + selektory | 1-2h |
| Smoke test całego panelu (każdy moduł CRUD) | n/a | 3-4h |
| **Bez brandbook Auranet razem:** | | **17-25h** |

### + Opcjonalny AURAADMIN rebrand (przy okazji)

| Sub-zadanie | Effort |
|---|---|
| Wymiana `logo_duonet.png` → logo Auranet (lub neutralne AURAADMIN) | 0.5h |
| Footer `© 2018 duonet.eu` → `Auranet • AURAADMIN` (lub neutralne) | 0.5h |
| Kolory brandbook Auranet w navbar/sidebar/buttons primary | 2-3h |
| Typografia brandbook (font-family, sizing) | 1-2h |
| **Razem z brandbook Auranet:** | **+4-6h** |

**Suma:**
- **Wersja A (BS5 only, bez rebrand):** 17-25h
- **Wersja B (BS5 + AURAADMIN podstawowy):** 21-31h
- **Wersja C (BS5 + AURAADMIN + custom design Auranet):** 25-40h+

## 4. Decyzja architekturalna — czy razem z AURAADMIN rebrand?

**Argument ZA połączeniem (A+B w jednej fazie):**
- Każdy plik view dotykamy w ramach BS5 refactor — dorzucenie rebrandu to +20% effortu zamiast osobnego projektu za 6 miesięcy
- Klient widzi „nowy panel" jednorazowo — nie dwie kolejne przebudowy
- Reusable jako template dla pozostałych instalacji DuoCMS (Victorini, Agria, JanSchenk, Wydrukinasztuki) — chcemy żeby AURAADMIN był spójny od początku
- Memory `project_auraadmin_rebrand_plan.md` wskazuje że to long-term plan — okazja teraz jest naturalna

**Argument PRZECIW (BS5 only, rebrand osobno):**
- Klient płaci za upgrade techniczny — może nie chcieć płacić za branding
- Rebrand jest reversible — można odłożyć
- Klient może mieć preferencję wizualną (np. zachować zielony Desal w panelu admin)

**Default rekomendacja:** **B (BS5 + AURAADMIN podstawowy)** — logo + footer + neutralne kolory Auranet, **bez** custom designu. Klient otrzymuje nowy, spójny panel za rozsądny dodatkowy koszt. Pełen brandbook Auranet (custom design) zostawiamy jako osobny upsell.

## 5. Plan kolejności prac

1. **Faza A** — utworzenie branch `feature/bootstrap-5-upgrade`, backup pełny (DB + files)
2. **Faza B** — refactor layouts (`admin.php`, `left-col.php`, `Login/index.php`) → smoke test loginu i nawigacji
3. **Faza C** — regex replacements masowe (`data-toggle` → `data-bs-toggle` itd.) → grep verification
4. **Faza D** — refactor `panel panel-default` → `card` (manual per plik, większy effort) → moduł po module smoke test
5. **Faza E** — `glyphicon` → FA6, `btn-default` → `btn-secondary` (regex) → wizualny review
6. **Faza F** — Bump assets (BS3.3.7 → BS5.3.x CSS/JS, FA4 → FA6) → cross-browser test
7. **Faza G** — jeśli AURAADMIN: logo, footer, kolory primary
8. **Faza H** — smoke test pełny: każdy moduł admin (Products, Orders, News, Pages, Newsletter, Cars, Allegro, Otomoto, Users, Gallery, Custom_elements, Templates, Configuration)
9. **Faza I** — deployment na staging (jeśli istnieje) → akcept Janka → cutover na produkcję

## 6. Cross-DuoCMS implication

Inne instalacje DuoCMS w portfolio Auranet (Victorini, Agria, JanSchenk, Wydrukinasztuki, supportowe) **najpewniej mają ten sam stack BS3 + jQuery 1.11/3.2 + FA4 + glyphicon**. Janek planuje sprzedaż upgrade-pakietu cross-DuoCMS.

**Rekomendacja:** wynik tej pracy → `~/projekty/_duocms-playbook/BOOTSTRAP_BS3_TO_BS5.md` z procedurą per-instalacja. Sed/regex skrypty (`replace_data_toggle.sh`, `replace_panel_to_card.sh`) reusable. Estymata per-instalacja maleje z 17-25h (case 0) do ~12-18h (powtórki, mając gotowe skrypty + checklistę).

## 7. Powiązane dokumenty

- `docs/STACK_INVENTORY.md` — sekcja 3 (biblioteki front-end), kontekst całego stacku
- `docs/KCFINDER_TEST_RESULT_2026-05-28.md` — wymiana KCFindera idzie naturalnie razem z layout refactor (callback API w `admin.php` linie 78-90)
- Memory: `project_auraadmin_rebrand_plan.md` — kontekst długoterminowy rebrandu
- Memory: `feedback_duocms_playbook_reusability.md` — reusable cross-portfolio
