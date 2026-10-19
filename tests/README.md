# Test

Dalla cartella `pacchetti` usare l'ambiente condiviso, come indicato in AGENTS.md:

```sh
python3 .ibtest/bin/ibtest.py ActivityLoggerUI
```

Il runner genera il proprio bootstrap in `.ibtest`; `TestCase` carica anche il
bootstrap locale per confinare cache e viste compilate in `tests/.runtime`.
Non passare un secondo `--bootstrap`. Non viene usato `RefreshDatabase`.

In alternativa, con PHP e le dipendenze già disponibili:

```sh
php ../.ibtest/vendor/phpunit/phpunit/phpunit -c phpunit.xml
```

Il bootstrap preferisce `vendor/autoload.php` locale, poi il vendor condiviso;
`ACTIVITYLOGGERUI_TEST_VENDOR` può indicare un altro vendor. Per la suite di
integrazione servono anche i checkout fratelli Crud e FormField, oppure questi
package già presenti nel vendor. Non vengono utilizzati stub dei loro cast.

Copertura: routing, autenticazione e autorizzazione, escaping, paginazione stabile,
filtri esatti anche su valori nulli, eventi generati da Spatie, extraFields standard,
JSON/date e colonne rinominate, deduplica di fonti, assenza di scritture durante la
lettura, relazioni hasMany e belongsToMany, autori su una connessione separata,
fonti personalizzate, vista parziale e traduzioni.

Validato con Laravel 11 e PHPUnit 11 nel runtime condiviso. PHP 8.5 emette due
avvisi di deprecazione nella configurazione database di Testbench per
`PDO::MYSQL_ATTR_SSL_CA`; non sono errori della suite.
