<?php

namespace FriendsOfRedaxo\QuickNavigation\Domain;

/** Eine Domain der Website, wie sie der Strukturbaum zeigt – eingetragen per {@see \\FriendsOfRedaxo\\QuickNavigation\\ExtensionPoint\\QuickNavigationDomains} */
final readonly class Domain
{
    public function __construct(
        /** eindeutig, z. B. der Host */
        public string $key,
        /** Anzeige, z. B. „example.com“ */
        public string $label,
        /** Kategorie, in der die Domain beginnt (0 = ganze Struktur) */
        public int $mountId = 0,
        /** Adresse der Startseite */
        public ?string $url = null,
    ) {}

    /** @return array{key: string, label: string, mount: int, url: ?string} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'mount' => $this->mountId, 'url' => $this->url];
    }
}
