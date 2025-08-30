<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex;
use rex_addon;
use rex_clang;
use rex_fragment;
use rex_i18n;
use rex_string;
use FriendsOfRedaxo\QuickNavigation\Utility\BuildNavigationArray;

class StructureTreeButton implements ButtonInterface
{
    private function hasCurrentInChildren(array $children): bool
    {
        foreach ($children as $child) {
            if ($child['current'] === true) {
                return true;
            }
            if (!empty($child['children']) && $this->hasCurrentInChildren($child['children'])) {
                return true;
            }
        }
        return false;
    }

    public function renderTreeAsList(array $categoriesArray, int $depth = 0): array
    {
        $listItems = [];
        foreach ($categoriesArray as $item) {
            $attributes = [
                'href' => $item['url'],
                'title' => rex_i18n::msg('quick_navigation_domain') . ' ' . $item['domain'],
                'data-category-id' => $item['id'],
                'data-level' => $depth,
                'data-status' => $item['status'] ?? 1, // Status für Online/Offline/Gesperrt
            ];

            if ($item['current'] === true) {
                $attributes['class'] = 'structure-tree-current';
            }

            $hasChildren = !empty($item['children']);
            $toggleClass = $hasChildren ? ' structure-tree-has-children' : '';

            // Prüfe ob dieses Item oder ein Child-Item aktuell ist (für Auto-Expand)
            $isInCurrentPath = $item['current'] === true || $this->hasCurrentInChildren($item['children'] ?? []);
            if ($isInCurrentPath) {
                $toggleClass .= ' expanded';
            }

            $listItem = '<div class="structure-tree-item' . $toggleClass . '">';

            // Toggle Button für expandierbare Items - rechts positioniert
            if ($hasChildren) {
                $expandedState = $isInCurrentPath ? 'true' : 'false';
                $iconClass = 'fa-ellipsis-h'; // Immer horizontale Punkte, Rotation erfolgt über CSS
                $listItem .= '<button type="button" class="structure-tree-toggle" aria-expanded="' . $expandedState . '">';
                $listItem .= '<i class="rex-icon ' . $iconClass . '"></i>';
                $listItem .= '</button>';
            }

            // Category Link (Icons werden via CSS hinzugefügt)
            $listItem .= '<a' . rex_string::buildAttributes($attributes) . '>';
            $listItem .= '<span>' . rex_escape($item['name']) . '</span>';
            $listItem .= '<small class="rex-primary-id">(' . rex_escape($item['id']) . ')</small>';
            if (!empty($item['domain']) && $item['domain'] !== 'default') {
                $listItem .= '<small class="structure-tree-domain">' . rex_escape($item['domain']) . '</small>';
            }
            $listItem .= '</a>';

            // Children als verschachteltes UL INNERHALB des Items
            if ($hasChildren) {
                $fragment = new rex_fragment([
                    'listItems' => $this->renderTreeAsList($item['children'], $depth + 1),
                    'cssClass' => 'structure-tree-children',
                ]);
                $listItem .= $fragment->parse('QuickNavigation/StructureTreeList.php');
            }

            $listItem .= '</div>';

            $listItems[] = $listItem;
        }

        return $listItems;
    }

    public function get(): string
    {
        // Alle Kategorien anzeigen (auch offline/gesperrt) für vollständigen Baum
        $ignoreOffline = false;

        $currentClangId = rex_clang::getCurrentId();
        $categoriesArray = BuildNavigationArray::GenerateBackendNavArray($currentClangId, $ignoreOffline, null);

        // Render Tree Structure
        $treeItems = $this->renderTreeAsList($categoriesArray);

        // Search placeholder
        $placeholder = rex_i18n::msg('quick_navigation_placeholder_search');

        // Render Off-Canvas Button (nicht Dropdown)
        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('quick_navigation_structure_tree'),
            'icon' => 'fa-solid fa-sitemap',
            'buttonClass' => 'btn btn-default quick-navigation-button',
            'buttonId' => 'structure-tree-trigger',
            'treeItems' => $treeItems,
            'placeholder' => $placeholder,
        ]);

        return $fragment->parse('QuickNavigation/StructureTreeButton.php');
    }
}
