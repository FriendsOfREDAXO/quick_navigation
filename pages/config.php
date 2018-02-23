<?php
// Variablen initialisieren
$content = $user = $buttons = '';
$formElements = [];
$n = [];
// User-ID ermitteln
$user =  rex::getUser()->getId();
// Einstellungen speichern
if (rex_post('formsubmit', 'string') == '1') {
    $this->setConfig(rex_post('config', [
        ['quicknavi_favs'.$user, 'array[int]'],
        ['quicknavi_sked'.$user, 'int'],
        ['quicknavi_ignoreoffline'.$user, 'int'],
    ]));
    echo rex_view::success($this->i18n('quicknavi_config_saved'));
}
$content .= '<fieldset><legend>' . $this->i18n('quicknavi_info') . '</legend>';
// Kategorieauswahl 
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Kategorienauswahl
$formElements = [];
$n = [];
$n['label'] = '<label for="quicknavi-config-favs">' . $this->i18n('quicknavi_categories') . '</label>';
$category_select = new rex_category_select(false, false, true, true);
$category_select->setName('config[quicknavi_favs'.$user.'][]');
$category_select->setId('quicknavi-config-favs');
$category_select->setSize('10');
$category_select->setMultiple(true);
$category_select->setAttribute('class', 'selectpicker show-menu-arrow form-control');
$category_select->setAttribute('data-actions-box', 'false');
$category_select->setAttribute('data-live-search', 'true');
$category_select->setAttribute('data-size', '15');
$category_select->setSelected($this->getConfig('quicknavi_favs'.$user));
$n['field'] = $category_select->get();
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');	
// Ignore offline cats
	$formElements = [];
	$n = [];
	$n['label'] = '<label for="quicknavi-sked-checkbox">' . $this->i18n('quicknavi_ignoreoffline') . '</label>';
	$n['field'] = '<input type="checkbox" id="quicknavi-sked-checkbox" name="config[quicknavi_ignoreoffline'.$user.']"' . (!empty($this->getConfig('quicknavi_ignoreoffline'.$user)) && $this->getConfig('quicknavi_ignoreoffline'.$user) == '1' ? ' checked="checked"' : '') . ' value="1" />';
	$formElements[] = $n;
	$fragment = new rex_fragment();
	$fragment->setVar('elements', $formElements, false);
	$content .= $fragment->parse('core/form/checkbox.php');



// SKED Checkbox
if(rex_addon::get('sked')->isAvailable() && rex::getUser()->hasPerm('sked[]')) {
	$formElements = [];
	$n = [];
	$n['label'] = '<label for="quicknavi-sked-checkbox">' . $this->i18n('quicknavi_sked') . '</label>';
	$n['field'] = '<input type="checkbox" id="quicknavi-sked-checkbox" name="config[quicknavi_sked'.$user.']"' . (!empty($this->getConfig('quicknavi_sked'.$user)) && $this->getConfig('quicknavi_sked'.$user) == '1' ? ' checked="checked"' : '') . ' value="1" />';
	$formElements[] = $n;
	$fragment = new rex_fragment();
	$fragment->setVar('elements', $formElements, false);
	$content .= $fragment->parse('core/form/checkbox.php');
}

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $this->i18n('quicknavi_config_save') . '">' . $this->i18n('quicknavi_config_save') . '</button>';
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
$fragment->setVar('title', $this->i18n('quicknavi_general'));
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
