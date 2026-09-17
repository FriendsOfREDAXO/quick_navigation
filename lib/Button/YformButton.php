<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use function count;

use FriendsOfRedaxo\QuickNavigation\Yform\Search;
use rex_addon;
use rex_csrf_token;

use function rex_escape;

use rex_i18n;
use rex_plugin;
use rex_request;
use rex_string;
use rex_url;
use rex_yform_manager_table;

class YformButton implements ButtonInterface
{
    public function get(): string
    {
        $yform = rex_addon::get('yform');
        if (version_compare($yform->getVersion(), '5.0.0-beta1', '<')) {
            if (!$yform->isAvailable() && !rex_plugin::get('yform', 'manager')->isAvailable()) {
                return '';
            }
        }
        if (version_compare($yform->getVersion(), '5.0.0-beta1', '>=')) {
            if (!$yform->isAvailable()) {
                return '';
            }
        }

        $tables = Search::searchableTables();

        if (count($tables) < 1) {
            return '';
        }

        $currentTableName = rex_request('table_name', 'string', '');

        $listItemsHtml = '';
        foreach ($tables as $table) {
            $listItemsHtml .= $this->buildTableListItem($table, $currentTableName === $table->getTableName());
        }

        $label = rex_i18n::msg('quick_navigation_yform');
        $placeholder = rex_i18n::msg('quick_navigation_yform_search_placeholder');

        $button = '
            <div class="btn-group">
                <button type="button" class="btn btn-default" id="quick-navigation-yform-trigger" title="' . rex_escape($label) . '" data-quick-navigation-toggle="tooltip">
                    ' . self::icon() . '
                    <span class="sr-only quick-navigation-button-label">' . rex_escape($label) . '</span>
                </button>
            </div>
        ';

        $tableSelectHtml = $this->buildTableSelect($tables, $currentTableName);

        $overlay = '
            <div id="quick-navigation-yform-overlay" class="qn-yform-overlay" hidden data-current-table="' . rex_escape($currentTableName) . '">
                <div class="qn-yform-overlay-backdrop" data-qn-yform-close></div>
                <div class="qn-yform-overlay-panel" role="dialog" aria-modal="true" aria-label="' . rex_escape($label) . '">
                    <div class="qn-yform-overlay-header">
                        <span class="qn-yform-overlay-icon">' . self::icon() . '</span>
                        <input type="text" id="quick-navigation-yform-input" class="qn-yform-overlay-input" placeholder="' . rex_escape($placeholder) . '" autocomplete="off">
                        ' . $tableSelectHtml . '
                        <button type="button" class="qn-yform-overlay-filters-toggle hidden" id="quick-navigation-yform-filters-toggle" title="' . rex_escape(rex_i18n::msg('quick_navigation_yform_filters')) . '">
                            <i class="fa fa-filter" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="qn-yform-overlay-close" data-qn-yform-close title="' . rex_escape(rex_i18n::msg('quick_navigation_yform_close')) . '">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="qn-yform-overlay-filters" id="quick-navigation-yform-filters" hidden></div>
                    <div class="qn-yform-overlay-body">
                        <div class="qn-yform-overlay-tables">' . $listItemsHtml . '</div>
                        <div class="qn-yform-overlay-results" hidden></div>
                    </div>
                </div>
            </div>
        ';

        return $button . $overlay;
    }

    /**
     * @param list<rex_yform_manager_table> $tables
     */
    private function buildTableSelect(array $tables, string $currentTableName): string
    {
        $options = '<option value="">' . rex_escape(rex_i18n::msg('quick_navigation_yform_all_tables')) . '</option>';

        foreach ($tables as $table) {
            $customIcon = method_exists($table, 'getCustomIcon') ? $table->getCustomIcon() : null;
            $iconClass = $customIcon ? trim($customIcon) : 'rex-icon fa-database';
            $selected = $currentTableName === $table->getTableName() ? ' selected' : '';

            $options .= '<option value="' . rex_escape($table->getTableName()) . '" data-icon="' . rex_escape($iconClass) . '"' . $selected . '>'
                . rex_escape(rex_i18n::translate($table->getName()))
                . '</option>';
        }

        return '<select id="quick-navigation-yform-table-select" class="qn-yform-overlay-table-select selectpicker show-tick" data-width="200px" data-live-search="true" data-size="10">' . $options . '</select>';
    }

    private function buildTableListItem(rex_yform_manager_table $table, bool $isCurrent): string
    {
        $_csrf_key = 'table_field-' . $table->getTableName();
        $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();

        $attributes = [
            'href' => rex_url::backendPage('yform/manager/data_edit', ['table_name' => $table->getTableName()]),
            'title' => $table->getTableName(),
            'class' => 'qn-yform-overlay-table-link' . ($isCurrent ? ' quick-navigation-current' : ''),
            'data-quick-navigation-yform-table' => $table->getTableName(),
        ];

        $attributesAdd = [
            'href' => rex_url::backendPage('yform/manager/data_edit', [
                'table_name' => $table->getTableName(),
                'func' => 'add',
                '_csrf_token' => $_csrf_params['_csrf_token'],
            ]),
            'title' => rex_i18n::msg('quick_navigation_yform_add') . ' ' . $table->getTableName(),
        ];

        $customIcon = method_exists($table, 'getCustomIcon') ? $table->getCustomIcon() : null;
        $iconClass = $customIcon ? trim($customIcon) : 'rex-icon fa-database';
        $icon = '<i class="' . rex_escape($iconClass) . ' qn-yform-overlay-table-icon" aria-hidden="true"></i>';

        return '
            <div class="qn-yform-overlay-item-row">
                <a' . rex_string::buildAttributes($attributes) . '>
                    ' . $icon . rex_escape(rex_i18n::translate($table->getName())) . '
                </a>
                <button type="button" class="qn-yform-overlay-table-scope" data-quick-navigation-yform-scope="' . rex_escape($table->getTableName()) . '" title="' . rex_escape(rex_i18n::msg('quick_navigation_yform_scope')) . '">
                    <i class="fa fa-search" aria-hidden="true"></i>
                </button>
                <a' . rex_string::buildAttributes($attributesAdd) . '>
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </a>
            </div>
        ';
    }

    /**
     * Custom icon for YForm dataset search: a database cylinder with a
     * magnifying glass, distinguishing this trigger from the plain
     * fa-database "browse tables" icon used elsewhere.
     */
    private static function icon(): string
    {
        return '<svg class="qn-yform-icon-svg" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M8 1.5C5 1.5 2.5 2.2 2.5 3.2V5.6C2.5 6.6 5 7.3 8 7.3C11 7.3 13.5 6.6 13.5 5.6V3.2C13.5 2.2 11 1.5 8 1.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M2.5 5.6V8.4C2.5 9.4 5 10.1 8 10.1C8.5 10.1 9 10.08 9.47 10.03" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M2.5 8.4V11.2C2.5 12.15 4.8 12.83 7.6 12.9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <circle cx="11.6" cy="11.6" r="2.6" stroke="currentColor" stroke-width="1.4"/>
            <path d="M13.5 13.5L15 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>';
    }
}
