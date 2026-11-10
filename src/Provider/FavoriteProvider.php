<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Favorites;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Translation\I18n;

use function array_reverse;

/** Favoriten des Benutzers – ohne Eingabe ganz oben, neueste zuerst */
#[AsProvider('favorites', priority: 110, prefix: 'f', limit: 12)]
final class FavoriteProvider implements Provider
{
    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_favorites');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        foreach (array_reverse(Favorites::all($query->user)) as $favorite) {
            $score = $query->score($favorite['title'], (string) $favorite['meta']);
            if ($score > 0) {
                yield new Item(
                    title: $favorite['title'],
                    url: $favorite['url'],
                    meta: $favorite['meta'],
                    icon: $favorite['icon'] ?? 'rex-icon fa-solid fa-star',
                    score: $score,
                );
            }
        }
    }
}
