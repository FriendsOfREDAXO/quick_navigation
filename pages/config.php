<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
use FriendsOfRedaxo\QuickNavigation\Button\FavoriteButton;
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
    
    // Disabled Buttons speichern (Array bleibt als Array)
    if (!isset($config['quick_navigation_disabled_buttons' . $user])) {
        $config['quick_navigation_disabled_buttons' . $user] = [];
    }

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

// AddOn-Seiten Favoriten
$formElements = [];
$n = [];
$n['label'] = '<label for="quick-navigation-config-addon-favs">' . $package->i18n('quick_navigation_addon_pages_selection') . '</label>';

// Get all available pages
$availablePages = FavoriteButton::getAvailablePages();
$selectedPages = $package->getConfig('quick_navigation_addon_favs' . $user, []);
if (!is_array($selectedPages)) {
    $selectedPages = [];
}

$selectHtml = '<select name="config[quick_navigation_addon_favs' . $user . '][]" id="quick-navigation-config-addon-favs" class="selectpicker show-menu-arrow form-control" multiple="multiple" data-live-search="true" data-size="15" data-actions-box="false" size="10">';

foreach ($availablePages as $page) {
    $selected = in_array($page['key'], $selectedPages, true) ? ' selected="selected"' : '';
    $selectHtml .= sprintf(
        '<option value="%s"%s>%s</option>',
        rex_escape($page['key']),
        $selected,
        rex_escape($page['title'])
    );
}

$selectHtml .= '</select>';
$n['field'] = $selectHtml;
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

// Button-Management
$formElements = [];
$n = [];
$n['label'] = '<label>' . $package->i18n('quick_navigation_button_management') . '</label>';

// Get all available buttons
$availableButtons = ButtonRegistry::getAvailableButtons();
$disabledButtons = $package->getConfig('quick_navigation_disabled_buttons' . $user, []);
if (!is_array($disabledButtons)) {
    $disabledButtons = [];
}

$buttonCheckboxes = '';
foreach ($availableButtons as $button) {
    $isDisabled = in_array($button['id'], $disabledButtons, true);
    $buttonCheckboxes .= sprintf(
        '<div class="checkbox">
            <label>
                <input type="checkbox" name="config[quick_navigation_disabled_buttons%s][]" value="%s"%s>
                %s
            </label>
        </div>',
        $user,
        rex_escape($button['id']),
        $isDisabled ? ' checked="checked"' : '',
        rex_escape($button['label'])
    );
}

$n['field'] = '<div style="margin-top: 5px;">' . $buttonCheckboxes . '</div>';
$n['note'] = $package->i18n('quick_navigation_button_management_note');
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

$content .= '</fieldset>';

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
