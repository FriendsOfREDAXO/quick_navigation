<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Attribute;

/**
 * Meldet eine {@see Provider}-Klasse für die Befehlspalette an.
 *
 *  - key       eindeutiger Schlüssel (überschreibt eine gleichnamige Quelle mit geringerer Priorität)
 *  - priority  Reihenfolge der Gruppen, hohe Werte zuerst (Seiten 100, Struktur 90, Medien 80, Benutzer 50)
 *  - prefix    Kürzel, um nur in dieser Quelle zu suchen: „m: logo“ (Buchstaben); „>“ ist den Befehlen vorbehalten
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsProvider
{
    public function __construct(
        public string $key,
        public int $priority = 0,
        public ?string $prefix = null,
        /** Treffer ohne Kürzel (sonst {@see QuickNavigation::LIMIT}) */
        public ?int $limit = null,
    ) {}
}
