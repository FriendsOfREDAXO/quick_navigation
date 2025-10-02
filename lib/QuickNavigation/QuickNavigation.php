<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
use FriendsOfRedaxo\QuickNavigation\LinkMap\QuickNavigationLinkMap;
use FriendsOfRedaxo\QuickNavigation\Media\QuickNavigationMedia;
use rex_extension;
use rex_extension_point;

class QuickNavigation
{
    /**
    * @deprecated since version 8.1, will be removed in future version. Use <FriendsOfRedaxo\QuickNavigation\ApiFunction\MenuRender> instead.
    */
    public static function linkmap_list(rex_extension_point $ep): ?string
    {
        return QuickNavigationLinkMap::LinkMapQuickNavigation($ep);
    }

    /**
    * @deprecated since version 8.1, will be removed in future version. Use <FriendsOfRedaxo\QuickNavigation\ApiFunction\MenuRender> instead.
    */
    public static function media_history(rex_extension_point $ep): ?string
    {
        return QuickNavigationMedia::MediaHistory($ep);
    }

    public static function get(): string
    {
        $custom = '';
        $custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));
        return '<div class="btn-group quick-navigation-btn-group transparent pull-right">' . ButtonRegistry::getButtonsOutput() . $custom_buttons . '</div>';
    }
}
