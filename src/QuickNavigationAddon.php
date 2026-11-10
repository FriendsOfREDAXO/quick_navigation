<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Override;
use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Core;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Asset;

use function filemtime;
use function is_file;
use function json_encode;
use function Redaxo\Core\View\escape;
use function str_contains;
use function str_replace;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Quick Navigation: Befehlspalette im Backend – Suchknopf in der Kopfleiste, ⌘K/Strg+K überall.
 *
 * Quellen: {@see Provider}, angemeldet mit {@see AsProvider} oder {@see QuickNavigation::register()}.
 */
final class QuickNavigationAddon extends Addon
{
    /** Stelle in der Kopfleiste, vor der der Suchknopf steht */
    private const string ANCHOR = '<ul class="rex-topbar-meta">';

    #[Override]
    public function boot(): void
    {
        if (!Core::isBackend() || null === Core::getUser()) {
            return;
        }
        Asset::addCssFile($this->asset('quick-navigation.css'));
        Asset::addJsFile($this->asset('quick-navigation.js'), [Asset::JS_DEFERED => true]);

        Extension::register('OUTPUT_FILTER', function (ExtensionPoint $ep): string {
            $html = (string) $ep->subject;
            if (!str_contains($html, self::ANCHOR)) {
                return $html;
            }

            return str_replace(self::ANCHOR, $this->trigger() . self::ANCHOR, $html);
        });
    }

    /** Suchknopf; die Palette baut das Skript beim ersten Öffnen */
    private function trigger(): string
    {
        $user = Core::requireUser();
        $config = [
            'url' => Url::backendController(SearchApi::getUrlParams()),
            'storage' => 'quick-navigation:' . Core::getProject()->instanceName . ':' . $user->id,
            'prefixes' => QuickNavigation::prefixes(),
            'i18n' => [
                'label' => I18n::msg('quick_navigation_label'),
                'placeholder' => I18n::msg('quick_navigation_placeholder'),
                'recent' => I18n::msg('quick_navigation_recent'),
                'empty' => I18n::msg('quick_navigation_empty'),
                'loading' => I18n::msg('quick_navigation_loading'),
                'error' => I18n::msg('quick_navigation_error'),
                'open' => I18n::msg('quick_navigation_hint_open'),
                'newTab' => I18n::msg('quick_navigation_hint_new_tab'),
                'actions' => I18n::msg('quick_navigation_hint_actions'),
                'close' => I18n::msg('quick_navigation_hint_close'),
                'scope' => I18n::msg('quick_navigation_hint_scope'),
                'results' => I18n::msg('quick_navigation_results'),
                'clear' => I18n::msg('quick_navigation_clear_recent'),
            ],
        ];

        return '<div class="qn-slot"><button type="button" class="qn-trigger" aria-haspopup="dialog" aria-keyshortcuts="Control+K Meta+K"'
            . ' data-quick-navigation="' . escape(json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) . '">'
            . '<i class="rex-icon rex-icon-search" aria-hidden="true"></i>'
            . '<span class="qn-trigger-label">' . escape(I18n::msg('quick_navigation_trigger')) . '</span>'
            . '<kbd class="qn-trigger-kbd" aria-hidden="true">Strg K</kbd>'
            . '</button></div>';
    }

    /** Adresse einer Datei aus assets/ mit Version gegen veraltete Caches */
    private function asset(string $file): string
    {
        $path = $this->getPath('assets/' . $file);

        return $this->getAssetsUrl($file) . '?v=' . (is_file($path) ? filemtime($path) : '0');
    }
}
