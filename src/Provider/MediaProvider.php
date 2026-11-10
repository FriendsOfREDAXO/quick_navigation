<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\Action;
use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Media\MediaFinder;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\MediaPool\MediaCategory;
use Redaxo\Core\Translation\I18n;

/** Dateien im Medienpool – nach Dateiname oder Titel, ohne Eingabe die neuesten */
#[AsProvider('media', priority: 80, prefix: 'm')]
final class MediaProvider implements Provider
{
    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_media');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        // ohne Eingabe nur, wenn gezielt im Medienpool gesucht wird („m:“)
        if ($query->isEmpty() && !$query->scoped) {
            return;
        }
        $term = $query->isEmpty() ? '' : $query->words[0];
        foreach (MediaFinder::find($query->user, $term, [], $query->isEmpty() ? $query->limit : 60) as $media) {
            $score = $query->score($media->title ?: $media->fileName, $media->fileName, $media->originalFileName);
            if (0 === $score) {
                continue;
            }
            $category = $media->categoryId ? MediaCategory::get($media->categoryId) : null;

            yield new Item(
                title: $media->title ?: $media->fileName,
                url: Url::backendPage('mediapool/media', ['file_id' => $media->id, 'rex_file_category' => $media->categoryId ?? 0]),
                meta: ($media->title ? $media->fileName . ' · ' : '') . ($category->name ?? I18n::msg('quick_navigation_media_root')),
                icon: 'rex-icon rex-icon-media',
                badge: $media->getFormattedSize(),
                actions: [new Action(I18n::msg('quick_navigation_action_view'), $media->getUrl(), 'rex-icon rex-icon-external-link', true)],
                thumbnail: MediaFinder::thumbnail($media),
                score: $score,
            );
        }
    }
}
