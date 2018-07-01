<?php

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
    public static function getmedia($ep)
    {
        // get media history from fragment
        if (rex_be_controller::getCurrentPagePart(1) == 'mediapool') {       
                $subject = $ep->getSubject();
                $drophistory = new rex_fragment();
                $drophistory->setVar('limit', '15');
                $drophistory = $drophistory->parse('quick_media.php');
                $custom_media_buttons ='';
 +              $custom_media_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM_MEDIA', $custom_media));
                $button = $custom_media_buttons.'<div class="input-group-btn quickmedia clearfix">'.$drophistory . '</div><select name="rex_file_category"';
                $output = str_replace('<select name="rex_file_category"', $button, $subject);
                return $output;
        }
    }

    public static function get($ep)
    {

        if (rex_be_controller::getCurrentPageObject()->isPopup()) {
            return $ep->getSubject();
        }
        // get article history from fragment
        $drophistory = new rex_fragment();
        $drophistory->setVar('limit', '15');
        $drophistory = $drophistory->parse('quick_articles.php');
        
        // get requested language
        $qlang= rex_request('clang', 'int');
        if ($qlang==0) {
            $qlang = 1;
        }
        
        // get data from sked AddOn from fragment
        $dropsked = '';
        $sked_user =  rex::getUser()->getId();
        $sked_datas = rex_addon::get('quick_navigation')->getConfig('quicknavi_sked'.$sked_user);
        if ($sked_datas != '1') {
            if (rex_addon::get('sked')->isAvailable() && rex::getUser()->hasPerm('sked[]')) {
                $dropsked = new rex_fragment();
                $dropsked = $dropsked->parse('quick_sked.php');
            }
        }
       
        // get data from yForm AddOn from fragment
        $dropyform = '';
        if (rex_addon::get('yform')->isAvailable()) {
            $dropyform = new rex_fragment();
            $dropyform->setVar('clang', $qlang);
            $dropyform = $dropyform->parse('quick_yform.php');
        }

        // get favorites from fragment
        $dropfavs = '';
        $dropfavs = new rex_fragment();
        $dropfavs->setVar('clang', $qlang);
        $dropfavs = $dropfavs->parse('quick_favs.php');

        // Generate category Quick Navi 
        // ------------ Parameter
        $article_id = rex_request('article_id', 'int');
        $category_id = rex_request('category_id', 'int', $article_id);

        $select_name = 'category_id';
        $add_homepage = true;
        if (rex_be_controller::getCurrentPagePart(1) == 'content') {
            $select_name = 'article_id';
            $add_homepage = false;
        }

        $ignore = false;
        if (rex_addon::get('quick_navigation')->getConfig('quicknavi_ignoreoffline'.$sked_user)  == '1') {
            $ignore = true;
        }

        $category_select = new rex_category_select($ignore, false, true, $add_homepage);
        $category_select->setName($select_name);
        $category_select->setSize('1');
        $category_select->setAttribute('onchange', 'this.form.submit();');
        $category_select->setSelected($category_id);
        $select = $category_select->get();
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">'.$select);
        $options = $doc->getElementsByTagName('option');

        $droplistContext = rex_context::fromGet();
        $droplistContext->setParam('category_id', 0);
        $droplistContext->setParam('rex-api-call', 0);
        if (rex_be_controller::getCurrentPagePart(1) != 'structure' && rex_be_controller::getCurrentPagePart(1) != 'content') {
            $droplistContext->setParam('page', 'structure');
        }

        $button_label = '';
        $items = [];
        foreach ($options as $option) {
            $item = [];
            if ($option->hasAttributes()) {
                foreach ($option->attributes as $attribute) {
                    if ($attribute->name == 'value') {
                        $value = $attribute->value;
                        $item['domain']='';
                        if (rex_addon::get('yrewrite')->isAvailable()) {
                            $item['domain-title'] ='';
                            $item['quickID'] = $value;
                            if (rex_yrewrite::getDomainByArticleId($item['quickID'])!="") {
                                $item['domain'] = rex_yrewrite::getDomainByArticleId($item['quickID']);
                                $item['domain-title'] = ' | '.rex_escape($item['domain']);
                            }
                        }

                        $droplistContext->setParam('category_id', $value);
                        $droplistContext->setParam('article_id', $value);
                        if ($attribute->value == $category_id) {
                            $button_label = str_replace("\xC2\xA0", '', $option->nodeValue);
                            $item['active'] = true;
                        }
                    }
                }
            }

            $item['title'] = preg_replace('/\[([0-9]+)\]$/', '<small class="rex-primary-id">$1</small><br><small class="hidden">'.$item['domain'].'</small>', rex_escape($option->nodeValue));
            $item['hreftitle'] = '';
            if (rex_addon::get('yrewrite')->isAvailable()) {
                $item['hreftitle'] = rex_escape($option->nodeValue).$item['domain-title'];
            }
            $item['href'] = $droplistContext->getUrl();
            $items[] = $item;
        }

          $formurl = rex_url::backendPage(
              'content/edit',
              [
                    'mode' => 'edit',
                    'clang' => rex_clang::getCurrentId(),
                    'article_id' => ''
                ]
          );
        
        // get category quick navi from fragment
        $placeholder ='';
        $placeholder = rex_i18n::msg('quicknavi_placeholder');
        $fragment = new rex_fragment();
        $fragment->setVar('button_prefix', '');
        $fragment->setVar('header', '<input id="qsearch" name="article_id" type="text" class="form-control input" autofocus placeholder="'.$placeholder.'" />', false);
        $fragment->setVar('button_label', $button_label);
        $fragment->setVar('items', $items, false);
        $fragment->setVar('right', true, false);
        $fragment->setVar('group', true, false);
        $droplist = '<div class="btn-group">' . $fragment->parse('quick_drop.php') . '</div>';
        
        // set watson button if addon is available and button is active
        $watson = '';
        if (rex_addon::get('watson')->isAvailable() and rex_config::get('watson', 'toggleButton', 0)==1) {
            $watson = '<div class="btn-group"><button class="btn btn-default watson-btn">Watson</button></div>';
        }
        
        // generate output ep for custom buttons after default set.
        $custom_buttons ='';
        $custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));
        return '<div class="btn-group quicknavi-btn-group pull-right">' . $watson . $droplist . $drophistory . $dropyform . $dropsked . $dropfavs . $custom_buttons . '</div>' . $ep->getSubject();
    }
}
