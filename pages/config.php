<?php
$package = rex_addon::get('quick_navigation');
$content = $user = $buttons = '';
$formElements = [];
$n = [];
// User-ID ermitteln
$user =  rex::getUser()->getId();
// Einstellungen speichern
if (rex_post('formsubmit', 'string') == '1') {
    $package->setConfig(rex_post('config', [
        ['quicknavi_favs' . $user, 'array[int]'],
        ['quicknavi_ignoreoffline' . $user, 'int'],
        ['quicknavi_artdirections' . $user, 'int'],
    ]));
    echo rex_view::success($package->i18n('quicknavi_config_saved'));
}
$content .= '<fieldset><legend>' . $package->i18n('quicknavi_info') . '</legend>';
// Kategorieauswahl
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Kategorienauswahl
$formElements = [];
$n = [];
$n['label'] = '<label for="quicknavi-config-favs">' . $package->i18n('quicknavi_categories') . '</label>';
$category_select = new rex_category_select(false, false, true, true);
$category_select->setName('config[quicknavi_favs' . $user . '][]');
$category_select->setId('quicknavi-config-favs');
$category_select->setSize('10');
$category_select->setMultiple(true);
$category_select->setAttribute('class', 'selectpicker show-menu-arrow form-control');
$category_select->setAttribute('data-actions-box', 'false');
$category_select->setAttribute('data-live-search', 'true');
$category_select->setAttribute('data-size', '15');
$category_select->setSelected($package->getConfig('quicknavi_favs' . $user));
$n['field'] = $category_select->get();
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Ignore offline cats
$formElements = [];
$n = [];
$n['label'] = '<label for="quicknavi-forcal-checkbox">' . $package->i18n('quicknavi_ignoreoffline') . '</label>';
$n['field'] = '<input type="checkbox" id="quicknavi-forcal-checkbox" name="config[quicknavi_ignoreoffline' . $user . ']"' . (!empty($package->getConfig('quicknavi_ignoreoffline' . $user)) && $package->getConfig('quicknavi_ignoreoffline' . $user) == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Enable / Disable article directions
$formElements = [];
$n = [];
$n['label'] = '<label for="quicknavi-article-checkbox">' . $package->i18n('quicknavi_artdirections') . '</label>';
$n['field'] = '<input type="checkbox" id="quicknavi-article-checkbox" name="config[quicknavi_artdirections' . $user . ']"' . (!empty($package->getConfig('quicknavi_artdirections' . $user)) && $package->getConfig('quicknavi_artdirections' . $user) == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $package->i18n('quicknavi_config_save') . '">' . $package->i18n('quicknavi_config_save') . '</button>';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$buttons = '
<fieldset class="rex-form-action">
    ' . $buttons . '
</fieldset>
';
// Ausgabe Formular
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $package->i18n('quicknavi_general'));
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$output = $fragment->parse('core/page/section.php');
$output = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="formsubmit" value="1" />
    ' . $output . '
</form>
';
echo $output;
