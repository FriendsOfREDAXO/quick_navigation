<?php

namespace FriendsOfRedaxo\QuickNavigation;

use rex;
use rex_category;
use rex_addon;
use rex_context;
use rex_request;
use rex_yrewrite;

class StructureArray
{
    public function getArray($clangId = 1, $ignoreOffline = true, $parentId = null): array
    {
        $user = rex::getUser();
        $backendContext = rex_context::fromGet();
        $backendContext->setParam('rex-api-call', 0);
        $backendContext->setParam('page', rex_request('page', 'string'));
        $backendContext->setParam('clang', $clangId);

        $articleId = rex_request('article_id', 'int');
        $currentId = rex_request('category_id', 'int', $articleId);
        if ($article = rex_article::get($articleId)) {
            $currentId = rex_article::get($articleId)->getCategoryId();
        }

        $categoriesArray = [];
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
            $current = false;
            if ($categoryId == $currentId)
            {
                $current = true;
            }
            $categoriesArray[] = [
                'id' => $category->getId(),
                'name' => $categoryId,
                'current' => $current;
                'domain' => $domainName,
                'url' => $backendContext->getUrl(),
                'children' => $this->getArray($clangId, $ignoreOffline, $category->getId())
            ];
        }

        return $categoriesArray;
    }
}
