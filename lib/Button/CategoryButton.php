<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use FriendsOfRedaxo\QuickNavigation\Utility\BuildNavigationArray;
use rex;
use rex_addon;
use rex_clang;
use rex_fragment;
use rex_i18n;
use rex_string;

class CategoryButton implements ButtonInterface
{
    public function RenderCategoriesAsList(array $categoriesArray, int $depth = 0): array
    {
        $listItems = [];
        $accessdeniedAvailable = rex_addon::get('accessdenied')->isAvailable();

        foreach ($categoriesArray as $item) {
            $status = $item['status'] ?? 1;

            $classes = [];
            if ($item['current'] === true) {
                $classes[] = 'quick-navigation-current';
            }
            if ($status === 1) {
                $classes[] = 'quick-navigation-status-online';
            } elseif ($status === 2 && $accessdeniedAvailable) {
                $classes[] = 'quick-navigation-status-restricted';
            } else {
                $classes[] = 'quick-navigation-status-offline';
            }

            $attributes = [
                'href' => $item['url'],
                'title' => 'Domain: ' . $item['domain'],
                'class' => implode(' ', $classes),
            ];

            $listItem =
                '<a' . rex_string::buildAttributes($attributes) . '>
                    ' . rex_escape($item['name']) . '
                    <small class="rex-primary-id">(' . rex_escape($item['id']) . ')</small>
                    <small class="hidden">' . rex_escape($item['domain']) . '</small>
                </a>';

            if (!empty($item['children'])) {
                $fragment = new rex_fragment([
                    'listItems' => $this->RenderCategoriesAsList($item['children'], $depth + 1),
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
        $categoriesArray = BuildNavigationArray::GenerateBackendNavArray($currentClangId, $ignoreOffline, null);

        $listItems = $this->RenderCategoriesAsList($categoriesArray);
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
