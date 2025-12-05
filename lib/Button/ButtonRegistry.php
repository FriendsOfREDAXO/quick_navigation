<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex;
use rex_addon;

class ButtonRegistry
{
    /**
     * @var array<array{id: string, label: string, instance: mixed, priority: int}> an array that contains button instances and their priorities
     */
    protected static array $buttons = [];

    /**
     * Registers a button with an optional priority.
     * Lower priority values cause the button to appear earlier in the list.
     *
     * @param string $id Unique identifier for the button (e.g. 'article_navigation', 'watson')
     * @param string $label Human readable label for config (e.g. 'Artikelnavigation', 'Watson')
     */
    public static function registerButton(ButtonInterface $buttonInstance, int $priority = 10, string $id = '', string $label = ''): void
    {
        // Generate ID from class name if not provided
        if ($id === '') {
            $className = get_class($buttonInstance);
            $id = strtolower(str_replace('Button', '', substr($className, strrpos($className, '\\') + 1)));
        }

        // Use ID as label if not provided
        if ($label === '') {
            $label = ucfirst(str_replace('_', ' ', $id));
        }

        self::$buttons[] = [
            'id' => $id,
            'label' => $label,
            'instance' => $buttonInstance,
            'priority' => $priority,
        ];
    }

    /**
     * Returns the buttons sorted by their priority, filtered by user preferences.
     */
    public static function getButtonsOutput(): string
    {
        $user = rex::getUser();
        if (!$user) {
            return '';
        }

        $userId = $user->getId();
        $addon = rex_addon::get('quick_navigation');

        // Get disabled buttons for this user (Opt-Out)
        $disabledButtons = $addon->getConfig('quick_navigation_disabled_buttons' . $userId, []);
        if (!is_array($disabledButtons)) {
            $disabledButtons = [];
        }

        // Sorts the buttons based on their priority
        usort(self::$buttons, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        $resultString = '';
        foreach (self::$buttons as $button) {
            // Skip if button is disabled for this user
            if (in_array($button['id'], $disabledButtons, true)) {
                continue;
            }

            // Since all instances implement ButtonInterface, it's guaranteed that get() exists.
            $resultString .= $button['instance']->get();
        }

        return $resultString;
    }

    /**
     * Returns all available buttons with their metadata for configuration.
     * @return array<array{id: string, label: string, priority: int}>
     */
    public static function getAvailableButtons(): array
    {
        // Sort by priority
        $sortedButtons = self::$buttons;
        usort($sortedButtons, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        $result = [];
        foreach ($sortedButtons as $button) {
            $result[] = [
                'id' => $button['id'],
                'label' => $button['label'],
                'priority' => $button['priority'],
            ];
        }

        return $result;
    }
}
