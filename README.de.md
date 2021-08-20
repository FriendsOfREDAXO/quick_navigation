
# REDAXO-AddOn: Quick Navigation

Backend- und Frontend-Schnellnavigation für REDAXO CMS

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/quick_navigation/assets/quick_navigation.png)

## Features
- Kategorie-Schnellauswahl übergreifend im Backend inkl. Linkmap
- Filter nach Kategorienamen, Domain und Kategorie-id
- Zuletzt geänderte Artikel(auch in /minibar/linkmap) und Medien, entsprechend Sprache und User 
- Blättern durch Artikel und Medien

### Per User Settings
- Individuelle Kategorie-Favoriten in der Struktur und der Linkmap
- Offline-Kategorien können optional durch User ausgeblendet werden
- "FOR Calendar"-Unterstützung kann abgeschaltet werden
- Artikelblättern kann deaktiviert werden

### AddOn-Integration 
- YForm-Tabellen-Auswahl mit Direktlinks um einen Datensatz in der ausgewählten Tabelle zu erstellen
- "FOR Calendar"-Support: Neuste Termine werden aufgelistet, ein Datensatz kann direkt erstellt werden. 
- Watson-Support: Quick Navigation bindet den Watson-Button ein (sofern in watson aktiviert). 
- Artikel-Historie in der Minibar

## Für Developer
- Für Developer stehen drei Extension Points QUICK_NAVI_CUSTOM, QUICK_LINKMAP_CUSTOM und QUICK_NAVI_CUSTOM_MEDIA zur Einschleusung eigener Buttons oder Dropdowns zur Verfügung 


### Beispiel:

in die boot.php des Project-AddOns:

```php
rex_extension::register('QUICK_NAVI_CUSTOM', ['my_quickbutton','makebutton'], rex_extension::LATE);    
```

my_quickbutton.php in lib Ordner des Projekt-AddOns ablegen

```php   

class my_quickbutton {
    public static function makebutton($ep) {

        $subject = $ep->getSubject();
        $subject .='<div class="btn-group">';
        $subject .='<button>Hallo Welt<button>';
        $subject .='</div>';
        return $subject ;
    }
}
```


## Beschreibung 

Die Quick Navigation stellt eine Kategorie-Schnellauswahl mit Filterfeld und Listen der zuletzt bearbeiteten Artikel und Medien zur Verfügung (last modified).

Im Live-Filter kann nach Kategorien, Autoren oder yrewrite-Domains gefiltert werden. Gibt man z.B. eine Domain ein, erhält man schnell den Baum der jeweiligen Domain. 

Favorisierte Kategorien können individuell durch die Redakteure in den AddOn-Einstellungen gepflegt werden. Die Favoritenliste erscheint erst, wenn Favoriten ausgewählt wurden. Über das (+)-Symbol neben der Kategorie können direkt neue Artikel erstellt werden. 

Quick Navigation integriert Funktionen auch anderer AddOns, sofern diese installiert und aktiviert wurden. 
Aktuell: YForm, FOR Calendar und Watson
Der "FOR Calendar"-Button kann durch die Redakteure ausgeblendet werden. 

Die Quick Navigation ist über den Access-Key **m** erreichbar.

Admins erhalten alle Funktionen. 
Für Redakteure kann die Verfügbarkeit der Funktionen über die Rollen-Rechte definiert werden. Es kann auch eingestellt werden, dass ein Redakteur auch die Änderungen anderer User verfolgen kann.   



## Installation

1. Über Installer laden oder Zip-Datei im AddOn-Ordner entpacken, der Ordner muss „quick_navigation“ heißen.
2. AddOn installieren und aktivieren.
3. Rechte für Rollen anpassen


## Bugtracker

Du hast einen Fehler gefunden oder ein nettes Feature parat? [Lege bitte ein Issue an]

## Changelog

siehe [CHANGELOG.md](https://github.com/FriendsOfREDAXO/quick_navigation/blob/master/CHANGELOG.md)

## Lizenz

siehe [LICENSE.md](https://github.com/FriendsOfREDAXO/quick_navigation/blob/master/LICENSE.md)


## Autor

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

**Projekt-Lead**

[Thomas Skerbis](https://github.com/skerbis)

**Credits**

First Release: [Thomas Blum](https://github.com/tbaddade)

Performance-Optimierung: [Markus Staab](https://github.com/staabm) 

"FOR Calendar"-Integration: [Christian Gehrke](https://github.com/chrison94)

"Bugfixes / Testing": [Hirbod](https://github.com/hirbod), [Marco Hanke](https://github.com/marcohanke)
