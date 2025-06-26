<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex;
use rex_addon;
use rex_plugin;
use rex_csrf_token;
use rex_fragment;
use rex_i18n;
use rex_string;
use rex_url;
use rex_yform_manager_table;

use function count;
use function rex_escape;

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
        $tables = rex_yform_manager_table::getAll();

        $yperm_suffix = '';
        if (version_compare($yform->getVersion(), '4.0.0-dev', '>=')) {
            $yperm_suffix = '_edit';
        }

        if (count($tables) < 1) {
            return '';
        }

        $listItems = [];
        foreach ($tables as $table) {
            $_csrf_key = 'table_field-' . $table->getTableName();
            $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();

            if (!$table->isHidden() && $table->isActive() && rex::getUser()->getComplexPerm('yform_manager_table' . $yperm_suffix)->hasPerm($table->getTableName())) {

                $attributes = [
                    'href' => rex_url::backendPage('yform/manager/data_edit', ['page' => 'yform/manager/data_edit', 'table_name' => $table->getTableName()]),
                    'title' => $table->getTableName(),
                ];


                $attributesAdd = [
                    'href' => rex_url::backendPage('yform/manager/data_edit', ['page' => 'yform/manager/data_edit', 'table_name' => $table->getTableName(), 'func' => 'add', '_csrf_token' => $_csrf_params['_csrf_token']]),
                    'title' => rex_i18n::msg('quick_navigation_yform_add') . ' ' . $table->getTableName(),
                ];

                $listItem = '
                    <div class="quick-navigation-item-row">
                        <a' . rex_string::buildAttributes($attributes) . '>
                            ' . rex_escape(rex_i18n::translate($table->getName())) . '
                        </a>
                        <a' . rex_string::buildAttributes($attributesAdd) . '>
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                    </div>
                ';

                $listItems[] = $listItem;
            }
        }

        if (count($listItems) < 1) {
            return '';
        }

        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('quick_navigation_yform'),
            'icon' => 'fa fa-database',
            'listItems' => $listItems,
        ]);
        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
}
