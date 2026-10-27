# ActivityLoggerUI

UI di sola lettura per gli eventi di `spatie/laravel-activitylog`: cronologia,
valori prima/dopo, autore, origine, filtri e paginazione sul database.
La pagina funziona senza JavaScript o una compilazione frontend. Include italiano
e inglese e può usare il layout dell'applicazione.

Richiede PHP 8.2+, Laravel e Spatie Activitylog con le sue migrazioni già
eseguite; le versioni minime sono dichiarate in Composer.

I vincoli Composer delle dipendenze Laravel e delle relative integrazioni indicano soltanto una versione minima, senza un limite sulle major successive. La scelta delle versioni concrete spetta all’applicazione ospite e al suo `composer.lock`; la compatibilità del codice va verificata con i test.

Il package legge il model, la tabella e la connessione configurati in
`activitylog`; non cambia il modo in cui vengono registrati gli eventi.
Il supporto agli extraFields richiede il package CRUD presente nell'applicazione.

## Utilizzo e delega dal CRUD

È sufficiente generare un link passando **model e id** alla route del package:

```php
route('activityloggerui.history', [
    'model' => $model->getMorphClass(), // alias morph oppure classe completa
    'id' => $model->getKey(),
]);

// Funziona anche senza avere già caricato un'istanza:
route('activityloggerui.history', ['model' => App\Models\Client::class, 'id' => 123]);
```

La route `GET /activity-log-management/{model}/{id}` risolve il model, controlla
i permessi, riconosce gli extraFields dai cast CRUD, aggrega le relazioni dichiarate
e rende la pagina completa con filtri e paginazione. Non serve un controller
specifico nell'applicazione né recuperare il record prima di creare il link.
`model` accetta un alias della morph map oppure il nome completo della classe
Eloquent, con risoluzione della classe di progetto tramite `gpc()` quando presente.
Gli ID restano stringhe, per supportare anche chiavi UUID. Model/record inesistenti
restituiscono 404 e accessi non autorizzati 403.

I filtri sono parametri aggiuntivi, ad esempio `field => 'city'`. I link generati
dalla pagina mantengono automaticamente model, id e filtri. La vecchia route
`activities.index`, con `classBasename` e `primary`, resta compatibile.

Se si possiede già l'istanza o si vuole incorporare lo storico:

```php
use IlBronza\ActivityLoggerUI\Facades\ActivityLoggerUI;

// Scorciatoia per la route activityloggerui.history.
$url = ActivityLoggerUI::url($model);
$urlDelCampo = ActivityLoggerUI::url($model, ['field' => 'city']);

// Da un controller: pagina completa oppure frammento per tab/modale.
return ActivityLoggerUI::view($model, request()->query());
return ActivityLoggerUI::view($model, request()->query(), partial: true);
```

Dal CRUD basta quindi puntare il pulsante storico alla nuova route. Solo se si
vogliono conservare i precedenti endpoint/modali CRUD, il corpo dei controller
`HistoryController` e `HistoryByFieldController` può continuare a risolvere il
model come già fa e restituire:

```php
return ActivityLoggerUI::view($model, request()->query(), partial: true);

// Storico del singolo campo; il parametro della route prevale sulla query string.
return ActivityLoggerUI::view($model, ['field' => $field] + request()->query(), partial: true);
```

Questo mantiene gli URL e i pulsanti del CRUD. La modifica ai controller del
package CRUD è un passaggio di integrazione separato: questo repository fornisce
la UI e il servizio, senza sostituire automaticamente route di altri package.
I form e i link di paginazione dei frammenti aprono la pagina del package; il
contenitore può intercettarli se vuole mantenere la navigazione dentro una modale.

`ActivityLoggerUI::data($model, $filters)` restituisce i dati per viste personalizzate.
`entries` contiene la presentazione con esclusioni e mapping già applicati;
`activities` è il paginator di model Activity **grezzi** e non va serializzato
in una risposta pubblica. Ogni accesso tramite `view()` o `data()` controlla i permessi.

Pubblicare la configurazione con `php artisan vendor:publish --tag=activityloggerui.config`.
Per usare il layout IlBronza impostare:

```php
// config/activityloggerui.php
'layout' => 'uikittemplate::app',
```

Il layout deve esporre `@yield('content')`. Sono pubblicabili anche i tag
`activityloggerui.views` e `activityloggerui.lang`.

## ExtraFields descritti dal CRUD

Il resolver usa `getExtraFieldsCasts()` e interpreta la descrizione dei cast,
senza leggere gli attributi virtuali né chiamare provider che creano record:

```php
protected $casts = [
    'note' => ExtraField::class,                         // extraFields.note
    'due_date' => ExtraFieldDate::class,                 // extraFields.due_date
    'settings' => ExtraFieldJson::class,                 // extraFields.settings
    'city' => ExtraField::class . ':address,stored_city', // address.stored_city
];
```

La relazione `extraFields` o quella indicata dal cast deve essere una relazione
Eloquent diretta. Il log viene cercato sul record di appoggio, ma la UI mostra il
nome pubblico (`city`). I filtri sul campo effettuano lo stesso mapping. Sono
visibili solo le colonne descritte dai cast, con i permessi del model principale.
Se lo storage non esiste non viene creato. Se più relazioni puntano allo stesso
record, l'evento compare una sola volta con tutti i mapping pertinenti.

