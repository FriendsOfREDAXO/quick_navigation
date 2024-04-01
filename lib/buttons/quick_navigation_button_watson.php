<?php

namespace FriendsOfRedaxo\QuickNavigation\Buttons;

use rex_addon;
use Watson\Foundation\Watson;

class WatsonButton implements ButtonInterface
{
    public function get(): string
    {
        if (rex_addon::get('watson')->isAvailable() && Watson::getToggleButtonStatus()) {
            return '<div class="btn-group">' . Watson::getToggleButton(['class' => 'btn btn-default watson-btn']) . '</div>';
        }
        return '';
    }
}
