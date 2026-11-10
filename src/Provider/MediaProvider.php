<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\Action;
use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\MediaManager\MediaManager;
use Redaxo\Core\MediaPool\Media;
use Redaxo\Core\MediaPool\MediaCategory;
use Redaxo\Core\MediaPool\MediaPoolPermission;
use Redaxo\Core\Translation\I18n;

use function in_array;
use function rawurlencode;
use function strtolower;

/** Dateien im Medienpool – nach Dateiname oder Titel, ohne Eingabe die neuesten */
#[AsProvider('media', priority: 80, prefix: 'm')]
final class MediaProvider implements Provider
{
    private const array IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_media');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        $user = $query->user;
        $perm = $user->getComplexPerm('media');
        if (!$user->admin && (!$perm instanceof MediaPoolPermission || !$perm->hasMediaPerm())) {
            return;
        }
        // ohne Eingabe nur, wenn gezielt im Medienpool gesucht wird („m:“)
        if ($query->isEmpty() && !$query->scoped) {
            return;
        }

        $sql = Sql::factory();
        if ($query->isEmpty()) {
            $rows = $sql->getArray('SELECT id FROM rex_media ORDER BY updatedate DESC LIMIT 100');
        } else {
            $rows = $sql->getArray('
                SELECT m.id FROM rex_media m
                LEFT JOIN rex_media_translation t ON t.media_id = m.id
                WHERE m.filename LIKE ? OR m.originalname LIKE ? OR t.title LIKE ?
                GROUP BY m.id
                ORDER BY m.updatedate DESC LIMIT 200', [$query->like(), $query->like(), $query->like()]);
        }

        $count = 0;
        foreach ($rows as $row) {
            $media = Media::forId((int) $row['id']);
            if (!$media instanceof Media) {
                continue;
            }
            if (!$user->admin && !$perm->hasCategoryPerm($media->categoryId ?: null)) {
                continue;
            }
            $score = $query->score($media->title ?: $media->fileName, $media->fileName, $media->originalFileName);
            if (0 === $score) {
                continue;
            }
            $category = $media->categoryId ? MediaCategory::get($media->categoryId) : null;
            $image = in_array(strtolower($media->getExtension()), self::IMAGES, true);

            yield new Item(
                title: $media->title ?: $media->fileName,
                url: Url::backendPage('mediapool/detail', ['file_id' => $media->id, 'rex_file_category' => $media->categoryId ?? 0]),
                meta: ($media->title ? $media->fileName . ' · ' : '') . ($category->name ?? I18n::msg('quick_navigation_media_root')),
                icon: 'rex-icon rex-icon-media',
                badge: $media->getFormattedSize(),
                actions: [new Action(I18n::msg('quick_navigation_action_view'), $media->getUrl(), 'rex-icon rex-icon-external-link', true)],
                thumbnail: $image ? MediaManager::getUrl('rex_media_small', rawurlencode($media->fileName), $media->updateDate) : ('svg' === strtolower($media->getExtension()) ? $media->getUrl() : null),
                score: $score,
            );
            if (++$count >= ($query->isEmpty() ? $query->limit : 60)) {
                return;
            }
        }
    }
}
