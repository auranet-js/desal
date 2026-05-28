# Quick winy — drobne fixy do dorzucenia przy okazji zaakceptowanych prac

Lista mniejszych usprawnień wykrytych podczas sesji rozpoznawczej 2026-05-28. Uzgodnione z Jankiem jako bufor do 14h zaakceptowanych prac (`docs/DIAGNOSIS.md` aktualizacja 2026-05-28) — **nie zwiększamy estymaty bez uzgodnienia**, mieszczą się w naturalnych oknach między głównymi blokami.

## Lista

| # | Problem | Lokalizacja | Estymata | Status |
|---|---|---|---|---|
| 1 | `/duocms/cars` — brak paginacji, sort ASC (najnowsze na dole, strona ładuje wieki, trzeba scrollować) | `application/controllers/duocms/Cars.php::index()` linie 21-37 | ~30 min | pending |
| 2 | Form opcji w `Configuration` — wszystkie `<input type="text">`, hasła plaintext | `application/views/duocms/Configuration/index.php` linia 13 | ~10 min | pending |
| 3 | Brak indeksu `duo_products(status2, otomoto_id)` — cron robi full scan po 27k wierszy | DB | ~15 min | pending |
| 4 | Brak unique index na `duo_options.key` — każdy `get_option()` to scan | DB | ~5 min | pending |
| 5 | Brak indeksu `duo_allegro_logs(type, created_at)` — przy 44k wierszy ad-hoc filtry wolne | DB | ~5 min | pending |

## Detale wdrożeniowe

### #1 Paginacja /duocms/cars + sort DESC

Aktualnie `Cars::index()` ładuje WSZYSTKIE auta (`get_car_list_with_string` bez `LIMIT`) i sortuje rosnąco po id — najnowsze auta na samym dole, strona ładuje wieki. Wzorzec paginacji już istnieje w **tym samym kontrolerze** w `Cars::edit()` linia 121:
```php
$page = empty($this->input->get('page')) ? 1 : $this->input->get('page');
$limit = 20;
$parts = $this->CarModel->get_sketches_by_car_id($car->id, $page);
$countParts = $this->CarModel->count_sketches_by_car_id($car->id);
```
Kopiujemy ten wzorzec do `Cars::index()`, dorabiamy `CarModel::get_car_list_with_string($string, $sort, $page, $limit)` z `LIMIT/OFFSET` i `count_car_list_with_string()`. Sortowanie domyślne zmienić z `'default'` na `'newest'` (ORDER BY `id` DESC). Widok `duocms/Cars/index` dorzucić linki paginatora.

### #2 Hasła plaintext w Configuration

`Configuration/index.php` renderuje każdą opcję jako:
```php
<input type="text" name="<?= $option->key;?>" value="<?= $option->value;?>" class="form-control"/>
```
Bez maskowania nawet dla `admin_modules_*_password`/`*_secret`/`*_token`. Fix: regex po `$option->key` na słowa kluczowe `password|secret|token|api_key`, render `type="password"` z toggle „pokaż" (JS one-liner z `input[type=password]` ↔ `input[type=text]`). Nie psuje istniejących zapisów, tylko zmienia render.

**Uwaga:** ekran i tak dostępny tylko w `ENVIRONMENT='development'` (patrz `docs/DIAGNOSIS.md` aktualizacja 28.05), ale nadal warto — gdy my edytujemy dev-side, hasła są widoczne za naszymi plecami.

### #3, #4, #5 Indeksy DB

Wszystkie idą jako jeden `ALTER TABLE` blok przy okazji backup'u przed PHP bump (zadanie #2 z akceptu). Backup `mysqldump` przed, sprawdzenie wpływu na rozmiar tabeli po. Indeksy bezpieczne, nie wpływają na semantykę, tylko przyspieszają.

```sql
-- #3
ALTER TABLE duo_products ADD INDEX idx_status2_otomoto (status2, otomoto_id);
-- #4
ALTER TABLE duo_options ADD UNIQUE INDEX idx_key (`key`);
-- #5
ALTER TABLE duo_allegro_logs ADD INDEX idx_type_created (`type`, created_at);
```

`#4` może wymagać sprawdzenia duplikatów `key` przed (jeśli są — czyścimy najstarszy lub łączymy). `#3` i `#5` bezpieczne.

## Zasada dorzutków

Quick winy które się pojawią dodatkowo w trakcie wykonywania głównych prac — dopisać tu w `docs/QUICK_WINS.md` (z datą wykrycia + lokalizacją), **nie wykonywać bez pinga do Janka**. Jeśli mieści się w buforze („naturalne 5-15 min przy okazji edycji tego samego pliku") — zgoda dorozumiana. Jeśli wymaga osobnej sesji lub dodatkowej godziny — explicit ack przed dotknięciem.
