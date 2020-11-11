<?php
// Generate category Quick Navi
// ------------ Parameter
$qn_user =  rex::getUser()->getId();
$article_id = rex_request('article_id', 'int');
$category_id = rex_request('category_id', 'int', $article_id);
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
                    if (rex_be_controller::getCurrentPagePart(1) != $this->mode && rex_be_controller::getCurrentPagePart(1) != 'content') {
                        $droplistContext->setParam('page', $this->mode);
                    }
                }
                if ($attribute->value == $category_id) {
                    $button_label = str_replace("\xC2\xA0", '', $option->nodeValue);
                    $item['active'] = true;
                }
            }
        }
    }
    $item['title'] = preg_replace('/\[([0-9]+)\]$/', '<small class="rex-primary-id">$1</small><br><small class="hidden">' . $item['domain'] . '</small>', rex_escape($option->nodeValue));
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
$fragment->setVar('button_prefix', '');
$fragment->setVar('header', '<input id="qsearch" name="article_id" type="text" class="form-control input" autofocus placeholder="' . $placeholder . '" />', false);
$fragment->setVar('button_label', $button_label);
$fragment->setVar('items', $items, false);
$fragment->setVar('right', true, false);
$fragment->setVar('group', true, false);
echo '<div class="btn-group">' . $fragment->parse('quick_drop.php') . '</div>';
