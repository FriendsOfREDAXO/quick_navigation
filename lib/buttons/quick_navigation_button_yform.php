<?php

namespace FriendsOfRedaxo\QuickNavigation\Buttons;

use rex;
use rex_addon;
use rex_plugin;
use rex_csrf_token;
use rex_fragment;
use rex_i18n;
use rex_url;
use rex_yform_manager_table;

use function count;
use function rex_escape;

class YformButton implements ButtonInterface
{
    public function get(): string
    {
        if (!rex_addon::get('yform')->isAvailable() || !rex_plugin::get('yform', 'manager')->isAvailable()) {
            return '';
        }
        $table_name = '';
        $table_real_name = '';
        $href = '';
        $addHref = '';
        $tables = rex_yform_manager_table::getAll();
        $active_table = false;
        $yform = rex_addon::get('yform');
        $yperm_suffix = '';
        if (version_compare($yform->getVersion(), '4.0.0-dev', '>=')) {
            $yperm_suffix = '_edit';
        }

        if (count($tables) > 0) {
            $ytables = [];
            foreach ($tables as $table) {
                $_csrf_key = 'table_field-' . $table->getTableName();
                $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();

                if (!$table->isHidden() && $table->isActive() && rex::getUser()->getComplexPerm('yform_manager_table' . $yperm_suffix)->hasPerm($table->getTableName())) {
                    $active_table = true;
                    $table_name = rex_escape($table->getTableName());
                    $table_real_name = rex_escape(rex_i18n::translate($table->getName()));
                    $href = rex_url::backendPage(
                        'yform/manager/data_edit',
                        [
                            'page' => 'yform/manager/data_edit',
                            'table_name' => $table_name,
                        ]
                    );
                    $addHref = rex_url::backendPage(
                        'yform/manager/data_edit',
                        [
                            'page' => 'yform/manager/data_edit',
                            'table_name' => $table_name,
                            'func' => 'add',
                            '_csrf_token' => $_csrf_params['_csrf_token'],
                        ]
                    );
                    $ytables[] = '<li class="quicknavi_left"><a href="' . $href . '" title="' . $table_name . '">' . $table_real_name . '</a></li><li class="quicknavi_right"><a href="' . $addHref  . '" title="' . rex_i18n::msg('title_yform') . ' ' .  $table_name . '"><i class="fa fa-plus" aria-hidden="true"></i></a></li>';
                }
            }

            if ($active_table == true) {
                $fragment = new rex_fragment();
                $fragment->setVar('items', $ytables, false);
                $fragment->setVar('icon', 'fa fa-database');
                return $fragment->parse('quick_button.php');
            }
        }

        return '';
    }
}
