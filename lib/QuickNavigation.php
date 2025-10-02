<?php

namespace FriendsOfRedaxo\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Button\ButtonRegistry;
use rex_extension;
use rex_extension_point;

class QuickNavigation
{
    public static function get(): string
    {
        $custom = '';
        $custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));
        return '<div class="btn-group quick-navigation-btn-group transparent pull-right">' . ButtonRegistry::getButtonsOutput() . $custom_buttons . '</div>';
    }
}
