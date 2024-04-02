
# Quick Navigation 7 for Redaxo

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/quick_navigation/assets/screenshots.png)

Complete rewrite of the classic QuickNavigation 

Features: 
- Structure navigation with live search
- Media history
- Article history
- minibar article history
- Linkmap article history
- Linkmap structure navigation
- Buttonregistry to easy register buttons sorted by prio in structure view
- extension points to add classic buttons to structure, linkmap, mediapool
- Category favorites
- YForm quick navigation

## Description 

The Quick Navigation provides a category quick selection with a filter field and lists of the last edited articles and media (last modified).

In live filter you can filter by categories, authors or yrewrite domains. For example, if one enters a domain, a tree of the respective domain is quickly displayed. 

Favorite categories can be maintained individually by the editors in the AddOn settings. The favorites list only appears when favorites have been selected. New articles can be created directly via the (+) symbol next to the category. 

Quick Navigation incorporates functions of other AddOns as well, provided they have been installed and activated. 
Current: YForm, Watson

The Quick Navigation is accessible via the access key **m**.

Admins get all functions. 
For editors the availability of the functions can be defined by role permissions. It can also be configured that an editor can also track the changes of other users.  
  
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

To see the default prios, look alt the boot.php

## Author

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

**Project Lead**

[Thomas Skerbis](https://github.com/skerbis)

**Credits**

First Release: [Thomas Blum](https://github.com/tbaddade)

Performance Optimization:  [Markus Staab](https://github.com/staabm) 

"Bugfixes / Testing": [Hirbod](https://github.com/hirbod), [Marco Hanke](https://github.com/marcohanke)

