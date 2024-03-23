<?php 
namespace FriendsOfRedaxo\QuickNavigation;

class ButtonRegistry
{
    protected static $buttons = [];

    public static function registerButton($buttonInstance, $priority = 10)
    {
        self::$buttons[] = ['button' => $buttonInstance, 'priority' => $priority];
    }

    public static function getButtonsOutput(): string
    {
        // Überprüfen, ob Buttons registriert wurden
        if (empty(self::$buttons)) {
            return ''; 
        }
        
        // Sort by prio
        usort(self::$buttons, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        $resultString = '';
        foreach (self::$buttons as $item) {
            $button = $item['button'];
            if (method_exists($button, 'getButton')) {
                $resultString .= $button->getButton();
            }
        }
        return $resultString;
    }
}
