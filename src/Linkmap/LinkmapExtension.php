<?php

namespace FriendsOfRedaxo\QuickNavigation\Linkmap;

use Redaxo\Core\Backend\Controller;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Translation\I18n;

use function in_array;
use function json_encode;
use function Redaxo\Core\View\escape;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Linkmap: Suchfeld mit Live-Suche über der Kategorie-Navigation (Artikel nach Name oder ID, ohne Eingabe die zuletzt
 * bearbeiteten, im Auswahl-Modus mit „übernehmen“) – dieselbe Oberfläche wie im Medienpool (assets/media-search.js).
 */
final class LinkmapExtension
{
    public static function boot(): void
    {
        Extension::register('PAGE_TITLE_SHOWN', static function (ExtensionPoint $ep): string {
            if ('linkmap' !== Controller::getCurrentPage()) {
                return (string) $ep->subject;
            }

            return $ep->subject . self::search();
        });
    }

    private static function search(): string
    {
        $picker = Request::request('picker', 'string', '');
        $languageId = Request::request('language', 'int', 0);
        $config = [
            'url' => Url::backendController(LinkmapApi::getUrlParams()),
            'picker' => in_array($picker, ['single', 'multiple'], true) ? $picker : '',
            'language' => Language::exists($languageId) ? $languageId : Language::getStartId(),
            'languageParam' => 'qn_language',
            'i18n' => [
                'recent' => I18n::msg('quick_navigation_recent_edits'),
                'results' => I18n::msg('quick_navigation_results'),
                'empty' => I18n::msg('quick_navigation_empty'),
                'pick' => I18n::msg('quick_navigation_media_pick'),
            ],
        ];
        $label = I18n::msg('quick_navigation_linkmap_search');

        return '<search class="qn-linkmap-search"><div class="rex-input-group">'
            . '<span class="rex-input-group-text"><i class="rex-icon rex-icon-search" aria-hidden="true"></i></span>'
            . '<input class="rex-input" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false"'
            . ' placeholder="' . escape($label) . '" aria-label="' . escape($label) . '"'
            . ' data-qn-media="' . escape(json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) . '">'
            . '</div></search>';
    }
}
