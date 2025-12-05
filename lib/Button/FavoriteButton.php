<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use function count;

use rex;
use rex_addon;
use rex_be_controller;
use rex_category;
use rex_clang;

use function rex_escape;

use rex_fragment;
use rex_i18n;
use rex_string;
use rex_url;

class FavoriteButton implements ButtonInterface
{
    public function get(): string
    {
        $user = rex::getUser();

        if (!$user) {
            return '';
        }

        $categoryIds = rex_addon::get('quick_navigation')->getConfig('quick_navigation_favs' . $user->getId());
        $addonPages = rex_addon::get('quick_navigation')->getConfig('quick_navigation_addon_favs' . $user->getId(), []);

        $listItems = [];
        
        // Struktur-Favoriten zuerst
        if ($categoryIds && count($categoryIds) > 0) {
            $listItems[] = '<div class="quick-navigation-section-header">' . rex_i18n::msg('quick_navigation_structure_favs') . '</div>';
            
            $clangId = rex_request('clang', 'int', rex_clang::getStartId());
            foreach ($categoryIds as $categoryId) {

                if (!$user->getComplexPerm('structure')->hasCategoryPerm($categoryId)) {
                    continue;
                }

                $category = rex_category::get($categoryId);
                if ($category) {
                    $name = $category->getName();
                } elseif (0 === $categoryId) {
                    $name = rex_i18n::msg('root_level');
                } else {
                    continue;
                }

                $attributesArticleAdd = [
                    'href' => rex_url::backendPage('structure', ['clang' => $clangId, 'category_id' => $categoryId, 'function' => 'add_art']),
                    'title' => rex_i18n::msg('quick_navigation_favorite_article_add') . ' ' . $name,
                ];

                $attributesCategoryAdd = [
                    'href' => rex_url::backendPage('structure', ['clang' => $clangId, 'category_id' => $categoryId, 'function' => 'add_cat']),
                    'title' => rex_i18n::msg('quick_navigation_favorite_category_add') . ' ' . $name,
                ];

                $attributesCategoryLink = [
                    'href' => rex_url::backendPage('structure', ['clang' => $clangId, 'category_id' => $categoryId]),
                    'title' => $name,
                ];

                $listItem = '
                        <div class="quick-navigation-item-row">
                            <a' . rex_string::buildAttributes($attributesCategoryLink) . '>
                                ' . rex_escape($name) . '
                            </a>
                            <a' . rex_string::buildAttributes($attributesCategoryAdd) . '>
                                <i class="fa fa-folder-plus" aria-hidden="true"></i>
                            </a>
                            <a' . rex_string::buildAttributes($attributesArticleAdd) . '>
                                <i class="fa fa-file-medical" aria-hidden="true"></i>
                            </a>
                        </div>
                    ';

                $listItems[] = $listItem;
            }
        }
        
        // AddOn-Seiten Favoriten danach
        if (is_array($addonPages) && count($addonPages) > 0) {
            if (count($listItems) > 0) {
                $listItems[] = '<div class="quick-navigation-section-divider"></div>';
            }
            $listItems[] = '<div class="quick-navigation-section-header">' . rex_i18n::msg('quick_navigation_addon_pages') . '</div>';
            
            foreach ($addonPages as $pageKey) {
                $pageInfo = $this->parsePageKey($pageKey);
                if (!$pageInfo) {
                    continue;
                }
                
                // Check permission
                $page = rex_be_controller::getPageObject($pageInfo['fullKey']);
                if (!$page || $page->isHidden()) {
                    continue;
                }
                
                // Check user permission
                $requiredPerms = $page->getRequiredPermissions();
                if (!empty($requiredPerms) && !$user->hasPerm($requiredPerms)) {
                    continue;
                }
                
                $attributes = [
                    'href' => rex_url::backendPage($pageInfo['fullKey']),
                    'title' => $pageInfo['title'],
                ];
                
                $listItem = '
                    <div class="quick-navigation-item-row quick-navigation-addon-fav">
                        <a' . rex_string::buildAttributes($attributes) . '>
                            <i class="' . rex_escape($pageInfo['icon']) . '" aria-hidden="true"></i>
                            ' . rex_escape($pageInfo['title']) . '
                        </a>
                    </div>
                ';
                
                $listItems[] = $listItem;
            }
        }
        
        // Keine Favoriten
        if (count($listItems) === 0) {
            $listItems[] = '<a class="btn manage_favortites" href="'.rex_url::backendPage('quick_navigation/config').'">'.rex_i18n::msg('quick_navigation_manage_favorite').'</a>';
        }

        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('quick_navigation_favorite'),
            'icon' => 'fa-regular fa-star',
            'listItems' => $listItems,
        ]);
        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
    
    /**
     * Parse page key and return info
     * @return array{fullKey: string, title: string, icon: string}|null
     */
    private function parsePageKey(string $pageKey): ?array
    {
        $page = rex_be_controller::getPageObject($pageKey);
        if (!$page) {
            return null;
        }
        
        return [
            'fullKey' => $pageKey,
            'title' => $page->getTitle(),
            'icon' => $page->getIcon() ?: 'rex-icon fa-cube',
        ];
    }
    
    /**
     * Get all available backend pages for favorites
     * @return array<array{key: string, title: string, icon: string, addon: string}>
     */
    public static function getAvailablePages(): array
    {
        $user = rex::getUser();
        if (!$user) {
            return [];
        }
        
        $pages = [];
        $pageContainer = rex_be_controller::getPages();
        
        foreach ($pageContainer as $pageKey => $page) {
            // Skip system pages
            if (in_array($pageKey, ['setup', 'login', 'logout', '2factor_auth', '2factor_auth_verify'], true)) {
                continue;
            }
            
            // Skip if user has no permission
            if ($page->isHidden()) {
                continue;
            }
            
            $requiredPerms = $page->getRequiredPermissions();
            if (!empty($requiredPerms) && !$user->hasPerm($requiredPerms)) {
                continue;
            }
            
            $addonName = $pageKey;
            $pages[] = [
                'key' => $pageKey,
                'title' => $page->getTitle(),
                'icon' => $page->getIcon() ?: 'rex-icon fa-cube',
                'addon' => $addonName,
            ];
            
            // Add subpages
            foreach ($page->getSubpages() as $subpageKey => $subpage) {
                if ($subpage->isHidden()) {
                    continue;
                }
                
                $subRequiredPerms = $subpage->getRequiredPermissions();
                if (!empty($subRequiredPerms) && !$user->hasPerm($subRequiredPerms)) {
                    continue;
                }
                
                $fullKey = $pageKey . '/' . $subpageKey;
                $pages[] = [
                    'key' => $fullKey,
                    'title' => '  → ' . $subpage->getTitle(),
                    'icon' => $subpage->getIcon() ?: 'rex-icon fa-cube',
                    'addon' => $addonName,
                ];
            }
        }
        
        return $pages;
    }
}
