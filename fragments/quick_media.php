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

$drophistory = $filename = $entryname = $date = $link = $where = '';
if (rex::getUser()->hasPerm('quick_navigation[history]')) {
    $file_id = rex_request('file_id', 'int');
    $quick_file_nav = '';
    if ($file_id) {
        $quick_file = rex_sql::factory();
        $quick_file->setQuery('select * from ' . rex::getTablePrefix() . 'media where id=?', [$file_id]);
    
        $quick_file_before = rex_sql::factory();
        $quick_file_before->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE category_id = '. $quick_file->getValue('category_id') .' AND updatedate > ? ORDER BY updatedate LIMIT 1', [$quick_file->getValue('updatedate')]);

        $quick_file_after = rex_sql::factory();
        $quick_file_after->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE category_id = '. $quick_file->getValue('category_id') .' AND updatedate < ? ORDER BY updatedate DESC LIMIT 1', [$quick_file->getValue('updatedate')]);

        if ($quick_file_before->getRows() == 1 && $quick_file_after->getRows() == 1) {
            $quick_file_nav = '<a class="btn btn-default rex-form-aligned" href="'.rex_url::currentBackendPage(array_merge(['file_id' => $quick_file_before->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])).'"><span class="fa fa-chevron-left"></span></a> - <a class="btn btn-default rex-form-aligned" href="'.rex_url::currentBackendPage(array_merge(['file_id' => $quick_file_after->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])).'"><span class="fa fa-chevron-right"></span></a>';
        } elseif ($quick_file_before->getRows() == 1 && !$quick_file_after->getRows() == 1) {
            $quick_file_nav = '<a class="btn btn-default rex-form-aligned" href="'.rex_url::currentBackendPage(array_merge(['file_id' => $quick_file_before->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])).'"><span class="fa fa-chevron-left"></span></a>';
        } elseif (!$quick_file_before->getRows() == 1 && $quick_file_after->getRows() == 1) {
            $quick_file_nav = '<a class="btn btn-default rex-form-aligned" href="'.rex_url::currentBackendPage(array_merge(['file_id' => $quick_file_after->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])).'"><span class="fa fa-chevron-right"></span></a>';
        }
    }

    
    $were ='';
    if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
        $where = 'WHERE updateuser="'.rex::getUser()->getValue('login').'"';
    }
    $opener ='';
    $opener = rex_request('opener_input_field');

    $qry = 'SELECT category_id, id, title, filename, updateuser, updatedate FROM ' . rex::getTable('media') . ' '.$where.' ORDER BY updatedate DESC LIMIT ' . $this->limit;
    $datas = rex_sql::factory()->getArray($qry);

    if (!count($datas)) {
        $link .= '<li class="malert">'.rex_i18n::msg('quick_navigation_no_entries').'</li>';
    }


    if (count($datas)) {
        foreach ($datas as $data) {
            $entryname = '';

            $date = rex_formatter::strftime(strtotime($data['updatedate']), 'datetime');
            $href = rex_url::backendPage(
                'mediapool/media',
                [
                'opener_input_field'=> $opener,
                'rex_file_category' => $data['category_id'],
                'file_id' => $data['id']
                ]
            );

            if ($data['title']!='') {
                $entryname =   rex_escape($data['title']);
            } else {
                $entryname = rex_escape($data['filename']);
            }
            $filename = rex_escape($data['filename']);

            $link .= '<li><a href="' . $href . '" title="' . $filename . '">' . $entryname. '<small> <i class="fa fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . '</small></a></li>';
        }
    } ?>

                <div class="btn-group">
					<?php echo $quick_file_nav ?>
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-clock-o" aria-hidden="true"></i>
                        <span class="caret"></span>
                    </button>
                    <ul class="quickfiles quicknavi dropdown-menu">
                        <?php echo $link ?>
                    </ul>
                </div>
<?php
}
