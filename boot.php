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

use FriendsOfRedaxo\QuickNavigation\ApiFunction\MenuRender;
use FriendsOfRedaxo\QuickNavigation\Button\ArticleHistoryButton;
use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
use FriendsOfRedaxo\QuickNavigation\Button\ArticleNavigationButton;
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
    rex_view::addCssFile(rex_addon::get('quick_navigation')->getAssetsUrl('quick-navigation.css'));
    rex_view::addJsFile(rex_addon::get('quick_navigation')->getAssetsUrl('quick-navigation.js'));

    $userId = rex::getUser()->getId();
    if (rex_addon::get('quick_navigation')->getConfig('quick_navigation_artdirections' . $userId) != '1') {
        ButtonRegistry::registerButton(new ArticleNavigationButton(), 10);
    }

    ButtonRegistry::registerButton(new WatsonButton(), 20);
    ButtonRegistry::registerButton(new CategoryButton(), 30);
    ButtonRegistry::registerButton(new ArticleHistoryButton('structure', 20), 40);
    ButtonRegistry::registerButton(new YformButton(), 50);
    ButtonRegistry::registerButton(new FavoriteButton(), 60);

    // Addonrechte (permissions) registieren
    rex_perm::register('quick_navigation[]');
    rex_perm::register('quick_navigation[history]');
    rex_perm::register('quick_navigation[all_changes]');
        
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

            return '<div id="quick-navigation-structure" data-url="' . rex_url::currentBackendPage($params + MenuRender::getUrlParams()) . '"></div>' . $ep->getSubject();
        });
        rex_extension::register('PAGE_TITLE_SHOWN', QuickNavigationLinkMap::LinkMapNavigation(...));
        rex_extension::register('MEDIA_LIST_TOOLBAR', QuickNavigationMedia::MediaHistory(...));
    }
}

/* Minibar */
if (rex::isFrontend() && rex_addon::get('minibar')->isAvailable()) {
    rex_minibar::getInstance()->addElement(new ArticleHistoryElement());
}
