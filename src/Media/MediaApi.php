<?php

namespace FriendsOfRedaxo\QuickNavigation\Media;

use Override;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\ApiFunction\AsApiFunction;
use Redaxo\Core\ApiFunction\Result;
use Redaxo\Core\Core;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\MediaPool\MediaCategory;
use Redaxo\Core\Translation\I18n;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function in_array;
use function mb_substr;
use function trim;

/**
 * Live-Suche im Medienpool (nur lesend): ?rex-api-call=quick_navigation_media&q=…&types=jpg,png&picker=single.
 *
 * Ohne q die zuletzt bearbeiteten Medien. Mit picker liefert jeder Treffer den Dateinamen zum Übernehmen.
 */
#[AsApiFunction('quick_navigation_media')]
final class MediaApi extends ApiFunction
{
    #[Override]
    public function execute(): Result
    {
        $user = Core::requireUser();
        $term = trim(mb_substr(Request::get('q', 'string', ''), 0, 100));
        $types = array_values(array_filter(array_map('trim', explode(',', Request::get('types', 'string', '')))));
        $picker = Request::get('picker', 'string', '');
        $picker = in_array($picker, ['single', 'multiple'], true) ? $picker : '';

        // Parameter, mit denen der Medienpool im Auswahl-Modus bleibt
        $args = array_filter([
            'types' => $types ? implode(',', $types) : null,
            'picker' => $picker ?: null,
            'language' => Request::get('language', 'int', 0) ?: null,
        ]);

        $items = [];
        foreach (MediaFinder::find($user, $term, $types, 12) as $media) {
            $category = $media->categoryId ? MediaCategory::get($media->categoryId) : null;
            $items[] = [
                'title' => $media->title ?: $media->fileName,
                'file' => $media->fileName,
                'meta' => ($media->title ? $media->fileName . ' · ' : '') . ($category->name ?? I18n::msg('quick_navigation_media_root')) . ' · ' . $media->getFormattedSize(),
                'thumbnail' => MediaFinder::thumbnail($media),
                'url' => Url::backendPage('mediapool/media', ['file_id' => $media->id, 'rex_file_category' => $media->categoryId ?? 0] + $args),
                'pick' => '' !== $picker ? $media->fileName : null,
            ];
        }

        Response::cleanOutputBuffers();
        Response::setHeader('Cache-Control', 'no-store');
        Response::sendJson(['recent' => '' === $term, 'items' => $items]);
        exit;
    }
}
