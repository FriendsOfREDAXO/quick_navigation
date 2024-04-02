<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Buttons\CatsButton;
use rex;
use rex_be_controller;
use rex_extension;
use rex_extension_point;
use rex_formatter;
use rex_fragment;
use rex_i18n;
use rex_sql;
use rex_url;

use function count;

class QuickNavigation
{
    /**
     * @param rex_extension_point<string> $ep
     */
    public static function media_history(rex_extension_point $ep): ?string
    {
        if (rex_be_controller::getCurrentPagePart(1) == 'mediapool') {
            $subject = $ep->getSubject();
            $drophistory = self::get_media();
            $custom_media_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM_MEDIA', ''));
            $button = $custom_media_buttons . '<div class="input-group-btn quickmedia clearfix">' . $drophistory . '</div><select name="rex_file_category"';
            return str_replace('<select name="rex_file_category"', $button, $subject);
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
            $custom = '';
            $drophistory = '';
            // Check if language is set
            $qlang = rex_request('clang', 'int', 0);
            if ($qlang == 0 || $qlang == '') {
                $qlang = 1;
            }

            $catsbutton = new catsButton();
            $catsbutton_output = $catsbutton->get();

            $history = new \FriendsOfRedaxo\QuickNavigation\Buttons\ArticleHistory('linkmap', 15);
            $drophistory = $history->get();
            $custom_linkmap_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_LINKMAP_CUSTOM', $custom));
            return '<div class="btn-group quicknavi-btn-group linkmapbt pull-right">' . $drophistory . $catsbutton_output . $custom_linkmap_buttons . '</div>' . $ep->getSubject();
        }

        return null;
    }

    public static function get_media(int $limit = 15): ?string
    {
        $opener = rex_request('opener_input_field');
        if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            $file_id = rex_request('file_id', 'int');

            // Verwendung der neuen Funktion zur Generierung der quick_file_nav
            $quick_file_nav = self::generateQuickFileNav($file_id, $opener);

            $where = '';
            if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
                $where = 'WHERE updateuser="' . rex::getUser()->getValue('login') . '"';
            }

            $qry = 'SELECT category_id, id, title, filename, updateuser, updatedate FROM ' . rex::getTable('media') . ' ' . $where . ' ORDER BY updatedate DESC LIMIT ' . $limit;
            $datas = rex_sql::factory()->getArray($qry);
            $media = [];
            if (count($datas) === 0) {
                $media[] = '<li class="malert">' . rex_i18n::msg('quick_navigation_no_entries') . '</li>';
            }

            foreach ($datas as $data) {
                $entryname = $data['title'] != '' ? rex_escape($data['title']) : rex_escape($data['filename']);
                $filename = rex_escape($data['filename']);
                $date = rex_formatter::intlDateTime(strtotime($data['updatedate']));
                $href = rex_url::backendPage(
                    'mediapool/media',
                    [
                        'opener_input_field' => $opener,
                        'rex_file_category' => $data['category_id'],
                        'file_id' => $data['id'],
                    ]
                );

                $media[] = '<li><a href="' . $href . '" title="' . $filename . '">' . $entryname . '<small> <i class="fa fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . '</small></a></li>';
            }

            $fragment = new rex_fragment();
            $fragment->setVar('prepend', $quick_file_nav, false);
            $fragment->setVar('items', $media, false);
            $fragment->setVar('icon', 'fa fa-clock');
            return $fragment->parse('quick_button.php');
        }

        return null;
    }

    protected static function generateQuickFileNav(int $file_id, string $opener): string
    {
        $quick_file_nav = '';
        if ($file_id !== 0) {
            $quick_file = rex_sql::factory();
            $quick_file->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE id = ?', [$file_id]);

            $quick_file_before = rex_sql::factory();
            $quick_file_before->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE category_id = ? AND updatedate > ? ORDER BY updatedate LIMIT 1', [$quick_file->getValue('category_id'), $quick_file->getValue('updatedate')]);

            $quick_file_after = rex_sql::factory();
            $quick_file_after->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE category_id = ? AND updatedate < ? ORDER BY updatedate DESC LIMIT 1', [$quick_file->getValue('category_id'), $quick_file->getValue('updatedate')]);

            // Link für "Zurück" Button, aktiv oder deaktiviert
            $backButton = $quick_file_before->getRows() == 1
                ? '<a class="btn btn-default rex-form-aligned" href="' . rex_url::currentBackendPage(['opener_input_field' => $opener, 'file_id' => $quick_file_before->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')]) . '"><span class="fa fa-chevron-left"></span></a>'
                : '<a class="btn btn-default rex-form-aligned disabled"><span class="fa fa-chevron-left"></span></a>';

            // Link für "Vorwärts" Button, aktiv oder deaktiviert
            $forwardButton = $quick_file_after->getRows() == 1
                ? '<a class="btn btn-default rex-form-aligned" href="' . rex_url::currentBackendPage(['opener_input_field' => $opener, 'file_id' => $quick_file_after->getValue('id'), 'rex_file_category' => $quick_file->getValue('category_id')]) . '"><span class="fa fa-chevron-right"></span></a>'
                : '<a class="btn btn-default rex-form-aligned disabled"><span class="fa fa-chevron-right"></span></a>';

            // Kombinieren der Buttons mit einem Trennzeichen
            $quick_file_nav = $backButton . ' - ' . $forwardButton;
        }
        return $quick_file_nav;
    }

    public static function get(): string
    {
        $custom = '';
        $custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));
        return '<div class="btn-group quicknavi-btn-group transparent pull-right">' . ButtonRegistry::getButtonsOutput() . $custom_buttons . '</div>';
    }
}
