<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Override;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\ApiFunction\AsApiFunction;
use Redaxo\Core\ApiFunction\Result;
use Redaxo\Core\Core;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;

/** Favorit umschalten (POST url, title, meta, icon) – Antwort {favorite: bool} */
#[AsApiFunction('quick_navigation_favorite')]
final class FavoriteApi extends ApiFunction
{
    #[Override]
    public function execute(): Result
    {
        $user = Core::requireUser();
        $favorite = Favorites::toggle(
            $user,
            Request::post('url', 'string', ''),
            Request::post('title', 'string', ''),
            Request::post('meta', 'string', '') ?: null,
            Request::post('icon', 'string', '') ?: null,
        );

        Response::cleanOutputBuffers();
        Response::sendJson(['favorite' => $favorite]);
        exit;
    }
}
