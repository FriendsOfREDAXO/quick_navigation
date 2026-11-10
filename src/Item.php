<?php

namespace FriendsOfRedaxo\QuickNavigation;

use function array_map;

/** Ein Treffer in der Befehlspalette */
final class Item
{
    /** @param list<Action> $actions */
    public function __construct(
        public string $title,
        public string $url,
        /** Zweite Zeile, z. B. der Pfad „Über uns › Team“ */
        public ?string $meta = null,
        /** Icon-Klassen, z. B. 'rex-icon rex-icon-article' */
        public ?string $icon = null,
        /** Kurzer Hinweis rechts, z. B. „offline“ oder der Sprachcode */
        public ?string $badge = null,
        public array $actions = [],
        /** In neuem Tab öffnen */
        public bool $external = false,
        /** Vorschaubild statt Icon */
        public ?string $thumbnail = null,
        /** Relevanz innerhalb der Gruppe (höher zuerst), siehe Query::score() */
        public int $score = 0,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'meta' => $this->meta,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'external' => $this->external,
            'thumbnail' => $this->thumbnail,
            'actions' => array_map(static fn (Action $a) => $a->toArray(), $this->actions),
        ];
    }
}
