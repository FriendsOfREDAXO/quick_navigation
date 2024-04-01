
# Quick Navigation 7 for Redaxo

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/quick_navigation/assets/screenshots.png)

Complete rewrite of the classic QuickNavigation 

Fearures: 
- Structure navigation with live search
- Media history
- Article history
- minibar article history
- Linkmap article history
- Linkmap structure navigation
- Buttonregistry to easy register buttons sorted by prio

ToDo: 
- new button for Yform
- new structure Navigation for linkmap
- media and linkmap buttons as separarted classes
  
## Adding custom buttons to Quick Navigation 

Create a button class

```php
class MeinButton implements   FriendsOfRedaxo\QuickNavigation\Buttons\ButtonInterface {
    public function get(): string {
        // Logik für deinen Button
        return '<button class="btn btn-primary"><i class="fa-solid fa-egg"></i> Easter Egg</button>';
    }
}

```
Add to boot.php of your AddOn

```php
use FriendsOfRedaxo\QuickNavigation\ButtonRegistry;
ButtonRegistry::registerButton(new MeinButton(), 5);
```
5 is the prio of the button
