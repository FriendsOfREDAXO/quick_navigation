<?php

use Watson\Foundation\Watson;

/**
 * This file is part of the Quick Navigation package.
 *
 * @author (c) Friends Of REDAXO
 * @author     <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class QuickNavigation
{
    // Media History
    /**
     * @param rex_extension_point<string> $ep
     */
    public static function media_history(rex_extension_point $ep): ?string
    {
        // get media history
        if (rex_be_controller::getCurrentPagePart(1) == 'mediapool') {
            $subject = $ep->getSubject();
            $drophistory = self::get_media();
            $custom_media_buttons = $custom_media = '';
            $custom_media_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM_MEDIA', $custom_media));
            $button = $custom_media_buttons . '<div class="input-group-btn quickmedia clearfix">' . $drophistory . '</div><select name="rex_file_category"';
            $output = str_replace('<select name="rex_file_category"', $button, $subject);
            return $output;
        }
        return null;
    }

    // linkmap catlist
    /**
     * @param rex_extension_point<string> $ep
     */
    public static function linkmap_list(rex_extension_point $ep): ?string
    {
        // get article history
        if (rex_be_controller::getCurrentPagePart(1) == 'linkmap') {
            $droplist = $favs = $drophistory = $qlang = '';
            // Check if language is set
            $qlang = rex_request('clang', 'int');
            if ($qlang == 0 || $qlang == '') {
                $qlang = 1;
            }
            $droplist = self::get_cats('linkmap');
            $favs = self::get_favs('linkmap');
            $drophistory = self::get_article_history('linkmap');

            // generate output ep for custom buttons after default set.
            $custom_linkmap_buttons = $custom = '';
            $custom_linkmap_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_LINKMAP_CUSTOM', $custom));
            return '<div class="btn-group quicknavi-btn-group linkmapbt pull-right">' . $droplist . $drophistory . $favs . $custom_linkmap_buttons . '</div>' . $ep->getSubject();
        }
        return null;
    }


    public static function get_cats(string $mode = 'structure'): string
    {

        // Generate category Quick Navi
        // ------------ Parameter
        $qn_user =  rex::getUser()->getId();
        $article_id = rex_request('article_id', 'int');
        $category_id = rex_request('category_id', 'int', $article_id);
        if ($article = rex_article::get($article_id)) {
            $category_id = rex_article::get($article_id)->getCategoryId();
        }
        $select_name = 'category_id';
        $add_homepage = true;
        if (rex_be_controller::getCurrentPagePart(1) == 'content') {
            $select_name = 'article_id';
            $add_homepage = true;
        }
        $ignore = false;
        if (rex_addon::get('quick_navigation')->getConfig('quicknavi_ignoreoffline' . $qn_user)  == '1') {
            $ignore = true;
        }
        $category_select = new rex_category_select($ignore, false, true, $add_homepage);
        $category_select->setName($select_name);
        $category_select->setSize('1');
        $category_select->setAttribute('onchange', 'this.form.submit();');
        $category_select->setSelected($category_id);
        $select = $category_select->get();
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $select);
        $options = $doc->getElementsByTagName('option');
        $droplistContext = rex_context::fromGet();
        $droplistContext->setParam('rex-api-call', 0);
        $button_label = '';
        $items = [];
        foreach ($options as $option) {
            $item = [];
            if ($option->hasAttributes()) {
                foreach ($option->attributes as $attribute) {
                    if ($attribute->name == 'value') {
                        $value = $attribute->value;
                        $item['domain'] = '';
                        if (rex_addon::get('yrewrite')->isAvailable()) {
                            $item['domain-title'] = '';
                            $item['quickID'] = $value;
                            if (rex_yrewrite::getDomainByArticleId($item['quickID']) != "") {
                                $item['domain'] = rex_yrewrite::getDomainByArticleId($item['quickID']);
                                $item['domain-title'] = ' | ' . rex_escape($item['domain']);
                            }
                        }
                        $droplistContext->setParam('category_id', $value);
                        $droplistContext->setParam('article_id', $value);
                        if ($value == '0') {
                            $droplistContext->setParam('page', 'structure');
                        } else {
                            $droplistContext->setParam('page', rex_request('page', 'string'));
                            if (rex_be_controller::getCurrentPagePart(1) != $mode && rex_be_controller::getCurrentPagePart(1) != 'content') {
                                $droplistContext->setParam('page', $mode);
                            }
                        }
                        if ($attribute->value == $category_id) {
                            $button_label = str_replace("\xC2\xA0", '', $option->nodeValue);
                            $item['active'] = true;
                        }
                    }
                }
            }
            $item['title'] = preg_replace('/\[([0-9]+)\]$/', '<small class="rex-primary-id">$1</small><br><small class="hidden">' . rex_escape($item['domain']) . '</small>', rex_escape($option->nodeValue));
            $item['hreftitle'] = '';
            if (rex_addon::get('yrewrite')->isAvailable()) {
                $item['hreftitle'] = rex_escape($option->nodeValue) . $item['domain-title'];
            }
            $item['href'] = $droplistContext->getUrl();
            $items[] = $item;
        }

        // get drop-down for quick navi from fragment
        $placeholder = '';
        $placeholder = rex_i18n::msg('quicknavi_placeholder');
        $fragment = new rex_fragment();
        $fragment->setVar('id', 'qsearch');
        $fragment->setVar('placeholder', $placeholder);
        $fragment->setVar('class', 'input-group input-group-xs has-feedback form-clear-button');
        $searchbar  = $fragment->parse('core/form/search.php');

        $fragment = new rex_fragment();
        $fragment->setVar('button_prefix', '');
        $fragment->setVar('header', $searchbar, false);
        $fragment->setVar('button_label', $button_label);
        $fragment->setVar('items', $items, false);
        $fragment->setVar('right', true, false);
        $fragment->setVar('group', true, false);
        return '<div class="btn-group">' . $fragment->parse('quick_drop.php') . '</div>';
    }

    public static function get_article_history(string $mode = 'structure', int $limit = 15): ?string
    {
        $drophistory = $date = $name = $link = $minibar = $where = $domaintitle = $status_css = $article_directions = '';


        if ($mode == 'minibar') {
            $icon_prefix = 'rex-minibar-icon--fa rex-minibar-icon--';
        } else {
            $icon_prefix = 'fa ';
        }
        if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            $were = '';
            if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
                $where = 'WHERE updateuser="' . rex::getUser()->getValue('login') . '"';
            }

            $qry = 'SELECT id, status, parent_id, clang_id, startarticle, name, updateuser, updatedate
                    FROM ' . rex::getTable('article') . ' 
                    ' . $where . ' 
                    ORDER BY updatedate DESC
                    LIMIT ' . $limit;
            $datas = rex_sql::factory()->getArray($qry);

            if (!count($datas)) {
                $link .= '<li class="alert">' . rex_i18n::msg('quick_navigation_no_entries') . '</li>';
            }

            $links = [];
            if (count($datas)) {
                foreach ($datas as $data) {
                    $dataID = rex_escape($data['id']);
                    $lang = rex_clang::get($data['clang_id']);
                    $langcode = $lang->getCode();
                    if ($langcode) {
                        $langcode = '<i class="' . $icon_prefix . 'fa-flag" aria-hidden="true"></i> ' . $langcode . ' - ';
                    }
                    $name = rex_escape($data['name']);
                    $date = rex_formatter::intlDateTime($data['updatedate']);
                    if ($mode == 'linkmap') {
                        $href = "javascript:insertLink('redaxo://" . $dataID . "','" . $name . " [" . $dataID . "]');";
                    } else {
                        $href = rex_url::backendPage(
                            'content/edit',
                            [
                                'mode' => 'edit',
                                'clang' => $data['clang_id'],
                                'category_id' => $data['parent_id'],
                                'article_id' => $data['id']
                            ]
                        );
                    }

                    if (rex_addon::get('yrewrite')->isAvailable()) {
                        if (count(rex_yrewrite::getDomains()) > 2) {
                            $domain = rex_yrewrite::getDomainByArticleId($data['id']);
                            if ($domain) {
                                $domaintitle = '<br><i class="' . $icon_prefix . 'fa-globe" aria-hidden="true"></i> ' . rex_escape($domain);
                            }
                        }
                    }
                    $status_css = ' qn_status_' . $data['status'];
                    $link .= '<li class=""><a class="quicknavi_left ' . $status_css . '" href="' . $href . '" title="' . $name . '">' . $name . '<small>' . $langcode . '<i class="' . $icon_prefix . 'fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . $domaintitle . '</small></a>';
                    $link .= '<span class="quicknavi_right"><a class ="' . $status_css . '" href="' . rex_getUrl($dataID) . '" title="' .  $name . ' ' . rex_i18n::msg('quick_navigation_title_eye') . '" target="blank"><i class="' . $icon_prefix . 'fa-eye" aria-hidden="true"></i></a></span></li>';
                    $links[] = $link;
                    $minibar .= $link;
                    $link = '';
                }
            }

            if ($mode != 'minibar') {
                $qn_user =  rex::getUser()->getId();
                if (rex_addon::get('quick_navigation')->getConfig('quicknavi_artdirections' . $qn_user)  == '1') {
                    $article_directions = '';
                } else {
                    $article_directions = self::article_nav();
                }
                $fragment = new rex_fragment();
                $fragment->setVar('prepend', $article_directions, false);
                $fragment->setVar('items', $links, false);
                $fragment->setVar('icon', 'fa fa-clock');
                return $fragment->parse('quick_button.php');
            } else {
                return '<ul class="minibar-quicknavi-items">
            ' . $minibar . '  
        </ul>';
            }
        }
        return null;
    }


    public static function article_nav(): string
    {
        $article_directions = '';
        if (rex_be_controller::getCurrentPage() == 'content/edit') {
            $predecessor = '';
            $successor = '';
            $article_stack[] = array();
            // Objekt der aktuellen Kategorie laden
            $cat = rex_category::getCurrent();
            if ($cat) {
                $article = $cat->getArticles(false);
            } else {
                $article  =  rex_article::getRootArticles();
            }
            $current_id = rex_request('article_id');
            if ($article  && $current_id) {
                if (is_array($article)) {
                    // Artikelreihenfolge in eine Array schreiben
                    foreach ($article as $var) {
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
                            if ($i + 1 < $catcount) {
                                // ID des nachfolgenden Artikels ermitteln
                                $next_id = $article_stack[$i + 1];
                                // Artikel-Objekt holen, um den Namen des vorhergehenden Artikels zu ermitteln,
                                // danach Link schreiben
                                $article = rex_article::get($next_id);

                                $href_next = rex_url::backendPage(
                                    'content/edit',
                                    [
                                        'mode' => 'edit',
                                        'clang' => rex_clang::getCurrentId(),
                                        'category_id' => rex_request('category_id'),
                                        'article_id' => $next_id
                                    ]
                                );
                                $successor = '

    <a class="btn btn-default" title="' . $article->getName() . '" href="' . $href_next . '">
      <span class="fa fa-chevron-right"> 
    </a>
';
                            }

                            // und das Ganze nochmal für den vorhergehenden Artikel
                            if ($i - 1 > -1) {
                                $prev_id = $article_stack[$i - 1];

                                $href_prev = rex_url::backendPage(
                                    'content/edit',
                                    [
                                        'mode' => 'edit',
                                        'clang' => rex_clang::getCurrentId(),
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

        <a class="btn btn-default" title="' . $article->getName() . '" href="' . $href_prev . '">
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
                $article_directions = '

' . $predecessor . '

' . $successor . '
';
            }
        }
        return $article_directions;
    }









    public static function get_favs(string $mode = 'structure'): ?string
    {
        $user =  rex::getUser()->getId();
        $datas = rex_addon::get('quick_navigation')->getConfig('quicknavi_favs' . $user);
        if ($datas && count($datas) >= 1) {
            $items = [];
            $clang = rex_request('clang', 'int');
            foreach ($datas as $data) {
                if (rex_category::get($data)) {
                    $cat = rex_category::get($data);
                    $catName = rex_escape($cat->getName());
                    $catId = rex_escape($cat->getId());
                    $href = rex_url::backendPage(
                        'content/edit',
                        [
                            'page' => $mode,
                            'clang' => $clang,
                            'category_id' => $data
                        ]
                    );
                    $addHref = rex_url::backendPage(
                        'structure',
                        [
                            'category_id' => $catId,
                            'clang' => $clang,
                            'function' => 'add_art'
                        ]
                    );
                    $items[] = '<li class="quicknavi_left"><a href="' . $href . '" title="' . $catName . '">' . $catName . '</a></li>';
                    if ($mode == 'structure') {
                        $items[] = '<li class="quicknavi_right"><a href="' . $addHref . '" title="' . rex_i18n::msg("quicknavi_title_favs") . ' ' .  $catName . '"><i class="fa fa-plus" aria-hidden="true"></i></a></li>';
                    }
                }
            }
            $fragment = new rex_fragment();
            if (count($items)) {
                $fragment->setVar('items', $items, false);
            }
            $fragment->setVar('icon', 'fa fa-star');
            return $fragment->parse('quick_button.php');
        }
        return null;
    }

    public static function get_media(int $limit = 15): ?string
    {
        $filename = $entryname = $date = $link = $where = '';
        $opener = '';
        $opener = rex_request('opener_input_field');
        if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            $file_id = rex_request('file_id', 'int');
            $quick_file_nav = '';
            if ($file_id) {
                $quick_file = rex_sql::factory();
                $quick_file->setQuery('select * from ' . rex::getTablePrefix() . 'media where id=?', [$file_id]);

                $quick_file_before = rex_sql::factory();
                $quick_file_before->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE category_id = ' . $quick_file->getValue('category_id') . ' AND updatedate > ? ORDER BY updatedate LIMIT 1', [$quick_file->getValue('updatedate')]);

                $quick_file_after = rex_sql::factory();
                $quick_file_after->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE category_id = ' . $quick_file->getValue('category_id') . ' AND updatedate < ? ORDER BY updatedate DESC LIMIT 1', [$quick_file->getValue('updatedate')]);

                if ($quick_file_before->getRows() == 1 && $quick_file_after->getRows() == 1) {
                    $quick_file_nav = '<a class="btn btn-default rex-form-aligned" href="' . rex_url::currentBackendPage(array_merge(['opener_input_field' => $opener, 'file_id' => $quick_file_before->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])) . '"><span class="fa fa-chevron-left"></span></a> - <a class="btn btn-default rex-form-aligned" href="' . rex_url::currentBackendPage(array_merge(['opener_input_field' => $opener, 'file_id' => $quick_file_after->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])) . '"><span class="fa fa-chevron-right"></span></a>';
                } elseif ($quick_file_before->getRows() == 1 && !$quick_file_after->getRows() == 1) {
                    $quick_file_nav = '<a class="btn btn-default rex-form-aligned" href="' . rex_url::currentBackendPage(array_merge(['opener_input_field' => $opener, 'file_id' => $quick_file_before->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])) . '"><span class="fa fa-chevron-left"></span></a>';
                } elseif (!$quick_file_before->getRows() == 1 && $quick_file_after->getRows() == 1) {
                    $quick_file_nav = '<a class="btn btn-default rex-form-aligned" href="' . rex_url::currentBackendPage(array_merge(['opener_input_field' => $opener, 'file_id' => $quick_file_after->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')])) . '"><span class="fa fa-chevron-right"></span></a>';
                }
            }


            $were = '';
            if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
                $where = 'WHERE updateuser="' . rex::getUser()->getValue('login') . '"';
            }
            $opener = '';
            $opener = rex_request('opener_input_field');

            $qry = 'SELECT category_id, id, title, filename, updateuser, updatedate FROM ' . rex::getTable('media') . ' ' . $where . ' ORDER BY updatedate DESC LIMIT ' . $limit;
            $datas = rex_sql::factory()->getArray($qry);
            $media = [];
            if (!count($datas)) {
                $media[] = '<li class="malert">' . rex_i18n::msg('quick_navigation_no_entries') . '</li>';
            }

            if (count($datas)) {
                foreach ($datas as $data) {
                    $entryname = '';
                    $date = rex_formatter::intlDateTime(strtotime($data['updatedate']));
                    $href = rex_url::backendPage(
                        'mediapool/media',
                        [
                            'opener_input_field' => $opener,
                            'rex_file_category' => $data['category_id'],
                            'file_id' => $data['id']
                        ]
                    );

                    if ($data['title'] != '') {
                        $entryname =   rex_escape($data['title']);
                    } else {
                        $entryname = rex_escape($data['filename']);
                    }
                    $filename = rex_escape($data['filename']);

                    $media[] = '<li><a href="' . $href . '" title="' . $filename . '">' . $entryname . '<small> <i class="fa fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . '</small></a></li>';
                }
            }
            $fragment = new rex_fragment();
            $fragment->setVar('prepend', $quick_file_nav, false);
            $fragment->setVar('items', $media, false);
            $fragment->setVar('icon', 'fa fa-clock');
            return $fragment->parse('quick_button.php');
        }
        return null;
    }


    public static function get_yformtables(): ?string
    {
        $table_name = $table_real_name = $link = $table_id = '';

        $tables = \rex_yform_manager_table::getAll();
        $active_table = false;
        $yform = rex_addon::get('yform');
        $yperm_suffix = '';
        if (version_compare($yform->getVersion(), '4.0.0-dev', '>=')) {
            $yperm_suffix = '_edit';
        }

        if (count($tables)) {
            $ytables = [];
            foreach ($tables as $table) {

                $_csrf_key = 'table_field-' . $table->getTableName();
                $_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();

                if (!$table->isHidden() && $table->isActive() && \rex::getUser()->getComplexPerm('yform_manager_table' . $yperm_suffix)->hasPerm($table->getTableName())) {
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
                            'func' => 'add',
                            '_csrf_token' => $_csrf_params['_csrf_token']
                        ]
                    );
                    $ytables[] = '<li class="quicknavi_left"><a href="' . $href . '" title="' . $table_name . '">' . $table_real_name . '</a></li><li class="quicknavi_right"><a href="' . $addHref  . '" title="' . rex_i18n::msg("title_yform") . ' ' .  $table_name . '"><i class="fa fa-plus" aria-hidden="true"></i></a></li>';
                }
            }
            if ($active_table == true) {
                $fragment = new rex_fragment();
                $fragment->setVar('items', $ytables, false);
                $fragment->setVar('icon', 'fa fa-table');
                return $fragment->parse('quick_button.php');
            }
        }
        return null;
    }


    public static function get(): string
    {
        $qn_user =  rex::getUser()->getId();

        // get requested language
        $qlang = rex_request('clang', 'int');
        if ($qlang == 0) {
            $qlang = 1;
        }

        // AddOn specific :: get data from yForm AddOn
        $dropyform = '';
        if (rex_addon::get('yform')->isAvailable()) {
            $dropyform = self::get_yformtables();
        }

        // AddOn specific :: set watson button if addon is available and show button is active
        $watson = '';
        if (rex_addon::get('watson')->isAvailable() && Watson::getToggleButtonStatus()) {
        $watson = '<div class="btn-group">'.Watson::getToggleButton(['class' => 'btn btn-default watson-btn']).'</div>';
        }

        // get user favorites
        $dropfavs = self::get_favs('structure');
        // get categories
        $droplist = self::get_cats('structure');
        // get article history
        $drophistory = self::get_article_history();

        // generate output ep for custom buttons after default set.
        $custom = '';
        $custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));

        // Output
        return '<div class="btn-group quicknavi-btn-group transparent pull-right">' . $watson . $droplist . $drophistory . $dropyform .  $custom_buttons  . $dropfavs . '</div>';
    }
}
