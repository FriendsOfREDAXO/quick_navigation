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

use function array_map;
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
        Asset::addJsFile($this->asset('media-search.js'), [Asset::JS_DEFERED => true]);
        Media\MediaPoolExtension::boot();

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
            'favoriteUrl' => Url::backendController(FavoriteApi::getUrlParams()),
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
                'favorite' => I18n::msg('quick_navigation_hint_favorite'),
                'favoriteAdd' => I18n::msg('quick_navigation_favorite_add'),
                'favoriteRemove' => I18n::msg('quick_navigation_favorite_remove'),
                'favoriteAdded' => I18n::msg('quick_navigation_favorite_added'),
                'favoriteRemoved' => I18n::msg('quick_navigation_favorite_removed'),
                'scopeAll' => I18n::msg('quick_navigation_scope_all'),
                'scopeLabel' => I18n::msg('quick_navigation_scope_label'),
                'results' => I18n::msg('quick_navigation_results'),
                'clear' => I18n::msg('quick_navigation_clear_recent'),
            ],
        ];

        // Strukturbaum – nur mit Rechten auf die Struktur
        $tree = '';
        if (StructureTree::allowed($user)) {
            $config['tree'] = [
                'url' => Url::backendController(TreeApi::getUrlParams()),
                'structure' => Url::backendPage('structure'),
                'languages' => array_map(
                    static fn ($l) => ['id' => $l->id, 'code' => $l->code, 'name' => $l->name],
                    StructureTree::languages($user),
                ),
            ];
            $config['i18n'] += [
                'tree' => I18n::msg('quick_navigation_tree'),
                'treeOpen' => I18n::msg('quick_navigation_tree_open'),
                'treeEmpty' => I18n::msg('quick_navigation_tree_empty'),
                'treeSearch' => I18n::msg('quick_navigation_tree_search'),
                'allDomains' => I18n::msg('quick_navigation_all_domains'),
                'domain' => I18n::msg('quick_navigation_domain'),
                'edit' => I18n::msg('quick_navigation_action_edit'),
                'view' => I18n::msg('quick_navigation_action_view'),
                'offline' => I18n::msg('quick_navigation_offline'),
            ];
            $tree = '<button type="button" class="qn-tree-trigger" aria-haspopup="tree" aria-expanded="false" aria-controls="qn-tree"'
                . ' title="' . escape(I18n::msg('quick_navigation_tree')) . '">'
                . '<i class="rex-icon fa-solid fa-sitemap" aria-hidden="true"></i>'
                . '<span class="rex-u-visually-hidden">' . escape(I18n::msg('quick_navigation_tree')) . '</span>'
                . '</button>';
        }

        // YForm: Tabellen nach Rechten, „+“ für neue Datensätze
        $yform = '';
        $tables = YForm\YFormTables::visible($user);
        if ($tables) {
            $config['yform'] = array_map(static fn ($table) => [
                'label' => $table->label(),
                'icon' => YForm\YFormTables::icon($table),
                'url' => YForm\YFormTables::url($table),
                'add' => YForm\YFormTables::canEdit($table, $user) ? YForm\YFormTables::url($table, ['func' => 'add']) : null,
            ], $tables);
            $config['i18n'] += [
                'yform' => I18n::msg('quick_navigation_yform'),
                'yformFilter' => I18n::msg('quick_navigation_yform_filter'),
                'yformAdd' => I18n::msg('quick_navigation_yform_add'),
                'yformSearch' => I18n::msg('quick_navigation_yform_search'),
            ];
            $yform = '<button type="button" class="qn-yform-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="qn-yform"'
                . ' title="' . escape(I18n::msg('quick_navigation_yform')) . '">'
                . '<i class="rex-icon fa-solid fa-table-list" aria-hidden="true"></i>'
                . '<span class="rex-u-visually-hidden">' . escape(I18n::msg('quick_navigation_yform')) . '</span>'
                . '</button>';
        }

        $label = I18n::msg('quick_navigation_label');

        return '<div class="qn-slot"><button type="button" class="qn-trigger" aria-haspopup="dialog" aria-keyshortcuts="Control+K Meta+K"'
            . ' title="' . escape($label) . '" data-quick-navigation="' . escape(json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) . '">'
            . '<i class="rex-icon rex-icon-search" aria-hidden="true"></i>'
            . '<span class="rex-u-visually-hidden">' . escape($label) . '</span>'
            . '</button>' . $tree . $yform . '</div>';
    }

    /** Adresse einer Datei aus assets/ mit Version gegen veraltete Caches */
    private function asset(string $file): string
    {
        $path = $this->getPath('assets/' . $file);

        return $this->getAssetsUrl($file) . '?v=' . (is_file($path) ? filemtime($path) : '0');
    }
}
