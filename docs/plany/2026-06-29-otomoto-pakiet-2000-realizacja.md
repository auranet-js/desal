# Otomoto pakiet 2000 — plan realizacji

> **Dla wykonawcy (agent/człowiek):** kroki mają checkboxy (`- [ ]`) do śledzenia. To legacy CodeIgniter 3 / DuoCMS na **produkcji bez harnessu testowego** — „test" = weryfikacja na żywym API Otomoto (MCP `curl_proxy`/`query_db`) i w panelu, nie PHPUnit. Każda zmiana kodu: najpierw `backup_file` (MCP), deploy przez `write_file` (MCP), potem mirror do repo `~/projekty/desal` + commit. Edytujemy bezpośrednio na serwerze (FTP zwraca 530 — patrz memory `feedback_use_ftp_proactively_no_human_clicks`).

**Cel:** dostarczyć dwie funkcje z zaakceptowanej oferty 2000 zł netto — (1) samodzielną edycję kategorii Otomoto z listą pobieraną z API i jej **faktyczne użycie** w wystawianym ogłoszeniu, (2) kilka zdjęć na ogłoszenie (2/4/więcej) z dopasowaniem rozdzielczości do Otomoto.

**Architektura:** DuoCMS (CI3) wystawia ogłoszenia przez `OtomotoModel::add_advert_from_product()` (cron `Cron/car_timetable`). Kategorie cache'ujemy w istniejącej, pustej tabeli `duo_category_otomoto` (odświeżanej z API), panel czyta z cache. Zdjęcia: część dziedziczy dziś 1 zdjęcie ze szkicu (`CarModel::product_from_sketch`); rozszerzamy szkic na wiele zdjęć i kopiujemy wszystkie do `duo_product_photos` — push do Otomoto już iteruje całą galerię.

**Tech stack:** PHP 8.3, CodeIgniter 3, MySQL (prefix `duo_`), Otomoto Open API (legacy, `client_type=user`, token auto-refresh), SimpleImage (`application/third_party/SimpleImage.php`), plupload (panel).

## Global Constraints

- PHP 8.3 — kod musi być 8.3-safe (bez `in_array(null,...)`, sprawdzaj `!empty()` przed iteracją; patrz ADR `2026-06-02-fix-otomoto-php8-in-array-null...`).
- Produkcja LIVE, brak staging. Każdy test wystawienia robimy na **jednym** ogłoszeniu i po weryfikacji **dezaktywujemy** je (`deactivate_advert`). Zapis na produkcyjnym API Otomoto wymaga zgody Janka (memory: prod write = pytaj).
- Konwencja Otomoto: `duo_products.active=0` = WIDOCZNE (odwrócona), `status2=1` = „idzie do Otomoto".
- Prefix tabel `duo_` doklejany automatycznie przez CI — w kodzie modeli używamy nazw BEZ prefiksu (`$this->db->insert('product_photos', ...)` → tabela `duo_product_photos`).
- Token Otomoto: NIE wołać API surowym curlem w nowym kodzie — używać `OtomotoModel::otomoto_query()` (ma User-Agent + bearer + auto-refresh).
- Commity do repo `~/projekty/desal`, branch `main`, konwencja `[obszar] opis`, trailer `Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>`.
- Budżet pakietowy — decyzje techniczne podejmujemy proaktywnie, bez pytań „robimy X w cenie?" (memory `feedback_no_in_scope_questions_when_budget_accepted`).

---

## FAZA A — Kategorie (self-service + faktyczne użycie)

> Największa wartość i najlepiej rozpoznane. Rdzeń: dziś `add_advert_from_product` **hardcoduje `category_id => 163`** i ignoruje zapisaną `duo_products.otomoto_category_id`. Picker (dropdown live z API) już istnieje w `form-temp.php` i `otomoto_attributes.php`, ale wybór nie trafia do ogłoszenia.

### Task A1: Użyj wybranej kategorii w payloadzie ogłoszenia

**Files:**
- Modify: `application/models/OtomotoModel.php` (metoda `add_advert_from_product`, linia z `"category_id" => 163,` ~207)

**Interfaces:**
- Consumes: `$product->otomoto_category_id` (kolumna `duo_products.otomoto_category_id`, int; 0/NULL = nieustawiona).
- Produces: ogłoszenie wystawione z kategorią wybraną przez operatora; przy braku — fallback 163.

- [ ] **Step 1: Backup pliku przed edycją**

MCP: `backup_file` path `application/models/OtomotoModel.php`.

