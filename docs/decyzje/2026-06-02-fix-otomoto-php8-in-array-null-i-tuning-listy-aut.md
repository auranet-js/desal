# Fix regresji Otomoto/PHP8 (panel aut) + tuning wydajności listy aut

**Data:** 2026-06-02
**Wykonawca:** Auranet (Jan Schenk + Claude Code)
**Budżet:** zgłoszenie klienta (Dariusz, Desal) — „nie działa dodawanie ogłoszeń". W ramach pakietu 14h (akcept 28.05). Diagnostyka + 2 deploye.
**Powiązane:** [[2026-05-28-php-bump-7.3-do-8.3]] (ta decyzja jest jej bezpośrednią konsekwencją)

## Streszczenie

Klient zgłosił, że po wejściu w edycję auta widzi tylko 2-3 części zamiast kilku stron i brak przycisków u dołu. Diagnoza: **fatalny `TypeError` w PHP 8.3 w widoku `Cars/otomoto_attributes.php`**, ucinający renderowanie w środku pętli części. Bug był **uśpiony od ~2021** i odsłoniła go kombinacja dwóch naszych świadomych zmian z pakietu: reaktywacji Otomoto (28-29.05) i migracji na PHP 8.3 (28.05). Przy okazji — jako wartość dodana do pakietu PHP — wykonano tuning wydajności listy aut (eliminacja N+1, stronicowanie).

## Część 1 — Root cause regresji panelu aut

### Objaw
- Edycja auta pokazuje 2-3 części zamiast wszystkich (np. auto 457 ma 42 szkice w bazie — generują się poprawnie).
- Brak przycisków „Zapisz do EDYCJI" / „Zapisz i dodaj do kolejki" (są **poniżej** pętli `foreach`, więc znikają razem z uciętym outputem).

