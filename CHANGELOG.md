# Changelog

## 9.1.1 – 2026-09-17

### Fix: "Class rex_api_quicknavigation_render not found" nach Update (#208)

- Die deprecated Kompatibilitätsklassen `rex_api_quicknavigation_render` und `rex_api_quicknavigation_media_search` wurden im Zuge eines CS-Fixer-/Cleanup-Commits versehentlich entfernt (waren ursprünglich als Fix für #165 eingeführt worden)
- REDAXOs globaler Autoload-Classmap-Cache wird bei einem Addon-Update nicht in jedem Fall zuverlässig für alle betroffenen Klassen neu aufgebaut, insbesondere bei älteren Core-Versionen – dadurch verwies der Cache teils noch auf die alte Klasse, bis ein Safe-Mode-Toggle einen kompletten Cache-Rebuild erzwang
- Beide `@deprecated`-Klassen wieder hinzugefügt, um Installationen mit veraltetem Autoload-Cache robust abzufangen, bis der Cache sich selbst korrigiert

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
