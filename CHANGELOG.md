# Changelog

## 9.0.2 – 2026-03-31

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
