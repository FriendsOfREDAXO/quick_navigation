<?php

namespace FriendsOfRedaxo\QuickNavigation;

use rex;
use rex_addon;
use rex_category;
use rex_clang; 
use rex_context;
use rex_request;
use rex_url;
use rex_yrewrite;

class StructureArray
{
    public function getArray($ignoreOffline = true, $includeHome = true, $clangId = null, $parentId = null): array
    {
        if ($clangId === null) {
            $clangId = rex_clang::getCurrentId(); 
        }

        $user = rex::getUser();
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
                'name' => 'Home',
                'current' => $currentId === 0,
                'domain' => '',
                'url' => rex_url::backendPage('structure', ['clang' => $clangId]),
                'children' => []
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
                $categories = $parentCategory->getChildren($ignoreOffline, $clangId);
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
                'children' => $this->getArray($ignoreOffline, false, $clangId, $category->getId())
            ];
        }

        return $categoriesArray;
    }
}
