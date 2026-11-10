<?php

namespace FriendsOfRedaxo\QuickNavigation;

/** Weitere Aktion an einem Treffer (z. B. „Ansehen“ oder „Metadaten“) – mit Pfeil rechts oder per Maus erreichbar */
final readonly class Action
{
    public function __construct(
        public string $label,
        public string $url,
        /** Icon-Klassen, z. B. 'rex-icon rex-icon-view' */
        public ?string $icon = null,
        /** In neuem Tab öffnen (z. B. Website) */
        public bool $external = false,
    ) {}

    /** @return array<string, string|bool|null> */
    public function toArray(): array
    {
        return ['label' => $this->label, 'url' => $this->url, 'icon' => $this->icon, 'external' => $this->external];
    }
}
