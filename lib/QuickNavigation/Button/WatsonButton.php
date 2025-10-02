<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex_addon;
use Watson\Foundation\Watson;

class WatsonButton implements ButtonInterface
{
    public function get(): string
    {
        if (!rex_addon::get('watson')->isAvailable()) {
            return '';
        }

        if (!Watson::getToggleButtonStatus()) {
            return '';
        }

        return '<div class="btn-group">' . Watson::getToggleButton(['class' => 'btn btn-default quick-navigation-watson-btn']) . '</div>';
    }
}
