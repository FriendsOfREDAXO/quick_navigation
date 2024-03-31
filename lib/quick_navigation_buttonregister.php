<?php

namespace FriendsOfRedaxo\QuickNavigation;

class ButtonRegistry
{
    protected static $buttons = [];

    /**
     * Registriert einen Button mit einer optionalen Priorität.
     * Niedrigere Prioritätswerte führen dazu, dass der Button früher in der Liste erscheint.
     *
     * @param mixed $buttonInstance Instanz des Buttons
     * @param int $priority Priorität des Buttons
     */
    public static function registerButton($buttonInstance, $priority = 10): void
    {
        self::$buttons[] = ['instance' => $buttonInstance, 'priority' => $priority];
    }

    /**
     * Gibt die Buttons sortiert nach ihrer Priorität aus.
     */
    public static function getButtonsOutput(): string
    {
        // Sortiert die Buttons basierend auf ihrer Priorität
        usort(self::$buttons, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        $resultString = '';
        foreach (self::$buttons as $button) {
            $instance = $button['instance'];
            if (method_exists($instance, 'get')) {
                $resultString .= $instance->get();
            }
        }
        return $resultString;
    }
}
