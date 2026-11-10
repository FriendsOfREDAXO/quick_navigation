<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Domain\Domain;
use FriendsOfRedaxo\QuickNavigation\Domain\Domains;
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
use function array_values;
use function in_array;
use function mb_substr;
use function trim;

/**
 * Strukturbaum (nur lesend): ?rex-api-call=quick_navigation_tree&category_id=…&qn_language=….
 *
 *  - category_id  Ebene, die geladen wird (0 = oberste)
 *  - q            Suche: nur Treffer und ihre Kategorien, aufgeklappt
 *  - domain       Schlüssel der Domain (* = alle; ohne: die der Seite aus open)
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

        $result = ['items' => [], 'open' => null, 'domains' => [], 'domain' => null];
        if (StructureTree::allowed($user) && $ids) {
            $categoryId = Request::get('category_id', 'int', 0);
            $openId = Request::get('open', 'int', 0);

            // Domains: gewählte, sonst die der aktuellen Seite; „*“ = alle
            $domain = null;
            if (Domains::multiple()) {
                $result['domains'] = array_values(array_map(static fn (Domain $d) => $d->toArray(), Domains::all()));
                $key = Request::get('domain', 'string', '');
                if ('*' !== $key) {
                    $domain = Domains::get($key);
                    if (!$domain && $openId > 0 && $article = Article::get($openId, $languageId)) {
                        $domain = Domains::forPath([...$article->path, $article->id]);
                    }
                }
                $result['domain'] = null !== $domain ? $domain->key : '*';
            }
            $tree = new StructureTree($user, $languageId, $domain);

            // aktuelle Stelle aufklappen: Pfad der Kategorie (bzw. der Kategorie des Artikels)
            $open = [];
            if (0 === $categoryId && $openId > 0) {
                $article = Article::get($openId, $languageId);
                $category = $article?->startArticle ? Category::get($article->id, $languageId) : $article?->getCategory();
                if ($category) {
                    $open = [...$category->path, $category->id];
                }
                $result['open'] = $openId;
            }
            $term = mb_substr(Request::get('q', 'string', ''), 0, 100);
            if ('' !== trim($term)) {
                $result = [...$result, ...$tree->search($term)];
            } else {
                $result['items'] = $tree->level($categoryId, $open);
            }
        }
        $result['language'] = $languageId;

        Response::cleanOutputBuffers();
        Response::setHeader('Cache-Control', 'no-store');
        Response::sendJson($result);
        exit;
    }
}
