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

$drophistory = $entryname = $date = $link = $where = '';
if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            
            if (rex::getUser()->hasPerm('quick_navigation[own_articles]') && !rex::getUser()->isAdmin()) {
        	
        	    $where = 'WHERE updateuser="'.rex::getUser()->getValue('login').'"';
        		
        	}
            $opener ='';
            $opener = rex_request('opener_input_field');
            
            $qry = 'SELECT category_id, id, title, filename, updateuser, updatedate FROM ' . rex::getTable('media') . ' ORDER BY updatedate DESC LIMIT ' . $this->limit;
            $datas = rex_sql::factory()->getArray($qry);

            if (!count($datas)) {
			   $link .= '<li class="alert">'.rex_i18n::msg('quick_navigation_no_entries').'</li>';
			}
            
            
            if (count($datas)) {

                foreach ($datas as $data) {
                   
                    $entryname = '';
                   
                   $date = rex_formatter::strftime(strtotime($data['updatedate']), 'datetime');
                   $attributes = [
                        'href' => rex_url::backendPage('mediapool/media',
                            [
                                'opener_input_field'=> $opener,
                                'rex_file_category' => $data['category_id'],
                                'file_id' => $data['id']
                            ]
                        )
                    ];
                    
                    if ($data['title']!='')
					{ $entryname =   $data['title']; }  
					else {
						 $entryname = $data['filename'];
					}              
                    
                    $link .= '<li><a ' . rex_string::buildAttributes($attributes) . ' title="' . $data['filename'] . '">' . $entryname. '<small> <i class="fa fa-user" aria-hidden="true"></i> ' . $data['updateuser'] . ' - ' . $date . '</small></a></li>';
                }
            }
?>
            
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-clock-o" aria-hidden="true"></i>
                        <span class="caret"></span>
                    </button>
                    <ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
                        <?= $link ?>
                    </ul>
                </div>
<?php                 
        }
        
 ?>
