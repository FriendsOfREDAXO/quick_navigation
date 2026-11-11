<?php

namespace FriendsOfRedaxo\QuickNavigation\Linkmap;

use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\ApiFunction\AsApiFunction;
use Redaxo\Core\ApiFunction\Result;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Content\StructurePermission;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Language\LanguagePermission;
use Redaxo\Core\Translation\I18n;

use function array_slice;
use function implode;
use function in_array;
use function mb_substr;
use function sprintf;
use function trim;
use function usort;

/**
 * Live-Suche in der Linkmap (nur lesend): ?rex-api-call=quick_navigation_linkmap&q=…&qn_language=…&picker=single.
 *
 * Artikel und Kategorien nach Name oder ID, ohne q die zuletzt bearbeiteten – nach Kategorie- und Sprach-Rechten.
 * Mit picker liefert jeder Treffer die Artikel-ID zum Übernehmen.
 */
#[AsApiFunction('quick_navigation_linkmap')]
final class LinkmapApi extends ApiFunction
{
    private const int LIMIT = 15;

    #[Override]
    public function execute(): Result
    {
        $user = Core::requireUser();
        $term = trim(mb_substr(Request::get('q', 'string', ''), 0, 100));
        $picker = Request::get('picker', 'string', '');
        $picker = in_array($picker, ['single', 'multiple'], true) ? $picker : '';
        $languageId = Request::get('qn_language', 'int', 0);
        $languageId = Language::exists($languageId) ? $languageId : Language::getStartId();

        $items = [];
        $perm = $user->getComplexPerm('structure');
        $languagePerm = $user->getComplexPerm('language');
        $allowed = $user->admin || ($perm instanceof StructurePermission && $perm->hasStructurePerm()
            && $languagePerm instanceof LanguagePermission && $languagePerm->hasPerm($languageId));

        if ($allowed) {
            $query = new Query($term, $user, self::LIMIT);
            $sql = Sql::factory();
            $rows = $query->isEmpty()
                ? $sql->getArray('SELECT a.id FROM rex_article a JOIN rex_article_translation t ON t.article_id = a.id AND t.language_id = ? ORDER BY a.updatedate DESC LIMIT 60', [$languageId])
                : $sql->getArray('
                    SELECT a.id FROM rex_article a
                    JOIN rex_article_translation t ON t.article_id = a.id AND t.language_id = ?
                    LEFT JOIN rex_category_translation c ON c.category_id = a.id AND c.language_id = t.language_id
                    WHERE t.name LIKE ? OR c.name LIKE ? OR a.id = ?
                    LIMIT 200', [$languageId, $query->like(), $query->like(), $query->isId() ? (int) $query->words[0] : -1]);

            $found = [];
            foreach ($rows as $row) {
                $article = Article::get((int) $row['id'], $languageId);
                if (!$article) {
                    continue;
                }
                $categoryId = $article->startArticle ? $article->id : ($article->categoryId ?? 0);
                if (!$user->admin && (!$perm instanceof StructurePermission || !$perm->hasCategoryPerm($categoryId ?: null))) {
                    continue;
                }
                $trail = [];
                foreach ($article->path as $parentId) {
                    if ($parentId !== $article->id && $parent = Category::get($parentId, $languageId)) {
                        $trail[] = $parent->name;
                    }
                }
                $score = $query->isId() && (int) $query->words[0] === $article->id ? 10_000 : $query->score($article->name, implode(' ', $trail));
                if (0 === $score) {
                    continue;
                }
                $found[] = [$score, $article, $trail, $categoryId];
            }
            if (!$query->isEmpty()) {
                usort($found, static fn (array $a, array $b) => $b[0] <=> $a[0]);
            }

            foreach (array_slice($found, 0, self::LIMIT) as [, $article, $trail, $categoryId]) {
                $items[] = [
                    'title' => $article->name,
                    'meta' => ($trail ? implode(' › ', $trail) : I18n::msg('quick_navigation_root')) . ' · ID ' . $article->id
                        . ($article->isOnline() ? '' : ' · ' . I18n::msg('quick_navigation_offline')),
                    'icon' => 'rex-icon rex-icon-' . match (true) {
                        $article->isSiteStartArticle() => 'sitestartarticle',
                        $article->startArticle => 'startarticle',
                        default => 'article',
                    },
                    // Klick auf die Zeile: in der Linkmap zur Kategorie des Artikels
                    'url' => Url::backendPage('linkmap', ['category_id' => $categoryId, 'language' => $languageId] + ('' !== $picker ? ['picker' => $picker] : [])),
                    'pick' => '' !== $picker ? (string) $article->id : null,
                    'pickLabel' => sprintf('%s [%s]', $article->name, $article->id),
                ];
            }
        }

        Response::cleanOutputBuffers();
        Response::setHeader('Cache-Control', 'no-store');
        Response::sendJson(['recent' => '' === $term, 'items' => $items]);
        exit;
    }
}
