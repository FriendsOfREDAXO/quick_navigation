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

if (rex::isBackend() && rex::getUser() && rex_backend_login::hasSession() && rex_be_controller::getCurrentPage() != '2factor_auth_verify') {
    // Addonrechte (permissions) registieren    
    rex_perm::register('quick_navigation[]');
    rex_perm::register('quick_navigation[history]');
    rex_perm::register('quick_navigation[all_changes]');

    if (rex::getUser()->hasPerm('quick_navigation[]')) {
        rex_extension::register('PAGE_TITLE', function ($ep) {
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
                'buster' => time()
            ];
            return '<div id="rex-quicknavigation-structure" data-url="' . rex_url::currentBackendPage($params + rex_api_quicknavigation_render::getUrlParams()) . '"></div>' . $ep->getSubject();
        });
         rex_extension::register('PAGE_TITLE_SHOWN', QuickNavigation::linkmap_list(...));
        rex_extension::register('MEDIA_LIST_TOOLBAR', QuickNavigation::media_history(...));
        rex_view::addCssFile(rex_addon::get('quick_navigation')->getAssetsUrl('quicknavi.css'));
        rex_view::addJsFile(rex_addon::get('quick_navigation')->getAssetsUrl('quicknavi.js'));
    }
    // Set Config for User fav if Config is not set
    if (!rex_addon::get('quick_navigation')->hasConfig()) {
        $user =  rex::getUser()->getId();
        rex_addon::get('quick_navigation')->setConfig('quicknavi_favs' . $user, []);
    }
}
/* Minibar */
if (rex::isFrontend() && rex_addon::get('minibar')->isAvailable()) {
    rex_minibar::getInstance()->addElement(new rex_minibar_element_quicknavi());
}
