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
            $category_id = $category->getId();
            $backendContext->setParam('category_id', $category_id);
            $backendContext->setParam('article_id', $category_id);
            $domainName = '';
            if (rex_addon::get('yrewrite')->isAvailable()) {
                $domainName = rex_escape(rex_yrewrite::getDomainByArticleId($category_id)->getName());
            }

            $categoriesArray[] = [
                'id' => $category->getId(),
                'name' => rex_escape($category->getName()),
                'domain' => $domainName,
                'url' => $backendContext->getUrl(),
                'children' => $this->generateBackendNavArray($clangId, $ignoreOffline, $category->getId())
            ];
        }

        return $categoriesArray;
    }
}