- [ ] **Step 2: Podmień hardcoded category_id**

W tablicy `$in` w `add_advert_from_product` zmień:

```php
    "category_id" => 163,
```

na:

```php
    "category_id" => !empty($product->otomoto_category_id) ? (int)$product->otomoto_category_id : 163,
```

Deploy: MCP `write_file`.

- [ ] **Step 3: Weryfikacja składni i braku regresji listy**

MCP `php_info` / otwórz panel listy produktów (`duocms/products`) — strona ładuje się bez fatala (PHP 8.3).

- [ ] **Step 4: Mirror do repo + commit**

```bash
cd ~/projekty/desal
# pobierz aktualny plik z serwera do repo (przez MCP read_file → zapis) lub zsynchronizuj application/models/OtomotoModel.php
git add application/models/OtomotoModel.php
git commit -m "[feat+otomoto] użyj otomoto_category_id w payloadzie zamiast hardcoded 163"
```

- [ ] **Step 5: Test live (po zgodzie Janka)** — patrz Task A4 (łączny test fazy A).

### Task A2: Cache kategorii w duo_category_otomoto + odświeżanie z API

> Dziś `getCategoriesArray(1)` woła API przy KAŻDYM otwarciu formularza (wolne + zależne od tokenu). Klient chce „lista z API, nowe kategorie pojawiają się same". Rozwiązanie: cache w `duo_category_otomoto` (tabela istnieje, 0 wierszy), odświeżany cronem dziennie + przyciskiem „Odśwież kategorie".

**Files:**
- Modify: `application/models/CategoryOtomotoModel.php` (dodanie `refresh_from_api()` + `get_all_cached()`)
- Modify: `application/controllers/duocms/Otomoto.php` (akcja `refresh_categories()`)
- Modify: `application/controllers/cron/Cron.php` (metoda `otomoto_categories_refresh()`)
- Reference: `application/models/OtomotoModel.php::getCategoriesArray()` (źródło danych z API)

**Interfaces:**
- Consumes: `OtomotoModel::get_categories(1)` (zwraca `->results[]` z `id`, `names->pl`).
- Produces: `CategoryOtomotoModel::get_all_cached()` → `array[ id => name ]` do dropdownów; akcja panelu `duocms/Otomoto/refresh_categories`; cron URL `/pl/cron/Cron/otomoto_categories_refresh`.

- [ ] **Step 1: Sprawdź schemat duo_category_otomoto**

MCP `query_db`: `SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='duo_category_otomoto'` — potwierdź kolumny (oczekiwane: `id`/`otomoto_id`, `name`). Jeśli brak adekwatnych kolumn, dołóż migrację (kolumny: `otomoto_id BIGINT`, `name VARCHAR(255)`, `updated_at DATETIME`).

- [ ] **Step 2: Backup + dopisz refresh_from_api() do CategoryOtomotoModel**

```php
    public function refresh_from_api() {
        $this->load->model('OtomotoModel');
        $cats = (new OtomotoModel())->get_categories(1); // ->results[]
        if (empty($cats->results)) return 0;
        $this->db->truncate('category_otomoto');
        $n = 0;
        foreach ($cats->results as $r) {
            if (empty($r->id) || empty($r->names->pl)) continue;
            $this->db->insert('category_otomoto', [
                'otomoto_id' => $r->id,
                'name'       => $r->names->pl,
                'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ]);
            $n++;
        }
        return $n;
    }

    public function get_all_cached() {
        $out = [];
        foreach ($this->db->order_by('name', 'asc')->get('category_otomoto')->result() as $r) {
            $out[$r->otomoto_id] = $r->name;
        }
        return $out;
    }
```

Deploy MCP `write_file`.

- [ ] **Step 3: Akcja panelu „Odśwież kategorie"**

W `application/controllers/duocms/Otomoto.php` dodaj:

```php
    public function refresh_categories() {
        $this->load->model('CategoryOtomotoModel');
        $n = (new CategoryOtomotoModel())->refresh_from_api();
        setAlert($n ? 'success' : 'error', $n ? "Zaktualizowano kategorie Otomoto ($n)." : 'Nie pobrano kategorii z Otomoto.');
        redirect(site_url('duocms/Otomoto'));
    }
```

Dodaj przycisk w widoku ustawień Otomoto (`views/duocms/Otomoto/settings.php`) linkujący do `duocms/Otomoto/refresh_categories`.

- [ ] **Step 4: Cron odświeżania**

W `application/controllers/cron/Cron.php` dodaj:

