<?php

namespace FriendsOfRedaxo\QuickNavigation\Media;

use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Language\Language;
use Redaxo\Core\MediaPool\Media;
use Redaxo\Core\MediaPool\MediaPoolPermission;
use Redaxo\Core\Translation\I18n;

use function array_column;
use function array_fill;
use function array_filter;
use function array_map;
use function array_search;
use function count;
use function explode;
use function implode;
use function Redaxo\Core\View\escape;
use function strtolower;
use function trim;

/**
 * Blättern in der Detailansicht eines Mediums: voriges/nächstes Medium in der Reihenfolge der Liste (Kategorie,
 * erlaubte Dateitypen, Sortierung). ← und → blättern ebenfalls (assets/media-search.js).
 */
final class MediaPager
{
    public static function render(int $fileId, string $orderBy): string
    {
        $media = Media::forId($fileId);
        if (!$media instanceof Media) {
            return '';
        }
        $user = Core::requireUser();
        $category = Request::request('rex_file_category', 'int', -1);
        $category = $category >= 0 ? $category : ($media->categoryId ?? 0);
        $perm = $user->getComplexPerm('media');
        if (!$user->admin && (!$perm instanceof MediaPoolPermission || !$perm->hasCategoryPerm($category ?: null))) {
            return '';
        }

        // dieselbe Auswahl wie die Liste
        $types = array_filter(array_map(static fn (string $t) => strtolower(trim($t)), explode(',', Request::request('types', 'string', ''))));
        $where = 'm.category_id <=> ?';
        $params = [$category ?: null];
        if ($types) {
            $where .= ' AND LOWER(SUBSTRING_INDEX(m.filename, ".", -1)) IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            foreach ($types as $type) {
                $params[] = $type;
            }
        }
        $rows = Sql::factory()->getArray('
            SELECT m.id FROM rex_media m
            LEFT JOIN rex_media_translation l ON l.media_id = m.id AND l.language_id = ?
            WHERE ' . $where . ' ORDER BY ' . $orderBy . ', m.id DESC', [Language::getCurrentId(), ...$params]);
        $ids = array_map('intval', array_column($rows, 'id'));

        $index = array_search($media->id, $ids, true);
        if (false === $index || count($ids) < 2) {
            return '';
        }

        $args = array_filter([
            'rex_file_category' => $category,
            'types' => Request::request('types', 'string', '') ?: null,
            'picker' => Request::request('picker', 'string', '') ?: null,
            'language' => Request::request('language', 'int', 0) ?: null,
        ], static fn ($v) => null !== $v);

        $link = static function (?int $id, string $rel) use ($args): string {
            $label = I18n::msg('prev' === $rel ? 'quick_navigation_media_prev' : 'quick_navigation_media_next');
            $icon = '<i class="rex-icon fa-solid ' . ('prev' === $rel ? 'fa-chevron-left' : 'fa-chevron-right') . '" aria-hidden="true"></i>';
            $neighbor = null !== $id ? Media::forId($id) : null;
            if (!$neighbor) {
                return '<span class="rex-button qn-media-pager-step" aria-disabled="true">' . $icon . '<span class="rex-u-visually-hidden">' . escape($label) . '</span></span>';
            }
            $name = $neighbor->title ?: $neighbor->fileName;
            $thumb = MediaFinder::thumbnail($neighbor);

            return '<a class="rex-button qn-media-pager-step" rel="' . $rel . '" data-qn-pager="' . $rel . '" href="'
                . escape(Url::currentBackendPage(['file_id' => $neighbor->id] + $args)) . '" aria-label="' . escape($label . ': ' . $name) . '">'
                . $icon
                . '<span class="qn-media-peek" aria-hidden="true">'
                . (null !== $thumb ? '<img src="' . escape($thumb) . '" alt="" loading="lazy">' : '<i class="rex-icon rex-icon-media"></i>')
                . '<span>' . escape($name) . '</span></span></a>';
        };

        return '<nav class="qn-media-pager" aria-label="' . escape(I18n::msg('quick_navigation_media_pager')) . '">'
            . $link($ids[$index - 1] ?? null, 'prev')
            . '<span class="qn-media-pager-pos">' . ($index + 1) . ' / ' . count($ids) . '</span>'
            . $link($ids[$index + 1] ?? null, 'next')
            . '</nav>';
    }
}
