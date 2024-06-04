<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Button\ArticleHistoryButton;
use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
use FriendsOfRedaxo\QuickNavigation\Button\CategoryButton;
use rex;
use rex_be_controller;
use rex_extension;
use rex_extension_point;
use rex_formatter;
use rex_fragment;
use rex_sql;
use rex_string;
use rex_url;

class QuickNavigation
{
    /**
     * @param rex_extension_point<string> $ep
     */
    public static function LinkMapQuickNavigation(rex_extension_point $ep): ?string
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

            $catsbutton = new CategoryButton();
            $catsbutton_output = $catsbutton->get();

            $history = new ArticleHistoryButton('linkmap', 15);
            $drophistory = $history->get();
            $custom_linkmap_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_LINKMAP_CUSTOM', $custom));
            return '<div class="btn-group quick-navigation-btn-group linkmapbt pull-right">' . $drophistory . $catsbutton_output . $custom_linkmap_buttons . '</div>' . $ep->getSubject();
        }

        return null;
    }

    public static function get(): string
    {
        $custom = '';
        $custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));
        return '<div class="btn-group quick-navigation-btn-group transparent pull-right">' . ButtonRegistry::getButtonsOutput() . $custom_buttons . '</div>';
    }
}