```php
    public function otomoto_categories_refresh() {
        $this->load->model('CategoryOtomotoModel');
        $n = (new CategoryOtomotoModel())->refresh_from_api();
        echo "Kategorie Otomoto odświeżone: $n";
    }
```

Dodaj wpis crontab (przez cPanel API — memory `reference_cpanel_api`): `curl -L https://desal.pl/pl/cron/Cron/otomoto_categories_refresh` raz dziennie. **Uwaga:** `-L` obowiązkowe (incydent HSTS — memory `incident_https_hardening_killed_crons`).

- [ ] **Step 5: Uruchom refresh i potwierdź zapis**

Wywołaj akcję panelu lub cron URL, potem MCP `query_db`: `SELECT COUNT(*) FROM duo_category_otomoto` — oczekiwane > 0.

- [ ] **Step 6: Commit**

```bash
git add application/models/CategoryOtomotoModel.php application/controllers/duocms/Otomoto.php application/controllers/cron/Cron.php application/views/duocms/Otomoto/settings.php
git commit -m "[feat+otomoto] cache kategorii Otomoto w duo_category_otomoto + refresh (panel/cron)"
```

### Task A3: Dropdowny kategorii czytają z cache (self-service)

**Files:**
- Modify: `application/controllers/duocms/Templates.php` (zamień `getCategoriesArray(1)` → cache, 3 miejsca: ~79, ~119, ~194)
- Modify: `application/controllers/duocms/Cars.php` (~157 `getCategoriesArray(1)`)
- Reference: `application/views/duocms/Templates/form-temp.php:14`, `application/views/duocms/Cars/otomoto_attributes.php:4-10`

**Interfaces:**
- Consumes: `CategoryOtomotoModel::get_all_cached()`.
- Produces: dropdowny kategorii w szablonie i per-część zasilane z cache (szybkie, niezależne od tokenu w czasie edycji).

- [ ] **Step 1: Backup + podmiana źródła w Templates.php**

W każdym z 3 miejsc zamień:

```php
$this->OtomotoModel->getCategoriesArray(1)
```

na:

```php
$this->CategoryOtomotoModel->get_all_cached()
```

i dodaj `$this->load->model('CategoryOtomotoModel');` w konstruktorze lub akcji. Deploy `write_file`.

- [ ] **Step 2: To samo w Cars.php (~157).**

- [ ] **Step 3: Weryfikacja w panelu**

Otwórz formularz szablonu (`duocms/Templates`) i edycję auta z częściami — dropdown kategorii pokazuje pełną listę z cache, zaznaczona wartość = zapisana `otomoto_category_id`. Brak fatala PHP 8.3.

- [ ] **Step 4: Commit**

```bash
git add application/controllers/duocms/Templates.php application/controllers/duocms/Cars.php
git commit -m "[feat+otomoto] dropdowny kategorii z cache zamiast live API per request"
```

### Task A4: Test live fazy A — wystawienie z wybraną kategorią

- [ ] **Step 1: Wybierz część testową** — MCP `query_db`: znajdź produkt `status2=1`, `otomoto_id IS NULL/0`, z ustawionym `otomoto_category_id` ≠ 163 (lub ustaw ręcznie na jednej części testowej).

- [ ] **Step 2: Wystaw przez cron** — wywołaj `https://desal.pl/pl/cron/Cron/createotomotoad/{productId}` (`-L`). 

- [ ] **Step 3: Zweryfikuj kategorię w Otomoto** — pobierz świeży token (OAuth password grant) i `GET /account/adverts/{otomoto_id}` (MCP `curl_proxy`); potwierdź, że `category_id` w odpowiedzi = wybrana kategoria, nie 163.

- [ ] **Step 4: Sprzątnij** — `deactivate_advert({otomoto_id})` (za zgodą Janka) albo zostaw jeśli to realna, poprawna pozycja.

- [ ] **Step 5: Log decyzji** — dopisz wynik do `docs/decyzje/` jeśli wyszły niespodzianki (np. kategoria wymaga innych params).

---

## FAZA B — Kilka zdjęć na ogłoszenie

> Push do Otomoto już iteruje całą galerię (`add_advert_from_product` pętla po `findAllPhotos()`), a `saveImage` skaluje do max 1600px. Wąskie gardło: **część dziedziczy 1 zdjęcie ze szkicu** (`CarModel::product_from_sketch` kopiuje pojedyncze `$sketch->image`). Rozszerzamy szkic na wiele zdjęć.

### Task B0 (SPIKE): zmapuj upload zdjęcia szkicu w kreatorze

