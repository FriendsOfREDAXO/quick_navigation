<?php
/**
 * This file is part of the Quick Navigation package.
 *
 * @author (c) Friends Of REDAXO
 * @author <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$tables = $table_name = $table_real_name = $link = $table_id;

$tables = \rex_yform_manager_table::getAll();
$active_table = false;

if (count($tables)) {
    foreach ($tables as $table) {
        if (!$table->isHidden() && $table->isActive() && \rex::getUser()->getComplexPerm('yform_manager_table')->hasPerm($table->getTableName())) {
            $active_table = true;
            $table_name = rex_escape($table->getTableName());
            $table_real_name = rex_escape(rex_i18n::translate($table->getName()));
            $table_id = rex_escape($table->getId());
            $href = rex_url::backendPage(
                'yform/manager/data_edit',
                [
                            'page' => 'yform/manager/data_edit',
                            'table_name' => $table_name
                        ]
            );
            $addHref = rex_url::backendPage(
                'yform/manager/data_edit',
                [
                            'page' => 'yform/manager/data_edit',
                            'table_name' => $table_name,
                            'func' => 'add'
                        ]
            );
            $link .= '<li class="quicknavi_left"><a href="' . $href . '" title="' . $table_name . '">' . $table_real_name .'</a></li><li class="quicknavi_right"><a href="' . $addHref . '" title="'. $this->i18n("title_yform") .' '.  $table_name . '"><i class="fa fa-plus" aria-hidden="true"></i></a></li>';
        }
    }
    if ($active_table == true) {
?>
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-table" aria-hidden="true"></i>
                        <span class="caret"></span>
                    </button>
                    <ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
                        <?= $link ?>
                    </ul>
                </div>
<?php
                               }
}
