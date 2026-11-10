<?php

namespace FriendsOfRedaxo\QuickNavigation\Media;

use Redaxo\Core\Core;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;
use Redaxo\Core\MediaPool\ExtensionPoint\MediaListQuery;
use Redaxo\Core\MediaPool\ExtensionPoint\MediaListToolbar;
use Redaxo\Core\Translation\I18n;

use function array_filter;
use function in_array;
use function json_encode;
use function Redaxo\Core\View\escape;
use function str_contains;
use function str_replace;
use function time;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Medienpool: Live-Suche im Suchfeld (ohne Eingabe die zuletzt bearbeiteten Medien, im Auswahl-Modus mit
 * „übernehmen“) und ein Umschalter für die Sortierung der Liste (neueste, Name, Titel).
 */
final class MediaPoolExtension
{
    public const string SORT = 'qn_media_sort';

    /** Sortierung → nächste beim Klick */
    private const array NEXT = ['date' => 'name', 'name' => 'title', 'title' => 'date'];

    private const array ORDER = [
        'date' => 'm.updatedate DESC',
        'name' => 'm.filename ASC',
        'title' => 'l.title IS NULL, l.title = "", l.title ASC, m.filename ASC',
    ];

    public static function boot(): void
    {
        if (!MediaFinder::allowed(Core::requireUser())) {
            return;
        }

        // Sortierung per Link wählen, im Cookie merken
        $chosen = Request::get(self::SORT, 'string', '');
        if (isset(self::NEXT[$chosen])) {
            Response::sendCookie(self::SORT, $chosen, ['expires' => time() + 31_536_000, 'samesite' => 'lax']);
            $_COOKIE[self::SORT] = $chosen;
        }

        Extension::register(MediaListToolbar::class, static fn (MediaListToolbar $ep): string => self::toolbar((string) $ep->subject));
        Extension::register(MediaListQuery::class, static function (MediaListQuery $ep): string {
            $query = (string) $ep->subject;
            if ($ep->count) {
                return $query;
            }

            // mit eindeutiger Folgesortierung, damit das Blättern (MediaPager) dieselbe Reihenfolge hat
            return str_replace(' ORDER BY m.updatedate DESC', ' ORDER BY ' . self::ORDER[self::sort()] . ', m.id DESC', $query);
        });
    }

    private static function sort(): string
    {
        $sort = Request::cookie(self::SORT, 'string', 'date');

        return isset(self::NEXT[$sort]) ? $sort : 'date';
    }

    /** Suchfeld mit Live-Suche ausstatten, Sortier-Knopf vor den Such-Knopf */
    private static function toolbar(string $toolbar): string
    {
        $anchor = 'id="be_search-media-name"';
        if (!str_contains($toolbar, $anchor)) {
            return $toolbar;
        }

        $picker = Request::request('picker', 'string', '');
        $config = [
            'url' => Url::backendController(MediaApi::getUrlParams()),
            'types' => Request::request('types', 'string', ''),
            'picker' => in_array($picker, ['single', 'multiple'], true) ? $picker : '',
            'language' => Request::request('language', 'int', 0),
            'i18n' => [
                'recent' => I18n::msg('quick_navigation_media_recent'),
                'results' => I18n::msg('quick_navigation_results'),
                'empty' => I18n::msg('quick_navigation_empty'),
                'pick' => I18n::msg('quick_navigation_media_pick'),
            ],
        ];
        $toolbar = str_replace(
            $anchor,
            $anchor . ' autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" data-qn-media="'
                . escape(json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) . '"',
            $toolbar,
        );

        // Detailansicht: blättern statt sortieren
        $fileId = Request::request('file_id', 'int', 0);
        if ($fileId > 0) {
            $pager = MediaPager::render($fileId, self::ORDER[self::sort()]);

            return '' === $pager ? $toolbar : str_replace('<div class="rex-field">', '<div class="rex-field qn-media-tools">' . $pager, $toolbar);
        }

        // Sortieren: Link auf dieselbe Liste mit der nächsten Sortierung
        $sort = self::sort();
        $next = self::NEXT[$sort];
        $url = Url::currentBackendPage(array_filter([
            'rex_file_category' => Request::request('rex_file_category', 'int', 0) ?: null,
            'media_name' => Request::request('media_name', 'string', '') ?: null,
            'types' => $config['types'] ?: null,
            'picker' => $config['picker'] ?: null,
            'language' => $config['language'] ?: null,
            self::SORT => $next,
        ]));
        $icon = ['date' => 'fa-arrow-down-wide-short', 'name' => 'fa-arrow-down-a-z', 'title' => 'fa-heading'][$sort];
        $label = I18n::msg('quick_navigation_media_sort_' . $sort) . ' – ' . I18n::msg('quick_navigation_media_sort_next', I18n::msg('quick_navigation_media_sort_' . $next));
        $button = '<a class="rex-button qn-media-sort" href="' . escape($url) . '" title="' . escape($label) . '">'
            . '<i class="rex-icon fa-solid ' . $icon . '" aria-hidden="true"></i><span class="rex-u-visually-hidden">' . escape($label) . '</span></a>';

        return str_replace('<span class="rex-input-group-actions">', '<span class="rex-input-group-actions">' . $button, $toolbar);
    }
}