> Niewiadoma do domknięcia przed implementacją: gdzie operator wgrywa zdjęcie szkicu i jak `duo_sketches` (lub analog) trzyma `image`. Bez tego nie zaprojektujemy multi-uploadu.

- [ ] **Step 1:** MCP `query_db`: `SELECT COLUMN_NAME,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME LIKE '%sketch%'` — ustal tabelę szkiców i kolumnę obrazka.
- [ ] **Step 2:** MCP `code_search` pattern `sketch` w `application/controllers/duocms/Cars.php` i `application/views/duocms/Cars/edit.php` — znajdź akcję uploadu zdjęcia szkicu + JS plupload.
- [ ] **Step 3:** Przeczytaj `CarModel::get_sketch()` i `add_sketch`/`save_sketch`. Zapisz w tym pliku (poniżej) ustalenia: tabela szkiców, ścieżka `uploads/sketch/{id}/`, czy upload to single czy queue.
- [ ] **Step 4: Decyzja projektowa** — czy multi-foto trzymamy jako (a) nowa tabela `duo_sketch_photos(sketch_id, name, order)` [REKOMENDOWANE — spójne z `duo_product_photos`], czy (b) JSON w kolumnie. Domyślnie (a).

**Wynik spike'a (do uzupełnienia przez wykonawcę):** _tabela szkiców = …, kolumna obrazka = …, akcja uploadu = …, ścieżka = …_

### Task B1: Wiele zdjęć szkicu — storage

**Files:**
- Create (migracja): `application/phinx/migrations/XXXXXX_create_sketch_photos_table.php` (tabela `duo_sketch_photos`: `id`, `sketch_id`, `name`, `order`, `created_at`)
- Modify: `CarModel` (metody `add_sketch_photo($sketch_id,$name)`, `get_sketch_photos($sketch_id)`)

**Interfaces:**
- Produces: `CarModel::get_sketch_photos($sketch_id)` → lista `{name, order}`; tabela `duo_sketch_photos`.

- [ ] **Step 1:** Utwórz migrację wg wzoru `20161021102713_create_product_photos_table.php`. Uruchom (`phinx migrate` lub ręczny DDL przez MCP `query_db` confirm=true jeśli tabela na whiteliście; inaczej DDL przez `exec_safe`).
- [ ] **Step 2:** Dodaj metody do `CarModel`. Deploy. Commit `[feat+otomoto] storage wielu zdjęć szkicu (duo_sketch_photos)`.

### Task B2: Kreator — multi-upload zdjęć per szkic

**Files:**
- Modify: `application/controllers/duocms/Cars.php` (akcja uploadu zdjęcia szkicu → zapis do `duo_sketch_photos`)
- Modify: `application/views/duocms/Cars/edit.php` (plupload queue zamiast pojedynczego inputa — wzór: `Products/form` plupload, linie z `jquery.plupload.queue`)

**Interfaces:**
- Consumes: `CarModel::add_sketch_photo()`.
- Produces: operator wgrywa 2/4/więcej zdjęć na pozycję szkicu; każde przeskalowane (reuse logiki `ProductPhotoModel::saveImage` — cap 1600px + mini 400px).

- [ ] **Step 1:** Backup. Podłącz plupload (wzór z `Products/form.php`) do sekcji szkicu w `edit.php`.
- [ ] **Step 2:** Akcja uploadu: zapis pliku do `uploads/sketch/{sketch_id}/`, resize jak w `saveImage` (cap 1600 + mini), wpis do `duo_sketch_photos`.
- [ ] **Step 3:** Weryfikacja w panelu — wgraj 3 zdjęcia na szkic, potwierdź 3 wpisy: `SELECT COUNT(*) FROM duo_sketch_photos WHERE sketch_id={id}`.
- [ ] **Step 4:** Commit `[feat+otomoto] kreator: multi-upload zdjęć szkicu z resize`.

### Task B3: product_from_sketch kopiuje wszystkie zdjęcia

**Files:**
- Modify: `application/models/CarModel.php::product_from_sketch` (~424-447 — sekcja kopiowania pojedynczego `$photo`)

**Interfaces:**
- Consumes: `CarModel::get_sketch_photos($sketch->id)`, `ProductPhotoModel`.
- Produces: część dostaje N wpisów `duo_product_photos` = N zdjęć szkicu (fallback do dotychczasowego pojedynczego `$sketch->image` gdy brak wpisów w `duo_sketch_photos`).

- [ ] **Step 1: Backup.** Zastąp blok pojedynczego kopiowania pętlą:

