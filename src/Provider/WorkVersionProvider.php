<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\Action;
use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\ArticleRevision;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Content\StructurePermission;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Language\LanguagePermission;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\Util\Formatter;

use function count;
use function implode;
use function strtotime;
use function usort;

/**
 * Seiten mit unveröffentlichter Arbeitsversion – deren Inhalt (Blöcke, Reihenfolge, Werte, Status) vom Live-Stand
 * abweicht. Nur mit aktivierter Arbeitsversion (System → Einstellungen); Vergleich per Prüfsumme je Fassung in einer
 * Abfrage für alle Seiten.
 */
#[AsProvider('workversion', priority: 92, prefix: 'w')]
final class WorkVersionProvider implements Provider
{
    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_workversion');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        if (!Core::getConfig('article_work_version', false)) {
            return [];
        }
        $user = $query->user;
        $perm = $user->getComplexPerm('structure');
        if (!$user->admin && (!$perm instanceof StructurePermission || !$perm->hasStructurePerm())) {
            return [];
        }
        $languageId = Request::get('qn_language', 'int', 0) ?: Language::getStartId();
        $languagePerm = $user->getComplexPerm('language');
        if (!$user->admin && (!$languagePerm instanceof LanguagePermission || !$languagePerm->hasPerm($languageId))) {
            return [];
        }

        $list = [];
        foreach (self::changed($languageId) as $row) {
            $article = Article::get($row['id'], $languageId);
            if (!$article) {
                continue;
            }
            $categoryId = $article->startArticle ? $article->id : $article->categoryId;
            if (!$user->admin && !$perm->hasCategoryPerm($categoryId ?: null)) {
                continue;
            }
            $trail = [];
            foreach ($article->path as $parentId) {
                if ($parentId !== $article->id && $parent = Category::get($parentId, $languageId)) {
                    $trail[] = $parent->name;
                }
            }
            $score = $query->score($article->name, implode(' ', $trail), $row['user']);
            if (0 === $score) {
                continue;
            }
            $changed = strtotime($row['changed']) ?: null;
            $list[] = new Item(
                title: $article->name,
                url: Url::backendPage('content/edit', ['article_id' => $article->id, 'category_id' => $categoryId ?? 0, 'language' => $languageId]),
                meta: implode(' · ', [
                    $trail ? implode(' › ', $trail) : I18n::msg('quick_navigation_root'),
                    I18n::msg('quick_navigation_workversion_changed', null !== $changed ? Formatter::intlDateTime($changed) : '–', $row['user'] ?: '–'),
                ]),
                icon: 'rex-icon rex-icon-unstable-version',
                badge: $row['new'] ? I18n::msg('quick_navigation_workversion_new') : null,
                actions: [new Action(I18n::msg('quick_navigation_action_view'), $article->getUrl(), 'rex-icon rex-icon-external-link', true)],
                score: $score,
            );
            if (!$query->scoped && $query->isEmpty() && count($list) >= $query->limit) {
                break;
            }
        }

        return $list;
    }

    /**
     * Seiten, deren Arbeitsversion vom Live-Stand abweicht, zuletzt geänderte zuerst.
     *
     * @return list<array{id: int, changed: string, user: string, new: bool}>
     */
    private static function changed(int $languageId): array
    {
        $sql = Sql::factory();
        // Prüfsumme über den ganzen Inhalt einer Fassung
        $sql->setQuery('SET SESSION group_concat_max_len = 16777216');
        $rows = $sql->getArray('
            SELECT article_id, revision,
                MD5(GROUP_CONCAT(CONCAT_WS("|", module, priority, status, content_section_id, data) ORDER BY content_section_id, priority SEPARATOR "#")) AS sig,
                MAX(updatedate) AS changed,
                SUBSTRING_INDEX(GROUP_CONCAT(updateuser ORDER BY updatedate DESC), ",", 1) AS user
            FROM rex_article_slice WHERE language_id = ? AND revision IN (?, ?)
            GROUP BY article_id, revision', [$languageId, ArticleRevision::LIVE, ArticleRevision::WORK]);

        $by = [];
        foreach ($rows as $row) {
            $by[(int) $row['article_id']][(int) $row['revision']] = $row;
        }
        $list = [];
        foreach ($by as $id => $revisions) {
            $work = $revisions[ArticleRevision::WORK] ?? null;
            $live = $revisions[ArticleRevision::LIVE] ?? null;
            if (null === $work || (null !== $live && $live['sig'] === $work['sig'])) {
                continue;
            }
            $list[] = ['id' => $id, 'changed' => (string) $work['changed'], 'user' => (string) $work['user'], 'new' => null === $live];
        }
        usort($list, static fn (array $a, array $b) => $b['changed'] <=> $a['changed']);

        return $list;
    }
}
