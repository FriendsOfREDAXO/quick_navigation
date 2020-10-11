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

$drophistory = $date = $name = $mode = $link = $where = $domaintitle = $status_css = '';

if (!isset($this->mode)) {
    $mode ='structure';
} else {
    $mode = $this->mode;
}


if ($mode =='minibar') {
    $icon_prefix ='rex-minibar-icon--fa rex-minibar-icon--';
} else {
    $icon_prefix ='fa ';
}

if (rex::getUser()->hasPerm('quick_navigation[history]')) {
    $were ='';
    if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
        $where = 'WHERE updateuser="'.rex::getUser()->getValue('login').'"';
    }

    $qry = 'SELECT id, status, parent_id, clang_id, startarticle, name, updateuser, updatedate
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
            $name = rex_escape($data['name']);
            $date = rex_formatter::strftime(strtotime($data['updatedate']), 'datetime');

            if ($mode =='linkmap') {
                $href = "javascript:insertLink('redaxo://".$dataID."','".$name." [".$dataID."]');";
            } else {
                $href = rex_url::backendPage(
                    'content/edit',
                    [
                    'mode' => 'edit',
                    'clang' => $data['clang_id'],
                    'article_id' => $data['id']
                ]
                );
            }

            if (rex_addon::get('yrewrite')->isAvailable()) {
                if (count(rex_yrewrite::getDomains())>2) {
                    $domain = rex_yrewrite::getDomainByArticleId($data['id']);
                    if ($domain) {
                        $domaintitle = '<br><i class="fa fa-globe" aria-hidden="true"></i> '.rex_escape($domain);
                    }
                }
            }
            $status_css = ' qn_status_'.$data['status'];
            $link .= '<li class=""><a class="quicknavi_left '. $status_css .'" href="' . $href . '" title="' . $name . '">' . $name . '<small>' . $langcode . '<i class="'. $icon_prefix.'fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . $domaintitle . '</small></a>';
            $link .= '<span class="quicknavi_right"><a class ="'. $status_css .'" href="'.rex_getUrl($dataID).'" title="'.  $name . ' '. $this->i18n("title_eye") .'" target="blank"><i class="'. $icon_prefix.'fa-eye" aria-hidden="true"></i></a></span></li>';
        }
    }

    if ($mode !='minibar') {
        $predecessor = '';
        $successor = '';
        $article_stack[] = array();
        // Objekt der aktuellen Kategorie laden
        $cat = rex_category::getCurrent();
        $current_id = rex_request('article_id');
        $page = rex_request('page');
           if ( $page != "structure"  && $cat && $current_id && $current_id != 0){
            // alle Artikel aus der aktuellen Kategorie laden
            $article = $cat->getArticles(true);
            if (is_array($article)) {
                // Artikelreihenfolge in eine Array schreiben
                foreach ($article as $var) {
                    // Startartikel werden ignoriert

                    $article_stack[] = $var->getId();
                }

                $i = 0;
                // Zahl der Artikel ermitteln
                $catcount = count($article_stack);
                foreach ($article_stack as $var) {
                    if ($var == $current_id) {
                        $successor = '

                        <button class="btn btn-default" disabled>
                           <span class="fa fa-chevron-right"> 
                        </button>
                    ';
                        if ($i+1 < $catcount) {
                            // ID des nachfolgenden Artikels ermitteln
                            $next_id = $article_stack[$i+1];

                            // Artikel-Objekt holen, um den Namen des vorhergehenden Artikels zu ermitteln,
                            // danach Link schreiben
                            $article = rex_article::get($next_id);
                
                            $href_next = rex_url::backendPage(
                                'content/edit',
                                [
                    'mode' => 'edit',
                    'clang' => $data['clang_id'],
                    'category_id' => rex_request('category_id'),
                    'article_id' => $next_id
                ]
                            );
                            $successor = '

                    <a class="btn btn-default" href="'.$href_next.'">
                      <span class="fa fa-chevron-right"> 
                    </a>
                ';
                        }

                        // und das Ganze nochmal für den vorhergehenden Artikel
                        if ($i-1 > -1) {
                            $prev_id = $article_stack[$i-1];
                
                            $href_prev = rex_url::backendPage(
                                'content/edit',
                                [
                    'mode' => 'edit',
                    'clang' => $data['clang_id'],
                    'category_id' => rex_request('category_id'),
                    'article_id' => $prev_id
                ]
                            );

                            if ($i < $catcount) {
                                $article = rex_article::get($prev_id);


                                $predecessor = '

                        <button class="btn btn-default" disabled>
                           <span class="fa fa-chevron-left"> 
                        </button>
                    ';

                                if ($article) {
                                    $predecessor = '

                        <a class="btn btn-default" href="'.$href_prev.'">
                           <span class="fa fa-chevron-left"> 
                        </a>
                    ';
                                }
                            }
                        }
                    }
                    $i++;
                }
            }
            $vz = '

    '.$predecessor.'

    '.$successor.'
';
        } ?>   
		<div class="btn-group"><?= $vz ?>
		<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
		<i class="fa fa-clock-o" aria-hidden="true"></i>
		<span class="caret"></span>
		</button>
		<ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
		<?= $link ?>
		</ul>
		</div>
		<?php
    } else { ?><ul class="minibar-quickfiles">
		<?= $link ?>
		</ul>
<?php }
}
