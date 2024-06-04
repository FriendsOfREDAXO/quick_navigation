<?php

namespace FriendsOfRedaxo\QuickNavigation\LinkMap;

use FriendsOfRedaxo\QuickNavigation\Button\ArticleHistoryButton;
use FriendsOfRedaxo\QuickNavigation\Button\CategoryButton;
use rex;
use rex_be_controller;
use rex_extension;
use rex_extension_point;

class QuickNavigationLinkMap
{
    /**
     * @param rex_extension_point<string> $ep
     */
    public static function LinkMapNavigation(rex_extension_point $ep): ?string
    {
        if (rex_be_controller::getCurrentPagePart(1) == 'linkmap') {

            $category_list = new CategoryButton();
            $category_button_output = $category_list->get();

            $history_list = new ArticleHistoryButton('linkmap', 15);
            $history_list_output = $history_list->get();
            
            $custom_linkmap_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_LINKMAP_CUSTOM', $custom));
            return '<div class="btn-group quick-navigation-btn-group linkmapbt pull-right">' . $history_list_output .   $category_button_output . $custom_linkmap_buttons . '</div>' . $ep->getSubject();
        }
        return null;
    }
}
