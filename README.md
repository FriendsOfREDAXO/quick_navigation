# Quick Navigation für REDAXO 6

Eine Befehlspalette für das Backend: **⌘K** (Mac) bzw. **Strg+K** oder der Suchknopf in der Kopfleiste öffnet ein
Suchfeld, das live über Backend-Seiten, Struktur, Medienpool und Benutzer sucht – immer nur über das, was der
angemeldete Benutzer auch öffnen darf.

> Branch `6.x` ist eine Neuentwicklung für REDAXO 6 und nicht kompatibel zu Quick Navigation 9.x für REDAXO 5.

## Strukturbaum

Der Knopf neben der Suche öffnet die Struktur als Baum – aufgeklappt bis zum Artikel bzw. zur Kategorie, auf der man
gerade ist. Unterebenen werden erst beim Aufklappen geladen. Klick auf eine Kategorie öffnet sie in der Struktur, auf
einen Artikel dessen Inhalt; „Inhalt bearbeiten“ und „Ansehen“ erscheinen beim Überfahren. Bei mehreren Sprachen wird
oben umgeschaltet. Wer nur Teile der Struktur darf, sieht seine Mountpoints.

Tastatur: ↑ ↓, → aufklappen bzw. hinein, ← zuklappen bzw. hinauf, Pos1/Ende, ↵ öffnen (⌘↵ neuer Tab), Buchstabe springt
zum nächsten Eintrag mit diesem Anfang, esc schließt.

## Medienpool

Der Medienpool läuft in REDAXO 6 als eigenes Fenster bzw. als Overlay beim Auswählen eines Mediums. Dort erweitert
Quick Navigation das vorhandene Suchfeld:

- **Live-Suche:** Treffer erscheinen beim Tippen unter dem Feld (Vorschaubild, Dateiname, Kategorie, Größe), ↑ ↓ wählen,
  ↵ öffnet das Medium.
- **Zuletzt bearbeitet:** ins leere Suchfeld klicken zeigt die zuletzt geänderten Medien.
- **Auswählen:** im Auswahl-Modus eines Medienfelds hat jeder Treffer „übernehmen“, ↵ übernimmt direkt; die erlaubten
  Dateitypen des Felds gelten auch hier.
- **Sortierung:** der Knopf neben der Suche schaltet die Liste zwischen „neueste zuerst“, Dateiname und Titel um
  (bleibt gespeichert).

Es erscheinen nur Medien aus Kategorien, für die der Benutzer Rechte hat.

## Bedienung

| Taste | Wirkung |
| --- | --- |
| ⌘K / Strg+K | Palette öffnen bzw. schließen |
| ↑ ↓ | Treffer wählen |
| Bild ↑ / Bild ↓ | zur vorigen / nächsten Gruppe |
| ↵ | öffnen |
| ⌘↵ / Strg+↵ / ⇧↵ | in neuem Tab öffnen |
| → / ← | Zusatzaktionen des Treffers (z. B. „In der Struktur zeigen“, „Ansehen“) |
| esc | Eingabe leeren, dann schließen |

Ohne Eingabe zeigt die Palette die **zuletzt geöffneten** Treffer (im Browser gespeichert) und die **zuletzt
bearbeiteten** Artikel. Mit einem Kürzel sucht sie nur in einer Quelle:

| Kürzel | Quelle |
| --- | --- |
| `p:` | Backend-Seiten (auch Unterseiten von AddOns) |
| `a:` | Artikel und Kategorien – nach Name oder ID |
| `m:` | Medienpool – nach Titel und Dateiname, mit Vorschaubild |
| `u:` | Benutzer (nur mit Recht auf die Benutzerverwaltung) |

Eine Zahl findet den Artikel mit dieser ID.

## Rechte

Die Suche läuft über eine API-Funktion (nur lesend, mit CSRF-Token). Jede Quelle prüft selbst:

- **Seiten**: `Page::checkPermission()` – wie die Navigation; versteckte Seiten und Popups bleiben draußen
- **Struktur**: Kategorie-Rechte (`StructurePermission`) und Sprach-Rechte
- **Medien**: Medienpool-Rechte je Kategorie (`MediaPoolPermission`)
- **Benutzer**: Recht `users[]`; Administratoren sehen nur Administratoren

## Eigene Quellen

Ein AddOn liefert Treffer mit einer Klasse, die `Provider` implementiert und das Attribut `AsProvider` trägt – mehr
braucht es nicht, die Klasse wird automatisch gefunden:

```php
use FriendsOfRedaxo\QuickNavigation\Action;
use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Redaxo\Core\Filesystem\Url;

#[AsProvider('news', priority: 40, prefix: 'n')]
final class NewsProvider implements Provider
{
    public function label(?Query $query = null): string
    {
        return 'News';
    }

    public function search(Query $query): iterable
    {
        // Rechte prüfen – nur liefern, was der Benutzer öffnen darf
        if ($query->isEmpty() || !$query->user->hasPerm('news[]')) {
            return;
        }

        foreach (News::query()->where('title', $query->like(), 'LIKE')->find() as $news) {
            $score = $query->score($news->title, $news->teaser);
            if (0 === $score) {
                continue;
            }

            yield new Item(
                title: $news->title,
                url: Url::backendPage('news', ['func' => 'edit', 'id' => $news->id]),
                meta: $news->date->format('d.m.Y'),
                icon: 'rex-icon fa-newspaper',
                badge: $news->online ? null : 'offline',
                actions: [new Action('Ansehen', $news->getUrl(), 'rex-icon rex-icon-external-link', external: true)],
                score: $score,
            );
        }
    }
}
```

- `AsProvider(key, priority, prefix)` – `priority` ordnet die Gruppen (Seiten 100, Struktur 90, Medien 80, Benutzer 50),
  ein gleicher `key` mit höherer Priorität ersetzt eine vorhandene Quelle, `prefix` ist das Kürzel für `x:`.
- `Query` – `term`, `words`, `user`, `limit`, `scoped` (per Kürzel gewählt), `isEmpty()`, `isId()`, `like()` (fertiges
  Muster für SQL `LIKE`) und `score()` für die Reihenfolge (gleich > beginnt mit > Wortanfang > enthalten, 0 = passt nicht).
- `Item` – Titel, Adresse, zweite Zeile, Icon oder Vorschaubild, Badge, Zusatzaktionen; `score` sortiert innerhalb der Gruppe.
- Bei leerer Eingabe darf eine Quelle Vorschläge liefern (z. B. „zuletzt bearbeitet“) oder nichts.

Ohne Attribut, etwa abhängig von der Konfiguration, im `boot()` des eigenen AddOns:

```php
QuickNavigation::register('news', new NewsProvider(), priority: 40, prefix: 'n');
QuickNavigation::unregister('users'); // eingebaute Quelle abschalten
```

Wirft eine Quelle eine Exception, fehlt nur ihre Gruppe (im Entwicklungsmodus wird der Fehler angezeigt).

## Technik

- Suchknopf in der Kopfleiste des Backends, auf schmalen Bildschirmen als Lupe
- `<dialog>` mit ARIA-Combobox und Listbox, Ansagen für Screenreader, `prefers-reduced-motion`
- Farben aus den Tokens des Cores – hell und dunkel ohne eigene Regeln
- keine Inline-Styles und -Handler (Content-Security-Policy des Backends)

## Lizenz

MIT, siehe [LICENSE](LICENSE).
