<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Override;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\ApiFunction\AsApiFunction;
use Redaxo\Core\ApiFunction\Result;
use Redaxo\Core\Core;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;

use function array_column;
use function array_reverse;
use function in_array;

/**
 * Favoriten (POST):
 *  - mit `url`, `title`, `meta`, `icon`: Favorit umschalten
 *  - mit `current`: nur die Liste, dazu ob diese Adresse (die Seite des Stern-Knopfs) ein Favorit ist
 *
 * Antwort {favorite: bool, current: bool, favorites: [{url, title, meta, icon}]} – neueste zuerst.
 */
#[AsApiFunction('quick_navigation_favorite')]
final class FavoriteApi extends ApiFunction
{
    #[Override]
    public function execute(): Result
    {
        $user = Core::requireUser();
        $url = Request::post('url', 'string', '');
        $favorite = false;
        if ('' !== $url) {
            $favorite = Favorites::toggle(
                $user,
                $url,
                Request::post('title', 'string', ''),
                Request::post('meta', 'string', '') ?: null,
                Request::post('icon', 'string', '') ?: null,
            );
        }
        $current = Favorites::normalize(Request::post('current', 'string', ''));
        $favorites = array_reverse(Favorites::all($user));

        Response::cleanOutputBuffers();
        Response::sendJson([
            'favorite' => $favorite,
            'current' => null !== $current && in_array($current, array_column($favorites, 'url'), true),
            'favorites' => $favorites,
        ]);
        exit;
    }
}
