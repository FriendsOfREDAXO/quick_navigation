<?php

namespace FriendsOfRedaxo\QuickNavigation\Media;

use Redaxo\Core\Database\Sql;
use Redaxo\Core\MediaManager\MediaManager;
use Redaxo\Core\MediaPool\Media;
use Redaxo\Core\MediaPool\MediaPoolPermission;
use Redaxo\Core\Security\User;

use function addcslashes;
use function array_fill;
use function count;
use function implode;
use function in_array;
use function rawurlencode;
use function strtolower;

/** Medien suchen bzw. die zuletzt bearbeiteten – nur aus Kategorien, die der Benutzer darf */
final class MediaFinder
{
    private const array IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    public static function allowed(User $user): bool
    {
        $perm = $user->getComplexPerm('media');

        return $user->admin || ($perm instanceof MediaPoolPermission && $perm->hasMediaPerm());
    }

    /**
     * @param list<string> $types Dateiendungen (leer = alle)
     * @return list<Media>
     */
    public static function find(User $user, string $term, array $types = [], int $limit = 12): array
    {
        if (!self::allowed($user)) {
            return [];
        }
        $perm = $user->getComplexPerm('media');

        $where = [];
        $params = [];
        if ('' !== $term) {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $where[] = '(m.filename LIKE ? OR m.originalname LIKE ? OR t.title LIKE ?)';
            $params = [$like, $like, $like];
        }
        if ($types) {
            $where[] = 'LOWER(SUBSTRING_INDEX(m.filename, ".", -1)) IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            foreach ($types as $type) {
                $params[] = strtolower($type);
            }
        }
        $rows = Sql::factory()->getArray('
            SELECT m.id FROM rex_media m
            LEFT JOIN rex_media_translation t ON t.media_id = m.id
            ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . '
            GROUP BY m.id
            ORDER BY m.updatedate DESC LIMIT 300', $params);

        $list = [];
        foreach ($rows as $row) {
            $media = Media::forId((int) $row['id']);
            if (!$media instanceof Media) {
                continue;
            }
            if (!$user->admin && (!$perm instanceof MediaPoolPermission || !$perm->hasCategoryPerm($media->categoryId ?: null))) {
                continue;
            }
            $list[] = $media;
            if (count($list) >= $limit) {
                break;
            }
        }

        return $list;
    }

    /** Vorschaubild für Bilder, sonst null */
    public static function thumbnail(Media $media): ?string
    {
        $ext = strtolower($media->getExtension());
        if (in_array($ext, self::IMAGES, true)) {
            return MediaManager::getUrl('rex_media_small', rawurlencode($media->fileName), $media->updateDate);
        }

        return 'svg' === $ext ? $media->getUrl() : null;
    }
}
