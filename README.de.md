# Quick Navigation 8 für Redaxo

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/quick_navigation/assets/quickbar.png)

Komplette Überarbeitung der klassischen QuickNavigation 

Funktionen: 
- Struktur-Navigation mit Live-Suche
- Medienverlauf
- Artikelverlauf
- Minibar-Artikelverlauf
- Linkmap-Artikelverlauf
- Linkmap-Struktur-Navigation
- Button-Registry zur einfachen Registrierung von Schaltflächen, sortiert nach Priorität in der Strukturansicht
- Erweiterungspunkte zum Hinzufügen klassischer Schaltflächen zu Struktur, Linkmap, Mediapool
- Kategoriefavoriten mit Hinzufügen-Schaltflächen für Kategorien und Artikel
- YForm-Schnellnavigation

## Beschreibung 

Die Quick Navigation bietet eine Kategorie-Schnellauswahl mit einem Filterfeld und Listen der zuletzt bearbeiteten Artikel und Medien (kürzlich geändert).

Im Live-Filter können Kategorien,IDs und yrewrite-Domains gefiltert werden. Wenn man beispielsweise eine Domain eingibt, wird schnell ein Baum der jeweiligen Domain angezeigt.

Favoritenkategorien können von den Redakteuren individuell in den AddOn-Einstellungen gepflegt werden. 

Neue Artikel oder Kategorien können direkt über das (+)-Symbol neben dem Kategorielink erstellt werden.

Quick Navigation integriert auch Funktionen anderer AddOns, sofern diese installiert und aktiviert sind.
Derzeit unterstützte AddOns: YForm, Watson

Admins haben Zugriff auf alle Funktionen.
Für Redakteure kann die Verfügbarkeit der Funktionen durch Rollenerlaubnisse definiert werden. Es kann auch konfiguriert werden, dass ein Redakteur die Änderungen anderer Benutzer nachverfolgen kann.

## Eigene Schaltflächen zur Quick Navigation hinzufügen

Erstelle eine Schaltflächenklasse

```php
class MeinButton implements FriendsOfRedaxo\QuickNavigation\Button\ButtonInterface {
    public function get(): string {
        // Logik für die Schaltfläche
        return '<button class="btn btn-primary"><i class="fa-solid fa-egg"></i> Easter Egg</button>';
    }
}
```

In die boot.php deines AddOns hinzufügen:

```php
use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
ButtonRegistry::registerButton(new MeinButton(), 5);
```
**5** ist die Priorität der Schaltfläche

Um die Standardprioritäten zu sehen, schaue in die boot.php

### Client-seitiges Ereignis für Quick Navigation

```js
$(document).on('quicknavigation:ready', function() { … });
```

## Autor

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

**Projektleitung**

[Thomas Skerbis](https://github.com/skerbis)

**Danksagungen**

Dank an: 

Erste Veröffentlichung: [Thomas Blum](https://github.com/tbaddade)

Leistungsoptimierung: [Markus Staab](https://github.com/staabm)

Styling, Bugfixing, Code-Refactoring und mehr: [Thomas Blum](https://github.com/tbaddade)

Bugfixes / Tests: [Hirbod](https://github.com/hirbod), [Marco Hanke](https://github.com/marcohanke)
