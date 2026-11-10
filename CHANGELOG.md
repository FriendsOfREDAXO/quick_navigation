# Changelog

## 10.0.0-dev

Neuentwicklung für REDAXO 6 (Branch 6.x), nicht kompatibel zu 9.x für REDAXO 5.

- Befehlspalette mit ⌘K / Strg+K und Suchknopf in der Kopfleiste
- Live-Suche über Backend-Seiten, Artikel und Kategorien, Medienpool und Benutzer – nach den Rechten des Benutzers
- Kürzel `p:`, `a:`, `m:`, `u:` für die Suche in einer Quelle
- Zuletzt geöffnet (im Browser) und zuletzt bearbeitet
- Zusatzaktionen je Treffer, Öffnen in neuem Tab
- Erweiterbar über `#[AsProvider]` bzw. `QuickNavigation::register()`
- Strukturbaum neben der Suche: aufgeklappt bis zur aktuellen Seite, lädt Ebenen nach, Sprachumschaltung, Mountpoints
- Medienpool: Live-Suche im Suchfeld mit „zuletzt bearbeitet“, Übernehmen im Auswahl-Modus, Sortierung (neueste, Dateiname, Titel)
- Strukturbaum mit Suche (ganze Struktur, Treffer mit ihren Kategorien); Kopfleiste nur mit Symbol-Knöpfen (Lupe, Baum)
- Domains: Extension Point QuickNavigationDomains für AddOns (fluid, yrewrite …) – Domain-Auswahl im Strukturbaum, Domain-Badge in der Palette
