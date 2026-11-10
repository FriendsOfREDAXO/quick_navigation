<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Content\StructurePermission;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Language\LanguagePermission;
use Redaxo\Core\Security\User;

use function array_filter;
use function array_map;
use function array_values;
use function in_array;

/**
 * Strukturbaum für den Knopf neben der Suche: Kategorien und Artikel einer Ebene, nach den Rechten des Benutzers.
 *
 * Wer nicht die ganze Struktur darf, sieht auf oberster Ebene seine Mountpoints.
 */
final readonly class StructureTree
{
    /** @phpstan-ignore property.unusedType (für Benutzer ohne Struktur-Rechte vorgesehen) */
    private ?StructurePermission $perm;

    public function __construct(
        private User $user,
        private int $languageId,
    ) {
        $perm = $user->getComplexPerm('structure');
        $this->perm = $perm instanceof StructurePermission ? $perm : null;
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
     * @param list<int> $open
     * @return list<array<string, mixed>>
     */
    public function level(int $categoryId, array $open = []): array
    {
        if (0 === $categoryId) {
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
            $node = $this->category($category);
            if (in_array($category->id, $open, true) && $node['children']) {
                $node['items'] = $this->level($category->id, $open);
                $node['expanded'] = true;
            }
            $nodes[] = $node;
        }
        foreach ($articles as $article) {
            if (!$article->startArticle) {
                $nodes[] = $this->article($article);
            }
        }

        return $nodes;
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
