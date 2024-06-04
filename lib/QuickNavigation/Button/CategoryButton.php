<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex;
use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
use rex_context;
use rex_fragment;
use rex_i18n;
use rex_string;
use rex_url;
use rex_yrewrite;
use FriendsOfRedaxo\QuickNavigation\Utility\BuildNavigationArray;

class CategoryButton implements ButtonInterface
{
    public function renderCategoriesAsList(array $categoriesArray, int $depth = 0): array
    {
        $listItems = [];
        foreach ($categoriesArray as $item) {
            $attributes = [
                'href' => $item['url'],
                'title' => 'Domain: ' . $item['domain'],
            ];

            if ($item['current'] === true) {
                $attributes['class'] = 'quick-navigation-current';
            }

            $listItem =
                '<a' . rex_string::buildAttributes($attributes) . '>
                    ' . rex_escape($item['name']) . '
                    <small class="rex-primary-id">(' . rex_escape($item['id']) . ')</small>
                    <small class="hidden">' . rex_escape($item['domain']) . '</small>
                </a>';

            if (!empty($item['children'])) {
                $fragment = new rex_fragment([
                    'listItems' => $this->renderCategoriesAsList($item['children'], $depth + 1),
                ]);
                $listItem .= $fragment->parse('QuickNavigation/List.php');
            }

            $listItems[] = $listItem;
        }

        return $listItems;
    }

    public function get(): string
    {
        $ignoreOffline = false;
        $user = rex::getUser()->getId();
        if (rex_addon::get('quick_navigation')->getConfig('quick_navigation_ignoreoffline' . $user) == '1') {
            $ignoreOffline = true;
        }

        $currentClangId = rex_clang::getCurrentId();
        $categoriesArray = BuildNavigationArray::generateBackendNavArray($currentClangId, $ignoreOffline, null);

        $listItems = $this->renderCategoriesAsList($categoriesArray);
        $placeholder = rex_i18n::msg('quick_navigation_placeholder');
        $fragment = new rex_fragment();
        $fragment->setVar('id', 'quick-navigation-search');
        $fragment->setVar('placeholder', $placeholder);
        $fragment->setVar('class', 'input-group input-group-xs has-feedback form-clear-button');

        $searchbar = $fragment->parse('core/form/search.php');

        $fragment = new rex_fragment([
            'header' => $searchbar,
            'label' => rex_i18n::msg('quick_navigation_structure'),
            'icon' => 'fa-solid fa-folder-tree',
            'listItems' => $listItems,
            'listType' => 'tree',
        ]);
        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
}
