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

/* Addon Parameter */

// Addonrechte (permissions) registieren
if (rex::isBackend() && is_object(rex::getUser())) {
    rex_perm::register('quick_navi[]');
    rex_perm::register('quick_navi[idinput]');
    rex_perm::register('quick_navi[history]');
}

if (rex::isBackend() && rex::getUser() && rex::getUser()->hasPerm('quick_navi[]')) {
    rex_extension::register('PAGE_TITLE', 'QuickNavigation::get');
    rex_view::addCssFile($this->getAssetsUrl('quicknavi.css?v=' . $this->getVersion()));
	rex_view::addJsFile($this->getAssetsUrl('quicknavi.js?v=' . $this->getVersion()));
}
