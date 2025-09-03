<?php

namespace FriendsOfRedaxo\QuickNavigation;

use rex;
use rex_addon;
use rex_category_select;
use rex_fragment;
use rex_url;
use rex_view;

$package = rex_addon::get('quick_navigation');
$content = '';
$user = '';
$buttons = '';
$formElements = [];
$n = [];
// User-ID ermitteln
$user = rex::getUser()->getId();
// Einstellungen speichern
if (rex_post('formsubmit', 'string') == '1') {
    // Standard-Werte für Checkboxen setzen (falls nicht gecheckt)
    $config = rex_post('config', 'array');

    // Checkbox-Werte normalisieren
    $config['quick_navigation_ignoreoffline' . $user] = isset($config['quick_navigation_ignoreoffline' . $user]) ? 1 : 0;
    $config['quick_navigation_artdirections' . $user] = isset($config['quick_navigation_artdirections' . $user]) ? 1 : 0;
    $config['quick_navigation_media_livesearch' . $user] = isset($config['quick_navigation_media_livesearch' . $user]) ? 1 : 0;

    $package->setConfig($config);
    echo rex_view::success($package->i18n('quick_navigation_config_saved'));
}

$content .= '<fieldset><legend>' . $package->i18n('quick_navigation_info') . '</legend>';
// Kategorieauswahl
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Kategorienauswahl
$formElements = [];
$n = [];
$n['label'] = '<label for="quick-navinigation-config-favs">' . $package->i18n('quick_navigation_categories') . '</label>';
$category_select = new rex_category_select(false, false, true, true);
$category_select->setName('config[quick_navigation_favs' . $user . '][]');
$category_select->setId('quick-navinigation-config-favs');
$category_select->setSize('10');
$category_select->setMultiple(true);
$category_select->setAttribute('class', 'selectpicker show-menu-arrow form-control');
$category_select->setAttribute('data-actions-box', 'false');
$category_select->setAttribute('data-live-search', 'true');
$category_select->setAttribute('data-size', '15');
$category_select->setSelected($package->getConfig('quick_navigation_favs' . $user));
$n['field'] = $category_select->get();
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Ignore offline cats
$formElements = [];
$n = [];
$n['label'] = '<label for="quick-navinigation-forcal-checkbox">' . $package->i18n('quick_navigation_ignoreoffline') . '</label>';
$n['field'] = '<input type="checkbox" id="quick-navinigation-forcal-checkbox" name="config[quick_navigation_ignoreoffline' . $user . ']"' . (!empty($package->getConfig('quick_navigation_ignoreoffline' . $user)) && $package->getConfig('quick_navigation_ignoreoffline' . $user) == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Enable / Disable article directions
$formElements = [];
$n = [];
$n['label'] = '<label for="quick-navinigation-article-checkbox">' . $package->i18n('quick_navigation_artdirections') . '</label>';
$n['field'] = '<input type="checkbox" id="quick-navinigation-article-checkbox" name="config[quick_navigation_artdirections' . $user . ']"' . (!empty($package->getConfig('quick_navigation_artdirections' . $user)) && $package->getConfig('quick_navigation_artdirections' . $user) == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Enable / Disable media live search
$formElements = [];
$n = [];
$n['label'] = '<label for="quick-navigation-media-livesearch-checkbox">' . $package->i18n('quick_navigation_media_livesearch') . '</label>';
// Standard ist aktiviert (1), nur wenn explizit deaktiviert (0) dann nicht checked
$mediaLiveSearchValue = $package->getConfig('quick_navigation_media_livesearch' . $user);
$isChecked = ($mediaLiveSearchValue === null || $mediaLiveSearchValue === '' || $mediaLiveSearchValue == '1');
$n['field'] = '<input type="checkbox" id="quick-navigation-media-livesearch-checkbox" name="config[quick_navigation_media_livesearch' . $user . ']"' . ($isChecked ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Medienpool-Sortier-Button-Option entfernt

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $package->i18n('quick_navigation_config_save') . '">' . $package->i18n('quick_navigation_config_save') . '</button>';
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
$fragment->setVar('title', $package->i18n('quick_navigation_general'));
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
