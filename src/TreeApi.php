<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Override;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\ApiFunction\AsApiFunction;
use Redaxo\Core\ApiFunction\Result;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Core;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;

use function array_map;
use function in_array;

/**
 * Strukturbaum (nur lesend): ?rex-api-call=quick_navigation_tree&category_id=…&qn_language=….
 *
 *  - category_id  Ebene, die geladen wird (0 = oberste)
 *  - open         Artikel- oder Kategorie-ID, bis zu der der Baum aufgeklappt geliefert wird (nur mit category_id=0)
 */
#[AsApiFunction('quick_navigation_tree')]
final class TreeApi extends ApiFunction
{
    #[Override]
    public function execute(): Result
    {
        $user = Core::requireUser();
        $languages = StructureTree::languages($user);
        $ids = array_map(static fn ($l) => $l->id, $languages);
        $languageId = Request::get('qn_language', 'int', 0);
        $languageId = in_array($languageId, $ids, true) ? $languageId : ($ids[0] ?? 0);

        $result = ['items' => [], 'open' => null];
        if (StructureTree::allowed($user) && $ids) {
            $tree = new StructureTree($user, $languageId);
            $categoryId = Request::get('category_id', 'int', 0);

            // aktuelle Stelle aufklappen: Pfad der Kategorie (bzw. der Kategorie des Artikels)
            $open = [];
            $openId = Request::get('open', 'int', 0);
            if (0 === $categoryId && $openId > 0) {
                $article = Article::get($openId, $languageId);
                $category = $article?->startArticle ? Category::get($article->id, $languageId) : $article?->getCategory();
                if ($category) {
                    $open = [...$category->path, $category->id];
                }
                $result['open'] = $openId;
            }
            $result['items'] = $tree->level($categoryId, $open);
        }
        $result['language'] = $languageId;

        Response::cleanOutputBuffers();
        Response::setHeader('Cache-Control', 'no-store');
        Response::sendJson($result);
        exit;
    }
}
