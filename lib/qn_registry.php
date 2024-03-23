<?pho
namespace FriendsOfRedaxo\QuickNavigation;

class ButtonRegistry
{
    protected static $buttons = [];

    public static function registerButton(string $className, array $params = [], $priority = 10)
    {
        self::$buttons[] = ['class' => $className, 'params' => $params, 'priority' => $priority];
    }

    public static function getButtonsOutput(): string
    {
        if (empty(self::$buttons)) {
            return ''; 
        }

        // Sort by prio
        usort(self::$buttons, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        $resultString = '';
        foreach (self::$buttons as $item) {
            $class = "FriendsOfRedaxo\\QuickNavigation\\Buttons\\" . $item['class'];
            if (class_exists($class)) {
                $button = new $class(...$item['params']);
                if ($button instanceof ButtonInterface && method_exists($button, 'getButton')) {
                    $resultString .= $button->getButton();
                }
            }
        }
        return $resultString;
    }
}
