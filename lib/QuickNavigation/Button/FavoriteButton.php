<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex;
use rex_addon;
use rex_category;
use rex_clang;
use rex_fragment;
use rex_i18n;
use rex_string;
use rex_url;

use function count;
use function rex_escape;

class FavoriteButton implements ButtonInterface
{
    public function get(): string
    {
        $user = rex::getUser();

        if (!$user) {
            return '';
        }

        $categoryIds = rex_addon::get('quick_navigation')->getConfig('quick_navigation_favs' . $user->getId());

        $listItems = [];
        if ($categoryIds && count($categoryIds) > 0) {
            $clangId = rex_request('clang', 'int', rex_clang::getStartId());
            foreach ($categoryIds as $categoryId) {

                if (!$user->getComplexPerm('structure')->hasCategoryPerm($categoryId)) {
                    continue;
                }

                $category = rex_category::get($categoryId);
                if ($category) {
                    $name = $category->getName();
                } elseif (0 === $categoryId)  {
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
        } else {
            $fragment = new rex_fragment();
            $listItems[] = $fragment->parse('QuickNavigation/NoResult.php');
        }

        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('quick_navigation_favorite'),
            'icon' => 'fa-regular fa-star',
            'listItems' => $listItems,
        ]);
        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
}
