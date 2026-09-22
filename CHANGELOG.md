# Changelog

## 9.3.1 – 2026-09-22

### Menü wird direkt gerendert statt per XHR nachgeladen

- Das Quick-Navigation-Menü wird jetzt serverseitig im Seitentitel ausgegeben; der bisherige zusätzliche Request `rex-api-call=quicknavigation_api` bei jedem Seitenaufruf entfällt
- Hintergrund: Jeder API-Aufruf bootet das komplette Backend und hält währenddessen die PHP-Session-Sperre, wodurch alle weiteren Requests derselben Session warten mussten (spürbar langsame Backend-Navigation, besonders zusammen mit anderen AddOns, die ebenfalls bei jedem Seitenaufruf API-Requests absetzen)
- Das Ereignis `quick-navigation:ready` wird weiterhin ausgelöst, die API `quicknavigation_api` bleibt aus Kompatibilitätsgründen registriert

## 9.3.0 – 2026-09-22

### Klassisches YForm-Dropdown ist wieder Standard, Live-Suche als Opt-in

- Neue Einstellung „YForm-Modus" je Benutzer: „Klassisches Dropdown" (Standard) oder „Live-Suche"
- Das Tabellen-Dropdown aus 9.1 ist zurück (inkl. „+"-Link bei Bearbeiten-Recht, versteckte Tabellen bleiben wie in YForm selbst ausgeblendet); die Rechteprüfung aus 9.2.2 gilt in beiden Modi
- Die Assets der Live-Suche werden nur noch geladen, wenn der Benutzer den Modus aktiviert hat

## 9.2.2 – 2026-09-21

### Fix: YForm-Tabellenrechte in Button und Live-Suche (#213)

- Tabellen erscheinen nur noch mit explizitem Ansehen- (`yform_manager_table_view`) oder Bearbeiten-Recht (`yform_manager_table_edit`); bisher wurde nur das Bearbeiten-Recht ausgewertet, Nutzer mit reinem Leserecht sahen keine Tabellen
- Der „+“-Link (Datensatz anlegen) wird nur noch bei Bearbeiten-Recht ausgegeben
- Implizite Leserechte über Relationen (YForm `isGranted('VIEW')`) werden bewusst nicht übernommen

## 9.2.1 – 2026-09-17

### Fix: "Class rex_api_quicknavigation_render not found" nach Update (#208)

- Die deprecated Kompatibilitätsklassen `rex_api_quicknavigation_render` und `rex_api_quicknavigation_media_search` wurden im Zuge eines CS-Fixer-/Cleanup-Commits versehentlich entfernt (waren ursprünglich als Fix für #165 eingeführt worden)
- REDAXOs globaler Autoload-Classmap-Cache wird bei einem Addon-Update nicht in jedem Fall zuverlässig für alle betroffenen Klassen neu aufgebaut, insbesondere bei älteren Core-Versionen – dadurch verwies der Cache teils noch auf die alte Klasse, bis ein Safe-Mode-Toggle einen kompletten Cache-Rebuild erzwang
- Beide `@deprecated`-Klassen wieder hinzugefügt, um Installationen mit veraltetem Autoload-Cache robust abzufangen, bis der Cache sich selbst korrigiert

## 9.2.0 – 2026-09-17

### Neu: YForm Live-Suche im YForm-Button

- Der bestehende YForm-Button (Tabellenübersicht) öffnet jetzt zusätzlich ein Spotlight-artiges Overlay mit einer Live-Suche über Datensätze aller berechtigten YForm-Tabellen
- Tabellenauswahl über ein permanent sichtbares Dropdown neben dem Suchfeld (bootstrap-select) – auch während/nach einer Suche jederzeit wechselbar, ohne den Suchbegriff zu verlieren
- Feldtyp-abhängige, aufklappbare Filter je Tabelle (Dropdown für choice/checkbox, Datum für date/datetime, Zahl für number)
- Ergebnis-Vorschau nutzt YForms eigene `getListValue()`-Formatierung: choice-Werte als Label-Badges, checkbox-Felder als farbige Ja/Nein-Badges, `fields_tagging`-Werte als farbige Tag-Badges, mehrsprachige `yform_lang_fields`-Spalten als Sprachcode-Chips (statt rohem JSON)
- Spalten `name`/`title`/`cat`/`category` (plus über die Addon-Einstellungen admin-konfigurierbare weitere Spalten) werden automatisch als Titel hervorgehoben und zuerst angezeigt
- Bearbeiten-Links nutzen `rex_yform_manager::url()` bzw. die aktuell geöffnete Addon-Seite (statt immer auf die YForm-Standardseite zu verweisen), inklusive korrektem CSRF-Handling
- Tastatur-Navigation: Pfeiltasten/Tab zwischen Suchfeld und Ergebnis-/Tabellenliste, Enter öffnet den fokussierten Eintrag
- Tabellenzeilen zeigen das in der Tabellenverwaltung konfigurierte Icon
- Eigenes Such-Icon (Datenbank + Lupe) statt des generischen Datenbank-Symbols
- Alle 8 von REDAXO-Core unterstützten Sprachen vollständig ausgeliefert (`de_de`, `en_gb`, `es_es`, `it_it`, `nl_nl`, `pt_br`, `ru_ru`, `sv_se`)

## 9.1.0 – 2026-03-31

### Neu: Sprachschalter (CLANG) in der Linkmap

- Neuer Button in der Linkmap-Toolbar zum Wechseln der Inhaltssprache
- Der Button zeigt das Kürzel der aktiven Sprache (z. B. `DE`, `EN`)
- Beim Sprachwechsel bleiben alle relevanten Linkmap-Parameter erhalten (`opener_input_field`, `category_id`, `article_id`, `function`)
- Dropdown zeigt alle verfügbaren Sprachen mit Kürzel und Namen
- Wird automatisch ausgeblendet, wenn nur eine Sprache vorhanden ist

> **Hinweis:** Der Sprachschalter ist eine rein visuelle Hilfe für die Navigation. Die tatsächliche Auswahl eines Links im Linkmap-Dialog übergibt weiterhin nur die Artikel-ID – ohne Sprachinformation.

## 9.0.1 – 2025

- Bugfix Release

## 9.0.0 – 2025

- Dark Mode Support
- Visuelle Verbesserungen
- Code-Qualität (PHP-CS-Fixer, Rector)
