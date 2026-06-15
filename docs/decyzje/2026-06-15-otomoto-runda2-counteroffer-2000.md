# 2026-06-15 — Rozbudowa Otomoto runda 2: kontroferta 2000 zł netto + samodzielna edycja kategorii

**Status:** wysłane do klienta, czeka na odpowiedź (T-135).
**Kontekst poprzedni:** [[2026-06-02-fix-otomoto-php8-in-array-null-i-tuning-listy-aut]], wycena pierwotna 12.06 (8h/1600 netto).

## Sytuacja

Po wysłaniu wyceny rozbudowy Otomoto (12.06: #1 multi-foto 3h, #2 mapowanie `parts-category` 5h = 8h/1600 netto) Pan Darek odpowiedział 15.06 (9:56). Chce wejść w rozbudowę, ale kwoty „dość znaczące" — pyta o szybciej/taniej w połączeniu z 1. częścią (zamknięty pakiet 14h, FV FS-4-06-2026). Plus 3 uwagi:

1. **Kategorie** — Otomoto co jakiś czas zmienia/uzupełnia kategorie; czy będą mogli sami robić drobne korekty, czy za każdym razem płacą za modyfikację programu?
2. **Zdjęcia** — wystarczą 2, czasem ~4; chce wbudowany mechanizm zmniejszający rozdzielczość do wymogów Otomoto.
3. **Sprzedaż w obu ośrodkach** — jednoczesna sprzedaż/integracja części w naszej bazie i w bazie Otomoto.

## Decyzja

**Nie obniżamy ceny przez rabat — odpowiadamy wartością.** Kontroferta wysłana 15.06 (15:24, mail [65] do `desaltarnow@gmail.com`):

- **Funkcja 2 rozszerzona o samodzielną edycję kategorii.** Zamiast jednorazowego mapowania tylko po naszej stronie (które przy każdej zmianie Otomoto = powrót do nas, czyli dokładnie lęk klienta z uwagi #1) — dokładamy w panelu wybór kategorii Otomoto przy pozycji w szablonie, z **listą pobieraną na żywo z Otomoto przez API**. Nowe/zmienione kategorie pojawiają się same, klient koryguje sam. Dodatkowy nakład **~4–6h**.
- **Cena:** pełen zakres rośnie do **2400+ zł netto**, ale za całość (łącznie z samodzielną edycją kategorii) oferujemy **2000 zł netto**. Anchor 2400+, lądowanie 2000 — klient dostaje *więcej za to samo* zamiast rabatu. Stawka i tak na dolnej półce (poprzedni pakiet 14h przyjęty po dolnych widełkach odbił się na marży — nie schodzimy niżej).
- **Uwaga #2 (zdjęcia):** obsługujemy 2/4/więcej zdjęć + automatyczny downscale do wymogów Otomoto, w cenie funkcji zdjęć (bez osobnej pozycji).
- **Uwaga #3 (sprzedaż w obu):** **nie wyceniamy ani nie sugerujemy zakresu** — pytamy klienta wprost, co ma na myśli. (Publikacja równoległa już dziś działa: kreator ma zaznaczanie celów, domyślnie desal.pl + Otomoto. Prawdopodobnie chodzi o sync „sprzedane → zdjąć z oferty", ale nie zakładamy — klient ma sam powiedzieć.)
- **Tempo:** start w tym tygodniu po akcepcie, ale realny odbiór **po 27.06** — Janek na urlopie 20–27.06, a każdą zmianę weryfikuje na żywym wystawieniu. Bez obietnicy domknięcia przed urlopem (nie wypuszczamy nieprzetestowanego). *(Janek usunął z draftu fragment o „szansie do czwartku" — mało realne.)*

## Konsekwencje

- Marża na funkcji 2 świadomie ścięta (~4–6h pracy przy +400 zł), ale unikamy zapachu negocjacji w dół i rozwiązujemy realny problem klienta (uwaga #1) → mocniejsza pozycja na kolejne zlecenia.
- Po akcepcie: realizacja wg diagnozy gotowej z 12.06 (Otomoto API `parts-category` 850 liści, szablon „Samochód_pełny" 279 poz., dry-run ~60% auto + ~84 ręcznie) + nowy moduł picker kategorii z API w kreatorze/szablonie.
- Uwaga #3 → osobny wątek/wycena po doprecyzowaniu przez klienta.

## Ton maila (utrwalone)

Miks „ja"/„my": **„ja"** przy relacji, decyzji, cenie („proponuję", „trzymam stawkę", „nie obiecuję", „weryfikuję"); **„my/zrobimy"** przy samej pracy („obsłużymy zdjęcia", „dołożymy edycję", „ruszamy"). Sygnalizuje zespół przy uzasadnianiu ceny/tempa, utrzymując osobistą odpowiedzialność Janka. Patrz memory `feedback_personal_tone_in_offer_sections`.
