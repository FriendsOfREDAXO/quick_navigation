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
        // Sort by priority
        usort(self::$buttons, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        $resultString = '';
        foreach (self::$buttons as $item) {
            $button = $item['button'];
            if (method_exists($button, 'get')) {
                $resultString .= $button->get();
            }
        }
        return $resultString;
    }
}
