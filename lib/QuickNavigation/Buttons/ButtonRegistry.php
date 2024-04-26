<?php

namespace FriendsOfRedaxo\QuickNavigation\Buttons;

class ButtonRegistry
{
    /**
     * @var array<array{instance: mixed, priority: int}> an array that contains button instances and their priorities
     */
    protected static array $buttons = [];

    /**
     * Registers a button with an optional priority.
     * Lower priority values cause the button to appear earlier in the list.
     */
    public static function registerButton(ButtonInterface $buttonInstance, int $priority = 10): void
    {
        self::$buttons[] = ['instance' => $buttonInstance, 'priority' => $priority];
    }

    /**
     * Returns the buttons sorted by their priority.
     */
    public static function getButtonsOutput(): string
    {
        // Sorts the buttons based on their priority
        usort(self::$buttons, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        $resultString = '';
        foreach (self::$buttons as $button) {
            // Since all instances implement ButtonInterface, it's guaranteed that get() exists.
            $resultString .= $button['instance']->get();
        }

        return $resultString;
    }
}
