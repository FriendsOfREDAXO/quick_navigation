<?php

namespace FriendsOfRedaxo\QuickNavigation\Media;

use rex;
use rex_be_controller;
use rex_i18n;
use rex_extension;
use rex_extension_point;
use rex_formatter;
use rex_fragment;
use rex_sql;
use rex_string;
use rex_url;

class QuickNavigationMedia
{
    /**
     * @param rex_extension_point<string> $ep
     */
    public static function MediaHistory(rex_extension_point $ep): ?string
    {
        if (rex_be_controller::getCurrentPagePart(1) == 'mediapool') {
            $subject = $ep->getSubject();
            $drophistory = self::GenerateMediaHistoryList();
            $custom_media_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM_MEDIA', ''));
            $button = $custom_media_buttons . '<div class="input-group-btn quickmedia clearfix">' . $drophistory . '</div><select name="rex_file_category"';
            return str_replace('<select name="rex_file_category"', $button, $subject);
        }

        return null;
    }

    public static function GenerateMediaHistoryList(int $limit = 15): ?string
    {
        $opener = rex_request('opener_input_field');
        if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            $file_id = rex_request('file_id', 'int');

            // Verwendung der neuen Funktion zur Generierung der quick_file_nav
            $quick_file_nav = self::GenerateFileNavigation($file_id, $opener);

            $where = '';
            if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
                $where = 'WHERE updateuser="' . rex::getUser()->getValue('login') . '"';
            }

            $qry = 'SELECT category_id, id, title, filename, updateuser, updatedate FROM ' . rex::getTable('media') . ' ' . $where . ' ORDER BY updatedate DESC LIMIT ' . $limit;
            $datas = rex_sql::factory()->getArray($qry);
            $listItems = [];
            if (count($datas) === 0) {
                $fragment = new rex_fragment();
                $listItems[] = $fragment->parse('QuickNavigation/NoResult.php');
            }

            foreach ($datas as $data) {
                $attributes = [
                    'title' => $data['filename'],
                    'href' => rex_url::backendPage('mediapool/media', ['opener_input_field' => $opener, 'rex_file_category' => $data['category_id'], 'file_id' => $data['id']]),
                ];

                $date = rex_formatter::intlDateTime(strtotime($data['updatedate']));

                $listItems[] = '
                    <div class="quick-navigation-item-row">
                        <a' . rex_string::buildAttributes($attributes) . '>
                            ' . ('' !== $data['title'] ? rex_escape($data['title']) : rex_escape($data['filename'])) . '
                        </a>
                    </div>
                    <div class="quick-navigation-item-row">
                        <div class="quick-navigation-item-info">
                            <small>
                                <i class="fa fa-user" aria-hidden="true"></i> 
                                ' . rex_escape($data['updateuser']) . ' - ' . $date . '
                            </small>
                        </div>
                    </div>';
            }

            $fragment = new rex_fragment([
                'label' => rex_i18n::msg('quick_navigation_media_history'),
                'icon' => 'fa-regular fa-clock',
                'listItems' => $listItems,
            ]);
           return $quick_file_nav . $fragment->parse('QuickNavigation/Dropdown.php');
        }

        return null;
    }

    protected static function GenerateFileNavigation(int $file_id, string $opener): string
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
            $quick_file_nav = '<div class="btn-group">' . $backButton . $forwardButton . '</div>';
        }

        return $quick_file_nav;
    }
}
