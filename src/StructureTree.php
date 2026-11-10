<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Domain\Domain;
use FriendsOfRedaxo\QuickNavigation\Domain\Domains;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Content\StructurePermission;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Language\LanguagePermission;
use Redaxo\Core\Security\User;

use function addcslashes;
use function array_filter;
use function array_map;
use function array_values;
use function ctype_digit;
use function in_array;
use function trim;

/**
 * Strukturbaum für den Knopf neben der Suche: Kategorien und Artikel einer Ebene, nach den Rechten des Benutzers.
 *
 * Wer nicht die ganze Struktur darf, sieht auf oberster Ebene seine Mountpoints.
 */
final readonly class StructureTree
{
    /** @phpstan-ignore property.unusedType (für Benutzer ohne Struktur-Rechte vorgesehen) */
    private ?StructurePermission $perm;

    /** @var list<int> Einstiegskategorien anderer Domains (ausgeblendet) */
    private array $exclude;

    public function __construct(
        private User $user,
        private int $languageId,
        /** nur diese Domain (null = ganze Struktur) */
        private ?Domain $domain = null,
    ) {
        $perm = $user->getComplexPerm('structure');
        $this->perm = $perm instanceof StructurePermission ? $perm : null;
        $this->exclude = $domain ? Domains::otherMounts($domain) : [];
    }

    public static function allowed(User $user): bool
    {
        $perm = $user->getComplexPerm('structure');

        return $user->admin || ($perm instanceof StructurePermission && $perm->hasStructurePerm());
    }

    /**
     * Sprachen, die der Benutzer bearbeiten darf.
     *
     * @return list<Language>
     */
    public static function languages(User $user): array
    {
        $perm = $user->getComplexPerm('language');

        return array_values(array_filter(
            Language::getAll(),
            static fn (Language $l) => $user->admin || ($perm instanceof LanguagePermission && $perm->hasPerm($l->id)),
        ));
    }

    /**
     * Knoten einer Ebene (0 = oberste); entlang $open (IDs von Kategorien) gleich mit den Kindern.
     *
     * Mit $filter nur die Knoten aus $filter['show'] – Kategorien aus $filter['open'] aufgeklappt, Knoten aus
     * $filter['match'] markiert (Suche, {@see search()}).
     *
     * @param list<int> $open
     * @param array{show: array<int, true>, open: array<int, true>, match: array<int, true>}|null $filter
     * @return list<array<string, mixed>>
     */
    public function level(int $categoryId, array $open = [], ?array $filter = null): array
    {
        if (0 === $categoryId && $this->domain && 0 !== $this->domain->mountId) {
            // Domain mit eigener Einstiegskategorie: deren Inhalt ist die oberste Ebene
            [$categories, $articles] = $this->domainRoot($this->domain->mountId);
        } elseif (0 === $categoryId) {
            $categories = $this->rootCategories();
            $articles = $this->all() ? Article::getRootArticles(false, $this->languageId) : [];
        } else {
            $category = Category::get($categoryId, $this->languageId);
            if (!$category || !$this->may($category->id)) {
                return [];
            }
            $categories = $category->getChildren();
            $articles = $category->getArticles();
        }

        $nodes = [];
        foreach ($categories as $category) {
            if (in_array($category->id, $this->exclude, true)) {
                continue;
            }
            if (null !== $filter && !isset($filter['show'][$category->id])) {
                continue;
            }
            $node = $this->category($category);
            if (null !== $filter) {
                $node['match'] = isset($filter['match'][$category->id]);
                if (isset($filter['open'][$category->id])) {
                    $node['items'] = $this->level($category->id, [], $filter);
                    $node['expanded'] = true;
                }
            } elseif (in_array($category->id, $open, true) && $node['children']) {
                $node['items'] = $this->level($category->id, $open);
                $node['expanded'] = true;
            }
            $nodes[] = $node;
        }
        foreach ($articles as $article) {
            if ($article->startArticle || (null !== $filter && !isset($filter['show'][$article->id]))) {
                continue;
            }
            $node = $this->article($article);
            $node['match'] = null !== $filter;
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Baum nur mit den Treffern für $term (Name oder ID) und ihren übergeordneten Kategorien, aufgeklappt.
     *
     * @return array{items: list<array<string, mixed>>, count: int}
     */
    public function search(string $term, int $limit = 150): array
    {
        $term = trim($term);
        if ('' === $term) {
            return ['items' => [], 'count' => 0];
        }
        $like = '%' . addcslashes($term, '%_\\') . '%';
        $rows = Sql::factory()->getArray('
            SELECT a.id FROM rex_article a
            JOIN rex_article_translation t ON t.article_id = a.id AND t.language_id = ?
            LEFT JOIN rex_category_translation c ON c.category_id = a.id AND c.language_id = t.language_id
            WHERE t.name LIKE ? OR c.name LIKE ? OR a.id = ?
            ORDER BY a.priority LIMIT ' . $limit, [$this->languageId, $like, $like, ctype_digit($term) ? (int) $term : -1]);

        $filter = ['show' => [], 'open' => [], 'match' => []];
        $count = 0;
        foreach ($rows as $row) {
            $article = Article::get((int) $row['id'], $this->languageId);
            if (!$article) {
                continue;
            }
            $categoryId = $article->startArticle ? $article->id : $article->categoryId;
            if ($categoryId && !$this->may($categoryId)) {
                continue;
            }
            if (!$this->inDomain($article)) {
                continue;
            }
            if (!$categoryId && !$this->all()) {
                continue;
            }
            ++$count;
            $filter['show'][$article->id] = true;
            $filter['match'][$article->id] = true;
            foreach ($article->path as $parentId) {
                if ($parentId !== $article->id) {
                    $filter['show'][$parentId] = true;
                    $filter['open'][$parentId] = true;
                }
            }
        }

        return ['items' => $count ? $this->level(0, [], $filter) : [], 'count' => $count];
    }

    /**
     * Inhalt der Einstiegskategorie einer Domain – wer sie nicht darf, sieht seine Mountpoints darin.
     *
     * @return array{list<Category>, list<Article>}
     */
    private function domainRoot(int $mountId): array
    {
        $mount = Category::get($mountId, $this->languageId);
        if (!$mount) {
            return [[], []];
        }
        if ($this->may($mountId)) {
            return [$mount->getChildren(), $mount->getArticles()];
        }

        return [array_values(array_filter(
            $this->rootCategories(),
            static fn (Category $c) => in_array($mountId, $c->path, true),
        )), []];
    }

    /** Gehört die Seite zur gewählten Domain? */
    private function inDomain(Article $article): bool
    {
        if (!$this->domain) {
            return true;
        }
        $path = [...$article->path, $article->id];
        if (0 !== $this->domain->mountId && !in_array($this->domain->mountId, $path, true)) {
            return false;
        }
        foreach ($this->exclude as $mount) {
            if (in_array($mount, $path, true)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<Category> */
    private function rootCategories(): array
    {
        if ($this->all()) {
            return Category::getRootCategories(false, $this->languageId);
        }

        // Mountpoints in der gewählten Sprache
        return array_values(array_filter(array_map(
            fn (Category $c) => Category::get($c->id, $this->languageId),
            null !== $this->perm ? $this->perm->getMountpointCategories() : [],
        )));
    }

    /** @return array<string, mixed> */
    private function category(Category $category): array
    {
        $start = Article::get($category->id, $this->languageId);

        return [
            'id' => $category->id,
            'type' => 'category',
            'name' => $category->name,
            'online' => $category->isOnline(),
            'children' => [] !== $category->getChildren() || [] !== array_filter($category->getArticles(), static fn (Article $a) => !$a->startArticle),
            'url' => Url::backendPage('structure', ['category_id' => $category->id, 'language' => $this->languageId]),
            'edit' => $start ? $this->editUrl($start) : null,
            'view' => $start?->getUrl(),
        ];
    }

    /** @return array<string, mixed> */
    private function article(Article $article): array
    {
        return [
            'id' => $article->id,
            'type' => $article->isSiteStartArticle() ? 'home' : 'article',
            'name' => $article->name,
            'online' => $article->isOnline(),
            'children' => false,
            'url' => $this->editUrl($article),
            'edit' => null,
            'view' => $article->getUrl(),
        ];
    }

    private function editUrl(Article $article): string
    {
        return Url::backendPage('content/edit', [
            'article_id' => $article->id,
            'category_id' => $article->startArticle ? $article->id : ($article->categoryId ?? 0),
            'language' => $this->languageId,
        ]);
    }

    private function all(): bool
    {
        return $this->user->admin || (null !== $this->perm && $this->perm->hasCategoryPerm(null) && !$this->perm->hasMountpoints());
    }

    private function may(int $categoryId): bool
    {
        return $this->user->admin || (null !== $this->perm && $this->perm->hasCategoryPerm($categoryId));
    }
}