```php
        $sketch_photos = $this->get_sketch_photos($sketch->id);
        if (empty($sketch_photos)) {
            // fallback: stara ścieżka (pojedyncze zdjęcie szkicu lub auta)
            $sketch_photos = [];
            if (!empty($sketch->image)) {
                $sketch_photos[] = (object)['name' => $sketch->image, 'source' => FCPATH.'uploads/sketch/'.$sketch->id.'/'];
            } elseif (!empty($car->image)) {
                $sketch_photos[] = (object)['name' => $car->image, 'source' => FCPATH.'uploads/cars/'.$car->id.'/'];
            }
        } else {
            foreach ($sketch_photos as $sp) { $sp->source = FCPATH.'uploads/sketch/'.$sketch->id.'/'; }
        }
        foreach ($sketch_photos as $sp) {
            $photo = new ProductPhotoModel();
            $photo->product_id = $product->id;
            $photo->insert();
            $dest = FCPATH.'uploads/products/'.$photo->product_id.'/'.$photo->id.'/';
            if (!is_dir($dest)) { mkdir($dest, 0777, true); mkdir($dest.'mini/', 0777, true); }
            @copy($sp->source.$sp->name, $dest.$sp->name);
            @copy($sp->source.'mini/'.$sp->name, $dest.'mini/'.$sp->name);
            $photo->name = $sp->name;
            $photo->update();
        }
```

(usuwając poprzedni pojedynczy blok `$photo = new ProductPhotoModel(); … $photo->update();`). Deploy.

- [ ] **Step 2: Weryfikacja** — utwórz część ze szkicu z 3 zdjęciami (`product_from_sketch`), potwierdź `SELECT COUNT(*) FROM duo_product_photos WHERE product_id={id}` = 3.

- [ ] **Step 3:** Commit `[feat+otomoto] product_from_sketch kopiuje wszystkie zdjęcia szkicu`.

### Task B4: Test live fazy B — galeria w ogłoszeniu

- [ ] **Step 1:** Wystaw część z 3 zdjęciami: `Cron/createotomotoad/{productId}` (`-L`).
- [ ] **Step 2:** `GET /account/adverts/{otomoto_id}` (MCP `curl_proxy`, świeży token) — potwierdź, że `photos` zawiera 3 pozycje (a nie 1).
- [ ] **Step 3:** Sprawdź wizualnie ogłoszenie pod `url` z odpowiedzi — galeria 3 zdjęć, rozdzielczości OK.
- [ ] **Step 4:** Dezaktywuj testowe ogłoszenie (za zgodą Janka) lub zostaw jeśli realne.

---

## FAZA C — Odbiór i domknięcie

- [ ] **C1:** Pełny przebieg na 2-3 realnych częściach (różne kategorie, 2 i 4 zdjęcia), weryfikacja w Otomoto.
- [ ] **C2:** ADR `docs/decyzje/2026-XX-XX-otomoto-pakiet-2000-wdrozenie.md` — co zrobione, ścieżki, ustalenia ze spike'a, ewentualne odstępstwa.
- [ ] **C3:** Aktualizacja `docs/INTEGRATIONS.md` (kategorie z cache + multi-foto).
- [ ] **C4:** Raport do Janka (mail przez `send-to-jan`, inline do review) — gotowe do przekazania klientowi po 27.06 (urlop zakończony). Faktura osobno (poza tym planem).

---

## Self-review (spec coverage)

- Oferta „samodzielna edycja kategorii z listą z API" → Task A2 (cache+refresh) + A3 (dropdowny self-service). ✓
- „Kategorie faktycznie używane w ogłoszeniu" (luka hardcode 163) → Task A1. ✓
- „Kilka zdjęć 2/4/więcej, bez sztywnego limitu" → Task B1-B3 (szkic multi → produkt → push iteruje). ✓
- „Auto-zmniejszanie rozdzielczości do wymogów Otomoto" → już w `saveImage` (cap 1600px); reuse w uploadzie szkicu (B2). ✓ (jeśli test B4 wykaże odrzucenia Otomoto przy 1600px — dostosować cap; do potwierdzenia w B4.)
- Ryzyko otwarte: params per kategoria (Otomoto wymaga atrybutów zależnych od kategorii) — pokryte przez istniejący UI parametrów (`Otomoto_parameters` + `get_category_data->parameters`) i weryfikowane w A4. Jeśli A4 pokaże braki params → dodatkowy task (wycena pakietowa, decyzja proaktywna).
