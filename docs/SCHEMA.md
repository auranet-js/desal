# Schemat tabel — integracje + powiązane

Źródło: `SHOW CREATE TABLE` przez MCP `query_db`, fetched 2026-05-27.
Baza: `desal_duonet` na MariaDB 10.11.16, prefiks `duo_`.

## Konwencje

- **CHARSET niespójny:** część tabel `utf8mb3` (nowsze), część `latin2` (legacy 2017-2019). Po pierwsze `duo_shop_allegro`, `duo_shop_allegro_photos`, `duo_category_otomoto`, `duo_otomoto_parameter_bind` siedzą na `latin2`. To może powodować problemy z polskimi znakami w łańcuchu tabel.
- Brak Foreign Keys poza jednym (`duo_products → duo_offer_categories`).
- `update_time` w `information_schema.tables` puste dla większości — InnoDB nie raportuje per-row.

## Allegro

### duo_shop_allegro (2 065 wierszy, MARTWY OD 2021-04-16)

```sql
CREATE TABLE `duo_shop_allegro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `allegro_id` varchar(255) NOT NULL,
  `allegro_status` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2066 DEFAULT CHARSET=latin2 COLLATE=latin2_general_ci
```

Mapa: `duo_products.id ↔ allegro.offer_id`. `allegro_status` ∈ {ACTIVE, INACTIVE, ENDED}.

### duo_allegro_logs (44 083 wierszy, ŻYWA)

```sql
CREATE TABLE `duo_allegro_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `item_id` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44084 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

Histogram `type` (z odczytu 2026-05-27):

| type | znaczenie (z kodu) | count | najnowszy |
|---|---|---|---|
| 1  | info: "część X została dodana jako produkt" | 26 429 | **2026-05-26** |
| -1 | info: "część X już jest w bazie" | 1 414  | **2026-05-27** |
| 3  | (nieznane) | 13 396 | 2026-04-24 |
| 2  | **aukcja Allegro dodana** | 2 146  | **2021-04-16** |
| -3 | info Otomoto: "produkt ma już ogłoszenie" | 625 | 2026-05-12 |
| -2 | warn Allegro: "produkt ma już aukcję" | 73 | 2026-03-27 |

Brak indeksu na `(type, created_at)` — przy 44k wierszy ad-hoc filtry działają OK ale skala będzie się pogarszać.

### duo_allegro_timetable (0 wierszy, AUTO_INCREMENT=2867)

```sql
CREATE TABLE `duo_allegro_timetable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2867 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

Pusta — kolejka jednokrotnego użytku, czyszczona przez `Cron::car_timetable()` po przetworzeniu. AUTO_INCREMENT=2867 → historycznie przeszło 2866 produktów. `product_id` to faktycznie **sketch_id** (mylne nazewnictwo, patrz `Cron.php`).

### duo_shop_allegro_deliveries (22 wiersze)

```sql
CREATE TABLE `duo_shop_allegro_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allegro_id` varchar(255) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

Mapa: `allegro_id` (UUID Allegro shipping rate) ↔ `duo_shop_delivery.id`.

### duo_shop_allegro_photos (1 wiersz)

