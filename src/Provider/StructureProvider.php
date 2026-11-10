<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\Action;
use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Content\StructurePermission;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Language\LanguagePermission;
use Redaxo\Core\Translation\I18n;

use function array_fill;
use function array_filter;
use function array_values;
use function count;
use function implode;
use function in_array;

/** Artikel und Kategorien – nach Name oder ID, ohne Eingabe die zuletzt bearbeiteten */
#[AsProvider('structure', priority: 90, prefix: 'a')]
final class StructureProvider implements Provider
{
    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg($query?->isEmpty() ? 'quick_navigation_recent_edits' : 'quick_navigation_group_structure');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        $user = $query->user;
        $perm = $user->getComplexPerm('structure');
        if (!$user->admin && (!$perm instanceof StructurePermission || !$perm->hasStructurePerm())) {
            return [];
        }
        $languagePerm = $user->getComplexPerm('language');
        $languages = array_values(array_filter(
            Language::getAllIds(),
            static fn (int $id) => $user->admin || ($languagePerm instanceof LanguagePermission && $languagePerm->hasPerm($id)),
        ));
        if (!$languages) {
            return [];
        }
        // Sprache der Seite, auf der die Palette geöffnet wurde, sonst die erste erlaubte
        $preferred = Request::get('qn_language', 'int', 0);
        $preferred = in_array($preferred, $languages, true) ? $preferred : $languages[0];

        $sql = Sql::factory();
        $in = implode(',', array_fill(0, count($languages), '?'));
        if ($query->isEmpty()) {
            // zuletzt bearbeitet – nur in der bevorzugten Sprache
            $rows = $sql->getArray('
                SELECT a.id, t.language_id FROM rex_article a
                JOIN rex_article_translation t ON t.article_id = a.id
                WHERE t.language_id = ?
                ORDER BY a.updatedate DESC LIMIT 40', [$preferred]);
        } elseif ($query->isId()) {
            $rows = $sql->getArray('
                SELECT a.id, t.language_id FROM rex_article a
                JOIN rex_article_translation t ON t.article_id = a.id
                WHERE a.id = ? AND t.language_id IN (' . $in . ')', [(int) $query->words[0], ...$languages]);
        } else {
            $rows = $sql->getArray('
                SELECT a.id, t.language_id FROM rex_article a
                JOIN rex_article_translation t ON t.article_id = a.id
                LEFT JOIN rex_category_translation c ON c.category_id = a.id AND c.language_id = t.language_id
                WHERE (t.name LIKE ? OR c.name LIKE ?) AND t.language_id IN (' . $in . ')
                ORDER BY a.updatedate DESC LIMIT 200', [$query->like(), $query->like(), ...$languages]);
        }

        // je Artikel ein Treffer: bevorzugte Sprache, sonst die erste, in der er passt
        $items = [];
        $multi = count($languages) > 1;
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $languageId = (int) $row['language_id'];
            if (isset($items[$id]) && $languageId !== $preferred) {
                continue;
            }
            $item = $this->item($id, $languageId, $query, $perm, $multi && $languageId !== $preferred);
            if (null !== $item) {
                $items[$id] = $item;
            }
            if ($query->isEmpty() && count($items) >= $query->limit) {
                break;
            }
        }

        return array_values($items);
    }

    private function item(int $id, int $languageId, Query $query, mixed $perm, bool $showLanguage): ?Item
    {
        $article = Article::get($id, $languageId);
        if (!$article instanceof Article) {
            return null;
        }
        $categoryId = $article->startArticle ? $article->id : $article->categoryId;
        if (!$query->user->admin && (!$perm instanceof StructurePermission || !$perm->hasCategoryPerm($categoryId ?: null))) {
            return null;
        }
        $category = $article->startArticle ? Category::get($article->id, $languageId) : null;
        $title = $category?->name ?: $article->name;

        $trail = [];
        foreach ($article->path as $parentId) {
            if ($parentId !== $article->id && $parent = Category::get($parentId, $languageId)) {
                $trail[] = $parent->name;
            }
        }
        $score = $query->isId() ? 1000 : $query->score($title, $article->name, implode(' ', $trail));
        if (0 === $score) {
            return null;
        }

        $actions = [];
        $actions[] = new Action(
            I18n::msg('quick_navigation_action_structure'),
            Url::backendPage('structure', ['category_id' => $categoryId ?? 0, 'language' => $languageId]),
            'rex-icon rex-icon-open-category',
        );
        $actions[] = new Action(
            I18n::msg('quick_navigation_action_view'),
            $article->getUrl(),
            'rex-icon rex-icon-external-link',
            true,
        );

        $badges = [];
        if ($showLanguage) {
            $badges[] = Language::get($languageId)?->code;
        }
        if (!$article->isOnline()) {
            $badges[] = I18n::msg('quick_navigation_offline');
        }

        return new Item(
            title: $title,
            url: Url::backendPage('content/edit', ['article_id' => $article->id, 'category_id' => $categoryId ?? 0, 'language' => $languageId]),
            meta: ($trail ? implode(' › ', $trail) : I18n::msg('quick_navigation_root')) . ' · ID ' . $article->id,
            icon: match (true) {
                $article->isSiteStartArticle() => 'rex-icon rex-icon-sitestartarticle',
                null !== $category => 'rex-icon rex-icon-category',
                default => 'rex-icon rex-icon-article',
            },
            badge: $badges ? implode(' · ', array_filter($badges)) : null,
            actions: $actions,
            score: $score,
        );
    }
}
