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

use FriendsOfRedaxo\QuickNavigation\ApiFunction\MediaSearch;
use FriendsOfRedaxo\QuickNavigation\ApiFunction\MenuRender;
use FriendsOfRedaxo\QuickNavigation\Button\ArticleHistoryButton;
use FriendsOfRedaxo\QuickNavigation\Button\ArticleNavigationButton;
use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
use FriendsOfRedaxo\QuickNavigation\Button\CategoryButton;
use FriendsOfRedaxo\QuickNavigation\Button\FavoriteButton;
use FriendsOfRedaxo\QuickNavigation\Button\WatsonButton;
use FriendsOfRedaxo\QuickNavigation\Button\YformButton;
use FriendsOfRedaxo\QuickNavigation\Linkmap\QuickNavigationLinkMap;
use FriendsOfRedaxo\QuickNavigation\Media\QuickNavigationMedia;
use FriendsOfRedaxo\QuickNavigation\Minibar\ArticleHistoryElement;
use rex;
use rex_addon;
use rex_api_function;
use rex_backend_login;
use rex_be_controller;
use rex_clang;
use rex_extension;
use rex_minibar;
use rex_perm;
use rex_url;
use rex_view;

if (rex::isBackend() && rex::getUser() && rex_backend_login::hasSession() && rex_be_controller::getCurrentPage() != '2factor_auth_verify') {
    if (rex::getUser()->hasPerm('quick_navigation[]')) {
        rex_api_function::register('quicknavigation_api', MenuRender::class);
        rex_api_function::register('quicknavigation_media_search', MediaSearch::class);
        rex_view::addCssFile(rex_addon::get('quick_navigation')->getAssetsUrl('quick-navigation.css'));
        rex_view::addCssFile(rex_addon::get('quick_navigation')->getAssetsUrl('media-live-search.css'));
        rex_view::addJsFile(rex_addon::get('quick_navigation')->getAssetsUrl('quick-navigation.js'));
        rex_view::addJsFile(rex_addon::get('quick_navigation')->getAssetsUrl('media-live-search.js'));

        // Media Live-Search Einstellung für aktuellen User
        $userId = rex::getUser()->getId();
        $mediaLiveSearchEnabled = rex_addon::get('quick_navigation')->getConfig('quick_navigation_media_livesearch' . $userId, 1); // Default: aktiviert
        rex_view::setJsProperty('QUICKNAV_MEDIA_LIVESEARCH_ENABLED', (bool) $mediaLiveSearchEnabled);

        $userId = rex::getUser()->getId();
        if (rex_addon::get('quick_navigation')->getConfig('quick_navigation_artdirections' . $userId) != '1') {
            ButtonRegistry::registerButton(
                new ArticleNavigationButton(),
                10,
                'article_navigation',
                rex_addon::get('quick_navigation')->i18n('quick_navigation_button_article_navigation')
            );
        }

        ButtonRegistry::registerButton(
            new WatsonButton(),
            20,
            'watson',
            rex_addon::get('quick_navigation')->i18n('quick_navigation_button_watson')
        );
        ButtonRegistry::registerButton(
            new CategoryButton(),
            30,
            'category',
            rex_addon::get('quick_navigation')->i18n('quick_navigation_button_category')
        );
        ButtonRegistry::registerButton(
            new ArticleHistoryButton('structure', 20),
            40,
            'article_history',
            rex_addon::get('quick_navigation')->i18n('quick_navigation_button_article_history')
        );
        ButtonRegistry::registerButton(
            new YformButton(),
            50,
            'yform',
            rex_addon::get('quick_navigation')->i18n('quick_navigation_button_yform')
        );
        ButtonRegistry::registerButton(
            new FavoriteButton(),
            60,
            'favorite',
            rex_addon::get('quick_navigation')->i18n('quick_navigation_button_favorite')
        );

        // Addonrechte (permissions) registieren
        rex_perm::register('quick_navigation[]');
        rex_perm::register('quick_navigation[history]');
        rex_perm::register('quick_navigation[all_changes]');

        rex_extension::register('PAGE_TITLE', static function (\rex_extension_point $ep) {
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

            return '<div id="quick-navigation-structure" data-url="' . rex_url::currentBackendPage($params + MenuRender::getUrlParams()) . '"></div>' . $ep->getSubject();
        });
        rex_extension::register('PAGE_TITLE_SHOWN', QuickNavigationLinkMap::LinkMapNavigation(...));
        rex_extension::register('MEDIA_LIST_TOOLBAR', QuickNavigationMedia::MediaHistory(...));

        // Registriere den Extension Point für die Mediensortierung
        rex_extension::register('MEDIA_LIST_QUERY', [QuickNavigationMedia::class, 'ModifyMediaListQuery']);
    }
}

/* Minibar */
if (rex::isFrontend() && rex_addon::get('minibar')->isAvailable()) {
    rex_minibar::getInstance()->addElement(new ArticleHistoryElement());
}
