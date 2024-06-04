<?php

namespace FriendsOfRedaxo\QuickNavigation\Utility;

use rex;
use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
use rex_context;
use rex_i18n;
use rex_url;
use rex_yrewrite;

class BuildNavigationArray
{
    /**
     * @return array<mixed>
     */
    public static function generateBackendNavArray(int $clangId = null, bool $ignoreOffline = true, int $parentId = null, bool $includeHome = true): array
    {
        $user = rex::getUser();
        if ($clangId === null) {
            $clangId = rex_clang::getCurrentId();
        }

        $backendContext = rex_context::fromGet();
        $backendContext->setParam('rex-api-call', 0);
        $backendContext->setParam('clang', $clangId);
        if ($backendContext->getParam('page') !== 'content/edit') {
            if ($backendContext->getParam('page') !== 'linkmap') {
                $backendContext->setParam('page', 'structure');
            }
        }
        $articleId = rex_request('article_id', 'int');
        $currentId = rex_request('category_id', 'int', $articleId);
        if ($article = rex_article::get($articleId)) {
            $currentId = $article->getCategoryId();
        }

        $categoriesArray = [];

        if ($includeHome) {
            $categoriesArray[] = [
                'id' => 0,
                'name' => rex_i18n::msg('root_level'),
                'current' => $currentId === 0,
                'domain' => 'default',
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
        } elseif ($parentCategory = rex_category::get($parentId, $clangId)) {
            $categories = $parentCategory->getChildren($ignoreOffline);
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
}
