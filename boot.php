<?php
/**
 * This file is part of the Quick Navigation package.
 *
 * @author (c) Friends Of REDAXO
 * @author <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Buttons\ArticleHistory;
use FriendsOfRedaxo\QuickNavigation\Buttons\ArticleNavButton;
use FriendsOfRedaxo\QuickNavigation\Buttons\CatsButton;
use FriendsOfRedaxo\QuickNavigation\Buttons\FavsButton;
use FriendsOfRedaxo\QuickNavigation\Buttons\WatsonButton;
use FriendsOfRedaxo\QuickNavigation\Buttons\YformButton;
use rex;
use rex_addon;
use rex_api_quicknavigation_render;
use rex_backend_login;
use rex_be_controller;
use rex_clang;
use rex_extension;
use rex_minibar;
use rex_perm;
use rex_url;
use rex_view;

if (rex::isBackend() && rex::getUser() && rex_backend_login::hasSession() && rex_be_controller::getCurrentPage() != '2factor_auth_verify') {
    rex_view::addCssFile(rex_addon::get('quick_navigation')->getAssetsUrl('quicknavi.css'));
    rex_view::addJsFile(rex_addon::get('quick_navigation')->getAssetsUrl('quicknavi.js'));

    $userId = rex::getUser()->getId();
    if (rex_addon::get('quick_navigation')->getConfig('quicknavi_artdirections' . $userId) != '1') {
        ButtonRegistry::registerButton(new ArticleNavButton(), 10);
    }
    ButtonRegistry::registerButton(new WatsonButton(), 20);
    ButtonRegistry::registerButton(new CatsButton(), 30);
    ButtonRegistry::registerButton(new ArticleHistory('structure', 20), 40);
    ButtonRegistry::registerButton(new YformButton(), 50);
    ButtonRegistry::registerButton(new FavsButton(), 60);

    // Addonrechte (permissions) registieren
    rex_perm::register('quick_navigation[]');
    rex_perm::register('quick_navigation[history]');
    rex_perm::register('quick_navigation[all_changes]');

    if (rex::getUser()->hasPerm('quick_navigation[]')) {
        rex_extension::register('PAGE_TITLE', static function ($ep) {
            if (rex_be_controller::getCurrentPageObject()->isPopup()) {
                return $ep->getSubject();
            }

            $clang = rex_request('clang', 'int');
            $clang = rex_clang::exists($clang) ? $clang : rex_clang::getStartId();

            $category_id = rex_request('category_id', 'int');
            $article_id = rex_request('article_id', 'int');

            $params = [
                'clang' => $clang,
                'category_id' => $category_id,
                'article_id' => $article_id,
                'buster' => time(),
            ];
            return '<div id="rex-quicknavigation-structure" data-url="' . rex_url::currentBackendPage($params + rex_api_quicknavigation_render::getUrlParams()) . '"></div>' . $ep->getSubject();
        });
        rex_extension::register('PAGE_TITLE_SHOWN', QuickNavigation::linkmap_list(...));
        rex_extension::register('MEDIA_LIST_TOOLBAR', QuickNavigation::media_history(...));
    }
}

/* Minibar */
if (rex::isFrontend() && rex_addon::get('minibar')->isAvailable()) {
    rex_minibar::getInstance()->addElement(new rex_minibar_element_quicknavi());
}
