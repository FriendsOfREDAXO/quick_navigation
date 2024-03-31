<?php

namespace FriendsOfRedaxo\QuickNavigation\Buttons;

use rex;
use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
use rex_context;
use rex_fragment;
use rex_i18n;
use rex_url;
use rex_yrewrite;

class CatsButton implements ButtonInterface
{
    /**
     * @return array<mixed>
     */
    public function generateBackendNavArray(int $clangId = null, bool $ignoreOffline = true, int $parentId = null, bool $includeHome = true): array
    {
        $user = rex::getUser();
        if ($clangId === null) {
            $clangId = rex_clang::getCurrentId();
        }

        $backendContext = rex_context::fromGet();
        $backendContext->setParam('rex-api-call', 0);
        $backendContext->setParam('page', 'structure');
        $backendContext->setParam('clang', $clangId);

        $articleId = rex_request('article_id', 'int');
        $currentId = rex_request('category_id', 'int', $articleId);
        if ($article = rex_article::get($articleId)) {
            $currentId = $article->getCategoryId();
        }

        $categoriesArray = [];

        if ($includeHome) {
            $categoriesArray[] = [
                'id' => 0,
                'name' => 'home',
                'current' => $currentId === 0,
                'domain' => '',
                'url' => rex_url::backendPage('structure', ['clang' => $clangId]),
                'children' => [],
            ];
        }

        $categories = [];

        if ($parentId === null) {
            $mountpoints = $user->getComplexPerm('structure')->getMountpoints();
            if (!empty($mountpoints)) {
                foreach ($mountpoints as $mpId) {
                    if ($mpCategory = rex_category::get($mpId, $clangId)) {
                        $categories[] = $mpCategory;
                    }
                }
            } else {
                $categories = rex_category::getRootCategories($ignoreOffline, $clangId);
            }
        } else {
            if ($parentCategory = rex_category::get($parentId, $clangId)) {
                $categories = $parentCategory->getChildren($ignoreOffline);
            }
        }

        foreach ($categories as $category) {
            if (!$user->getComplexPerm('structure')->hasCategoryPerm($category->getId())) {
                continue;
            }

            $categoryId = $category->getId();
            $backendContext->setParam('category_id', $categoryId);
            $backendContext->setParam('article_id', $categoryId);
            $domainName = '';
            if (rex_addon::get('yrewrite')->isAvailable()) {
                $domainName = rex_escape(rex_yrewrite::getDomainByArticleId($categoryId)->getName());
            }

            $current = $categoryId == $currentId;

            $categoriesArray[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'current' => $current,
                'domain' => $domainName,
                'url' => $backendContext->getUrl(),
                'children' => self::generateBackendNavArray($clangId, $ignoreOffline, $category->getId(), false),
            ];
        }

        return $categoriesArray;
    }

    /**
     * @param  array<mixed> $categoriesArray
     */
    public function renderCategoriesAsList(array $categoriesArray, int $depth = 0): string
    {
        if (empty($categoriesArray)) {
            return ''; // Keine Kategorien zu rendern
        }

        $html = '<ul>';
        foreach ($categoriesArray as $item) {
            $current = '';
            if ($item['current'] === true) {
                $current = ' bg-primary';
            }

            // Hinzufügen von 3 Leerzeichen je Ebene
            $indentation = str_repeat('&nbsp;&nbsp;&nbsp;', $depth); // Erzeugt die Einrückung
            $html .= '<li class="quickitem">';
            $html .= '<a class="quicklink' . $current . '" href="' . $item['url'] . '">' . $indentation . htmlspecialchars($item['name']) . '</a>';

            if (!empty($item['children'])) {
                // Erhöhe die Tiefe um 1 für die Kinder
                $html .= self::renderCategoriesAsList($item['children'], $depth + 1);
            }

            $html .= '</li>';
        }

        return $html . '</ul>';
    }

    public function get(): string
    {
        $ignoreOffline = false;
        $qn_user = rex::getUser()->getId();
        if (rex_addon::get('quick_navigation')->getConfig('quicknavi_ignoreoffline' . $qn_user) == '1') {
            $ignoreOffline = true;
        }

        $currentClangId = rex_clang::getCurrentId();
        $categoriesArray = self::generateBackendNavArray($currentClangId, $ignoreOffline, null); // Argumente nach Bedarf anpassen

        $html = self::renderCategoriesAsList($categoriesArray);
        $placeholder = rex_i18n::msg('quicknavi_placeholder');
        $fragment = new rex_fragment();
        $fragment->setVar('id', 'qsearch');
        $fragment->setVar('placeholder', $placeholder);
        $fragment->setVar('class', 'input-group input-group-xs has-feedback form-clear-button');

        $searchbar = $fragment->parse('core/form/search.php');

        $fragment = new rex_fragment();
        $fragment->setVar('button_prefix', '');
        $fragment->setVar('header', $searchbar, false);
        $fragment->setVar('button_label', 'Quick');
        $fragment->setVar('items', $html, false);
        $fragment->setVar('right', true, false);
        $fragment->setVar('icon', 'fa fa-clock');
        $fragment->setVar('group', true, false);

        return '<div class="btn-group">' . $fragment->parse('quick_cats.php') . '</div>';
    }
}
