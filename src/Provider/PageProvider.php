<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Backend\Controller;
use Redaxo\Core\Backend\Page;
use Redaxo\Core\Translation\I18n;

use function array_values;
use function implode;
use function in_array;
use function strip_tags;
use function trim;

/** Backend-Seiten und Unterseiten, die der Benutzer öffnen darf */
#[AsProvider('pages', priority: 100, prefix: 'p')]
final class PageProvider implements Provider
{
    /** Seiten, die in der Suche nichts verloren haben */
    private const array SKIP = ['login', 'credits', 'content', 'linkmap', 'mediapool/media_popup'];

    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_pages');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        if ($query->isEmpty() && !$query->scoped) {
            return [];
        }
        // eine Seite mit Unterseiten führt zur ersten Unterseite – gleiche Adresse nur einmal, der bessere Treffer zählt
        $items = [];
        foreach (Controller::getPages() as $page) {
            foreach ($this->walk($page, $query, [], null) as $item) {
                if (!isset($items[$item->url]) || $items[$item->url]->score < $item->score) {
                    $items[$item->url] = $item;
                }
            }
        }

        return array_values($items);
    }

    /**
     * @param list<string> $trail Titel der übergeordneten Seiten
     * @return iterable<Item>
     */
    private function walk(Page $page, Query $query, array $trail, ?string $icon): iterable
    {
        if ($page->isHidden() || $page->isPopup() || in_array($page->getFullKey(), self::SKIP, true)
            || !$page->checkPermission($query->user)) {
            return;
        }
        $title = trim(strip_tags($page->getTitle()));
        if ('' === $title) {
            return;
        }
        $icon = $page->getIcon() ?? $icon;

        $score = $query->score($title, implode(' ', $trail), $page->getFullKey());
        if ($score > 0) {
            yield new Item(
                title: $title,
                url: $page->getHref(),
                meta: $trail ? implode(' › ', $trail) : null,
                icon: $icon ?? 'rex-icon rex-icon-package-addon',
                score: $score + ($trail ? 0 : 50),
            );
        }
        foreach ($page->getSubpages() as $sub) {
            yield from $this->walk($sub, $query, [...$trail, $title], $icon);
        }
    }
}