`ExtraFieldParameter`, dossier, prezzi e totali calcolati non sono trattati come
semplici colonne: vengono segnalati nella UI come richiedenti una configurazione
dedicata. Per questi casi usare `getActivityHistorySources()` descritto sotto.

## Relazioni

Dichiarare sul model le relazioni di cui aggregare lo storico:

```php
public function getActivityHistoryRelations(): array
{
    return ['rows', 'customer', 'tags'];
}
```

Sono ammesse relazioni Eloquent dirette singole o multiple, incluse `belongsTo`,
`hasOne`, `hasMany`, `belongsToMany` e le corrispondenti relazioni polimorfiche.
Vengono inclusi solo i record collegati attualmente, visibili all'utente.
Non viene attraversato ricorsivamente l'intero grafo; i relativi extraFields non
sono inclusi automaticamente. Ogni evento conserva la sua origine e il suo ID.
La paginazione limita i log letti; la risoluzione iniziale delle relazioni carica
comunque i record collegati per controllarne i permessi. Per collezioni molto
ampie scegliere un insieme di fonti più ristretto.

Le modifiche a una chiave esterna del model principale compaiono nel suo log
come prima/dopo degli ID. I valori storici non vengono sostituiti con nomi attuali.
La variazione del contenuto di un record collegato è un evento diverso dalla
variazione dell'associazione. Per registrare `attach`, `detach`, `sync` e dati
pivot serve logging esplicito nell'applicazione o un pivot con logging configurato.

Per fonti speciali, pivot, record archiviati o precedentemente collegati:

```php
use IlBronza\ActivityLoggerUI\History\HistorySource;

public function getActivityHistorySources(): iterable
{
    // Metodo dell'applicazione: recupera solo dati esistenti e pertinenti.
    foreach ($this->existingHistoricalRows() as $row) {
        yield new HistorySource(
            model: $row,
            key: 'archived_rows',
            label: 'Righe archiviate',
            fields: ['stored_value' => 'public_field'], // null = tutti i campi ammessi
        );
    }
}
```

Le fonti esplicite devono avere una chiave non riservata (`model`, `extra:*` e
`relation:*` sono usate dal resolver) e passare il controllo di visibilità.
Per record eliminati definitivamente si può fornire un model con la chiave
storica e una policy adatta, ma l'applicazione deve conoscere e verificare
l'appartenenza di quella chiave al model principale.

Per un audit completo nel tempo conviene registrare l'associazione quando cambia,
con subject principale, ID e tipo del correlato, snapshot dei valori/nomi utili,
e un eventuale `batch_uuid` per correlare gli eventi della stessa operazione.
I vecchi log senza questa informazione non permettono di ricostruire associazioni
rimosse. La UI non inventa snapshot o stati completi a partire da log parziali.

## Permessi e dati mostrati

- Le route usano `web` e `auth`; l'accesso al model richiede `userCanShow()` oppure
  l'ability Laravel configurata (`view` per default).
- Le relazioni ordinarie e le fonti esplicite richiedono anche il permesso sul
  record collegato. Gli extraFields descritti dal CRUD ereditano quello del padre,
  limitatamente ai campi mappati.
- Sono esclusi chiavi primarie, `$hidden`, campi tecnici, password, remember token
  e `getHistoryExcludedFields()`. Le esclusioni si applicano sia ai nomi pubblici
  sia alle colonne di storage. Per JSON strutturati escludere l'intero campo se
  contiene valori riservati: non è prevista la redazione di sottochiavi.
- Tutti i valori sono escapati. `null`, stringa vuota, `false`, `0` e valore non
  registrato restano distinti; array e oggetti vengono mostrati come JSON.
- Gli autori vengono caricati in gruppo sulla connessione del proprio model;
  un autore eliminato o non risolvibile viene indicato come non disponibile.
- Il titolo usa gli attributi raw `name`, `title`, `rag_soc`, `email` oppure tipo e
  chiave, per non attivare getter che possono scrivere. Le etichette dei campi
  usano le traduzioni `fields.<nome>` quando disponibili.

Il model principale deve essere ancora recuperabile dalla route standard.
Per soft-delete o archivi usare un controller dell'applicazione che lo recuperi
con gli scope appropriati e poi chiami `view()`. Vengono rispettati gli scope
Eloquent e l'eventuale model Activity personalizzato. Su sistemi multi-tenant
l'applicazione deve isolare i log: `subject_type` e `subject_id` da soli non
distinguono record con la stessa chiave su database tenant diversi.

Filtri disponibili: `source`, `event`, `field` (nome esatto), `from`, `to` e `page`.
Le date usano il giorno registrato nel database, con estremi inclusivi. Le chiavi
JSON vengono cercate in `old` e `attributes`, anche quando il valore è `null`.

## Verifica

Vedere [tests/README.md](tests/README.md). La suite usa SQLite in memoria, due
connessioni separate per dati e log, e le implementazioni reali dei cast del CRUD.
