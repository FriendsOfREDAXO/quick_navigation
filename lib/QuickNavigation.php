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
    public static function get($ep)
    {

        if (rex_be_controller::getCurrentPageObject()->isPopup()) {
            return $ep->getSubject();
        }
        // Auslesen der Artikel-Liste
        $drophistory = new rex_fragment();
        $drophistory = $drophistory->parse('quick_files.php');
        
        // ------------ Parameter
        $article_id = rex_request('article_id', 'int');
        $category_id = rex_request('category_id', 'int', $article_id);

        $select_name = 'category_id';
        $add_homepage = true;
        if (rex_be_controller::getCurrentPagePart(1) == 'content') {
            $select_name = 'article_id';
            $add_homepage = false;
        }

        $category_select = new rex_category_select(false, false, true, $add_homepage);
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
                        $droplistContext->setParam('category_id', $value);
                        $droplistContext->setParam('article_id', $value);
                        if ($attribute->value == $category_id) {
                            $button_label = str_replace("\xC2\xA0", '', $option->nodeValue);
                            $item['active'] = true;
                        }
                    }
                }
            }
            $item['title'] = preg_replace('/\[([0-9]+)\]$/', '<small class="rex-primary-id">$1</small>', $option->nodeValue);
            $item['hreftitle'] = $option->nodeValue;
            $item['href'] = $droplistContext->getUrl();
            $items[] = $item;
        }
        $fragment = new rex_fragment();
        $fragment->setVar('button_prefix', '');
        $fragment->setVar('button_label', $button_label);
        $fragment->setVar('items', $items, false);
        $fragment->setVar('right', true, false);
        $fragment->setVar('group', true, false);
        $droplist = '<div class="btn-group">' . $fragment->parse('quick_drop.php') . '</div>';

        $quickout = '';
        if (rex::getUser()->hasPerm('quick_navigation[idinput]')) {
            $formurl = rex_url::backendPage(
                'content/edit',
                [
                    'mode' => 'edit',
                    'clang' => rex_clang::getCurrentId(),
                    'article_id' => ''
                ]
            );
            $quickout =
                '<div class="btn-group">
                    <form action="' . $formurl . '" method="post">
                        <div class="input-group">
                            <input class="form-control" id="qnid" type="text" name="article_id" placeholder="ID" value="" />
                        </div>
                    </form>
                </div>';
        }

        return '<div class="btn-group pull-right">' . $quickout . $droplist . $drophistory . '</div>' . $ep->getSubject();

    }
}