```sql
CREATE TABLE `duo_shop_allegro_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `expiresat` datetime NOT NULL,
  `location` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `photo_id` (`photo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2119 DEFAULT CHARSET=latin2 COLLATE=latin2_general_ci
```

Cache uploadowanych zdjęć Allegro (`location` to URL z `a.allegroimg.com`, TTL ~4h).

## Otomoto

### duo_category_otomoto (0 wierszy, PUSTY)

```sql
CREATE TABLE `duo_category_otomoto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `otomoto_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin2 COLLATE=latin2_general_ci
```

Cache kategorii Otomoto — nigdy nie wypełniony. `CategoryOtomotoModel::integrate()` próbuje to robić.

### duo_otomoto_parameter_bind (17 wierszy, ostatni 2021-07-07)

```sql
CREATE TABLE `duo_otomoto_parameter_bind` (
  `id` int(11) NOT NULL,
  `category` varchar(200) NOT NULL,
  `part_type` varchar(200) NOT NULL,
  `parameter` varchar(200) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin2 COLLATE=latin2_general_ci
```

Mapowanie: `(otomoto_category, part_type) → parameter`. Konfiguracja jakie parametry pokazywać dla danej kategorii w UI panelu admin.

### BRAKUJĄCA `duo_shop_otomoto`

Analogicznie do `duo_shop_allegro` powinna istnieć tabela mapująca `product_id ↔ otomoto_id`. **Nie istnieje** — zamiast tego `otomoto_id` siedzi jako kolumna w `duo_products`. Pipeline Otomoto nigdy nie miał porządnego audit-trailu.

## Produkty + auta (kontekst)

### duo_products (27 190 wierszy, update_time DZIŚ)

```sql
CREATE TABLE `duo_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(11) NOT NULL,
  `price` double DEFAULT NULL,
  `offer_category_id` int(11) DEFAULT NULL,
  `car_id` int(11) NOT NULL,
  `sketch_item_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT 0,           -- 1 = wystaw na Allegro
  `status2` tinyint(4) DEFAULT 0,          -- 1 = wystaw na Otomoto
  `allegro_category_id` text NOT NULL,
  `delivery_id` text NOT NULL,
  `type` text DEFAULT NULL,                -- JSON atrybutów Allegro
  `type2` text DEFAULT NULL,               -- JSON atrybutów Otomoto
  `options` tinyint(4) NOT NULL DEFAULT 0,
  `quantity` int(11) DEFAULT -1,
  `weight` double NOT NULL DEFAULT 0,
  `new` tinyint(4) NOT NULL DEFAULT 0,
  `promo` tinyint(4) DEFAULT 0,
  `bestseller` tinyint(4) DEFAULT 0,
  `sold` int(11) NOT NULL DEFAULT 0,
  `code` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 0,
  `amount_updated_at` datetime DEFAULT NULL,
  `otomoto_id` bigint(20) DEFAULT NULL,    -- ID ogłoszenia Otomoto
  `otomoto_url` text DEFAULT NULL,
  `otomoto_category_id` int(7) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offer_category_id` (`offer_category_id`),
  CONSTRAINT `duo_products_ibfk_1` FOREIGN KEY (`offer_category_id`)
    REFERENCES `duo_offer_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30464 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

Brakuje indeksów na `car_id`, `sketch_item_id`, `otomoto_id`, `status`, `status2`. Crony robią pełen scan przy szukaniu produktów do wystawienia.

### duo_cars (287 wierszy, AUTO_INCREMENT=457)

```sql
CREATE TABLE `duo_cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `production_date` varchar(30) NOT NULL,
  `brand` text NOT NULL,
  `buy_price` int(11) NOT NULL,
  `image` text NOT NULL,
  `version` text NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `brand` (`brand`),
  FULLTEXT KEY `brand_2` (`brand`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=457 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

Auta źródłowe do rozbiórki.

### duo_car_sketches (28 428 wierszy)

```sql
CREATE TABLE `duo_car_sketches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `template_item_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `price` float NOT NULL,
  `image` text NOT NULL,
  `shop` int(11) NOT NULL,                  -- 1 = wystaw w sklepie WWW
  `allegro` int(11) NOT NULL,               -- 1 = wystaw na Allegro
  `otomoto` tinyint(4) DEFAULT 0,           -- 1 = wystaw na Otomoto
  `otomoto_category_id` int(7) DEFAULT NULL,
  `attributes_json` text NOT NULL,           -- atrybuty Allegro
  `attributes_json_otomoto` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34694 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

Szkice części — z każdego auta klient generuje listę części-szkiców, każda staje się docelowo produktem.

### duo_options (144 wiersze)

```sql
CREATE TABLE `duo_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,        -- etykieta human-readable
  `key` varchar(255) NOT NULL,         -- klucz programistyczny (np. admin_modules_allegro_client_id)
  `value` text NOT NULL,               -- wartość (token, ID, hasło, flaga)
  `category` varchar(255) NOT NULL,    -- grupa (admin_module_allegro, admin_module_otomoto, payu, ...)
  `order` int(11) NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
```

To **tu** siedzą wszystkie klucze API (Allegro client_id/secret/tokens, Otomoto credentials, PayU, P24, reCaptcha, GoogleMaps, Facebook, InPost, Sendit, SMTP). Brak indeksu na `key` — `get_option()` robi pełen scan.

**Snapshot wartości:** `~/secrets/desal/api-options-snapshot-2026-05-27.env` (poza repo).
