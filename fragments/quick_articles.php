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

$drophistory = $date = $link = $where = $domaintitle ='';
if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            if (rex::getUser()->hasPerm('quick_navigation[own_articles]') && !rex::getUser()->isAdmin()) {
        	
        	    $where = 'WHERE updateuser="'.rex::getUser()->getValue('login').'"';
        		
        	}
            
            $qry = 'SELECT id, parent_id, clang_id, startarticle, name, updateuser, updatedate
                    FROM ' . rex::getTable('article') . ' 
                    '. $where .' 
                    ORDER BY updatedate DESC
                    LIMIT '.$this->limit;
            $datas = rex_sql::factory()->getArray($qry);

            if (!count($datas)) {
			   $link .= '<li class="alert">'.rex_i18n::msg('quick_navigation_no_entries').'</li>';
			}

            if (count($datas)) {
                foreach ($datas as $data) {
                	$dataID = rex_escape($data['id']);
                    $lang = rex_clang::get($data['clang_id']);
                    $langcode = $lang->getCode();
                    if ($langcode) {
                        $langcode = '<i class="fa fa-flag-o" aria-hidden="true"></i> ' . $langcode . ' - ';
                    }
                   $date = rex_formatter::strftime(strtotime($data['updatedate']), 'datetime');
                   $attributes = [
                        'href' => rex_url::backendPage('content/edit',
                            [
                                'mode' => 'edit',
                                'clang' => $data['clang_id'],
                                'article_id' => $data['id']
                            ]
                        )
                    ];

					
			if(rex_addon::get('yrewrite')->isAvailable()) {
				    if (rex_yrewrite::getDomainByArticleId($data['id'])!="" and count(rex_yrewrite::getDomains())>2)
					{ $domain = rex_yrewrite::getDomainByArticleId($data['id']); 
					  $domaintitle = '<br><i class="fa fa-globe" aria-hidden="true"></i> '.rex_escape($domain); 
					}

				}
					$name = rex_escape($data['name']);
                    $link .= '<li class=""><a class="quicknavi_left" ' . rex_string::buildAttributes($attributes) . ' title="' . $name . '">' . $name . '<small>' . $langcode . '<i class="fa fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . $domaintitle . '</small></a><span class="quicknavi_right"><a href="'.rex_getUrl($dataID).'" title="'.  $name . ' '. $this->i18n("title_eye") .'" target="blank"><i class="fa fa-eye" aria-hidden="true"></i></a></span></li>';
                	
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