### Przyczyna
Plik `application/views/duocms/Cars/otomoto_attributes.php`, linia 20:
```php
if(!in_array($a->code, $parametersVisibility[161][$part->template->otomoto_part_type])){
```
Gdy `otomoto_part_type` części to **NULL** (138 z 279 itemów szablonu „Samochód_pełny"), `$parametersVisibility[161][null]` → klucz `""` → brak klucza → `null`. W **PHP 7.3** `in_array($x, null)` zwracało `false` + warning (niegroźne). W **PHP 8.0+ to fatalny `TypeError: in_array(): Argument #2 must be of type array, null given`** → render umiera w środku pętli.

### Łańcuch regresji (dwa warunki, oba konieczne)
| Zmiana z pakietu | Skutek |
|---|---|
| Reaktywacja Otomoto (28-29.05, token martwy od 2021-04-24) | `get_category_data()` (live API) zaczęło zwracać parametry → `!empty($otomoto)` = true → wewnętrzna pętla z `in_array` **rusza** (wcześniej pomijana: „Błąd logowania otomoto") |
| Migracja PHP 7.3 → 8.3 (28.05) | `in_array(null)` zmienił się z warningu w **fatal** |

Każda zmiana z osobna NIE ujawniłaby buga. Dlatego był niewykrywalny przed pakietem — ścieżka kodu była martwa od 5 lat (zależy od **udanego live-calla do API Otomoto**, nie od ręcznego logowania klienta — autoryzacja jest maszynowa z creds w `duo_options`).

### Fix
Lookup wyrównany do konwencji `OtomotoModel::createVisibilityArray()` (która dla pustego part_type używa klucza `0`) + guard na nie-tablicę:
```php
$oto_part_type = !empty($part->template->otomoto_part_type) ? $part->template->otomoto_part_type : 0;
$visible_codes = (isset($parametersVisibility[161][$oto_part_type]) && is_array($parametersVisibility[161][$oto_part_type])) ? $parametersVisibility[161][$oto_part_type] : [];
if(!in_array($a->code, $visible_codes)){
    continue;
}
```
Efekt: brak fatala **i** części generyczne (null part_type) odzyskują pola parametrów Otomoto (klucz `0`), zamiast je tracić.

### Weryfikacja
- Repro na lokalnym PHP 8.3.30 (prod 8.3.31): stary kod → `TypeError`, nowy → przechodzi czysto.
- `code_search` na ten wzorzec `in_array(x, $arr[..][..])` w całym `application/` → **1 wystąpienie** (tylko ta linia). Nic więcej do łatania.
- Deploy FTP, zgodność prod vs lokalny bajt w bajt.

## Część 2 — Tuning wydajności listy aut (wartość dodana)

Klient zgłosił też, że lista aut jest wolna i wymaga scrollowania na sam dół. Diagnoza w `Cars::index`:
- **N+1:** `get_car_list_with_string()` pobierało WSZYSTKIE 345 aut, a `$car->get_info()` robił **2 zapytania SUM na każde auto** = ~690 zapytań/wejście.
- Brak limitu wierszy (345 wierszy + 345 miniatur ładowanych naraz).
- Najnowsze auta na końcu listy.

### Zmiany
| Zmiana | Plik | Efekt |
|---|---|---|
| `get_info_bulk()` — 1 grupowe `SUM ... GROUP BY car_id` zamiast 2*N | `models/CarModel.php` | ~690 → 1 zapytanie |
| Stronicowanie 30/stronę (limit/offset) + `count_car_list_with_string()` | `CarModel.php`, `controllers/duocms/Cars.php`, `views/duocms/Cars/index.php` | render 30 zamiast 345 wierszy |
| Domyślny sort `id DESC` (najnowsze na górze) | `CarModel.php` | koniec scrollowania na dół |
| Wyszukiwarka POST → GET + sticky + licznik wyników | `Cars.php`, `index.php` | paginacja współgra z frazą i sortem |
| `loading="lazy" decoding="async"` na miniaturach | `index.php` | ~30 obrazków zamiast 345 |

**Sumarycznie: ~691 zapytań → 3 na wejście na listę.** Keszowanie świadomie pominięte — przy 3 zapytaniach zbędne.

### Weryfikacja poprawności
Nowe zapytanie zbiorcze daje identyczne liczby co stary per-auto: auto 445 stare `total 42850 / income 15750` = nowe `42850 / 15750`. Auta bez produktów (np. 457) poprawnie wpadają w fallback `0/0`. Lint PHP 8.3 czysty dla 3 plików, deploy zweryfikowany bajt w bajt.

## Pliki zmienione na produkcji (+ backupy na serwerze)
- `application/views/duocms/Cars/otomoto_attributes.php` — `.bak-20260602-171300`
- `application/models/CarModel.php` — `.bak-20260602-172246`
- `application/controllers/duocms/Cars.php` — `.bak-20260602-172246`
- `application/views/duocms/Cars/index.php` — `.bak-20260602-172246`

## Otwarte sprawy (poza scope)
1. **Bootstrap 3→5:** JS w `Cars/edit.php` używa `$('#dialog').modal({backdrop:'static'})` (API jQuery z BS3/4, usunięte w BS5). Działa, bo BS5 to wciąż plan. Przy migracji BS3→BS5 trzeba przepisać na natywne `bootstrap.Modal`. Patrz [[BOOTSTRAP_UPGRADE_PLAN]].
2. **SQLi w wyszukiwarce aut:** `get_car_list_with_string` interpoluje `$string` wprost do LIKE/MATCH (admin-only, backend, pre-existing). Nie ruszane w tej sesji żeby nie zmieniać zachowania — do parametryzacji osobno.

## Lessons learned (do `_duocms-playbook/`)
1. **Po migracji PHP zawsze smoke-testować widoki/ścieżki, które były MARTWE z powodu wyłączonych integracji.** Reaktywacja integracji (Otomoto) może obudzić latentny kod, który na nowym PHP wywala fatala. Klasyczna mina: dwa niezależne warunki muszą zejść się naraz, więc żaden pojedynczy test ich nie złapie. Wzorzec ryzyka reusable cross-DuoCMS (Allegro/Otomoto/inne uśpione moduły).
2. **`in_array($x, null)` i podobne (`count(null)`, `foreach(null)`) — warning w PHP 7, fatal/TypeError w PHP 8.** Przy audycie pre-migracyjnym DuoCMS grepować `in_array(`, `count(`, `array_*(` z argumentem mogącym być null. Reusable checklist do playbooka.
3. **N+1 w listach DuoCMS (`get_info()` per wiersz)** — wzorzec powtarzalny w panelach DuoCMS (lista + agregaty per rekord). Fix: jedno `GROUP BY` + mapowanie po kluczu. Tani, duży, widoczny dla klienta zysk — dobry „value-add" do doczepienia przy każdym pakiecie technicznym (np. przy bumpie PHP).
