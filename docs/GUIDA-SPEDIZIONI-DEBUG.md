# Guida rapida `spedizionis_debug`

Questa guida serve per consultare velocemente le spedizioni in locale con i campi gia' raggruppati per blocchi logici.

## Prerequisito

La view `spedizionis_debug` viene creata dalla migration:

- `2026_04_22_190000_group_and_reorder_spedizionis_for_debug`

Se non esiste ancora:

```bash
php artisan migrate
```

## Query base

Ultime spedizioni:

```sql
SELECT *
FROM spedizionis_debug
ORDER BY id DESC
LIMIT 50;
```

Solo spedizioni reso:

```sql
SELECT id, codice_interno, spedizione_padre, codice_reso, created_at
FROM spedizionis_debug
WHERE reso = 1
ORDER BY id DESC;
```

Spedizioni standard (non reso):

```sql
SELECT id, codice_interno, user_id, ordine_id, created_at
FROM spedizionis_debug
WHERE reso = 0
ORDER BY id DESC;
```

## Legame tra spedizione originale e reso

Resi con riferimenti principali:

```sql
SELECT
    s.id AS id_reso,
    s.codice_interno AS codice_reso_spedizione,
    s.spedizione_padre AS id_spedizione_originale,
    p.codice_interno AS codice_spedizione_originale,
    s.codice_reso
FROM spedizionis_debug s
LEFT JOIN spedizionis p ON p.id = s.spedizione_padre
WHERE s.reso = 1
ORDER BY s.id DESC;
```

Tutte le spedizioni figlie di una spedizione padre:

```sql
SELECT id, codice_interno, reso, spedizione_padre, created_at
FROM spedizionis_debug
WHERE spedizione_padre = 123
ORDER BY id DESC;
```

## Filtri pratici

Per utente:

```sql
SELECT id, codice_interno, user_id, reso, spedizione_padre, importo_netto_iva_esc
FROM spedizionis_debug
WHERE user_id = 10
ORDER BY id DESC;
```

Per intervallo data:

```sql
SELECT id, codice_interno, created_at, reso
FROM spedizionis_debug
WHERE created_at >= '2026-04-01'
  AND created_at <  '2026-05-01'
ORDER BY id DESC;
```

Per tracking o codice interno:

```sql
SELECT id, codice_interno, tracking, reso, spedizione_padre
FROM spedizionis_debug
WHERE codice_interno = 'COD-123'
   OR tracking = 'TRACK-EXAMPLE'
LIMIT 20;
```

## Note utili

- La view e' pensata per debug/consultazione, non per update.
- `spedizione_padre` e' valorizzato solo sulle spedizioni nate come reso.
- `codice_reso` e' disponibile ma puo' rimanere `NULL` finche' non viene valorizzato dal flusso esterno.

