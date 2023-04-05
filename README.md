
# REDAXO-AddOn: Quick Navigation

Backend and frontend quick navigation for REDAXO cms

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/quick_navigation/assets/quick_navigation_screen.png)

## Features
- Category quick selection across the backend incl. linkmap
- Filter by category name, domain and category id
- Last modified articles(also in /minibar/linkmap) and media, corresponding to language and user 
- Browse through articles and media
- Supports dark-theme on REDAXO >= 5.13

### Per User Settings
- Individual category favorites in structure and linkmap
- Offline categories can be optionally hidden by user
- FOR Calendar" support can be disabled
- Article browsing can be disabled

### AddOn integration 
- YForm table selection with direct links to create a record in the selected table.
- Watson support: Quick Navigation incorporates the Watson button (if enabled in watson). 
- Article history in the minibar

## For developers
- Three extension points QUICK_NAVI_CUSTOM, QUICK_LINKMAP_CUSTOM and QUICK_NAVI_CUSTOM_MEDIA are available for developers to insert their own buttons or dropdowns. 


### Example:

put into boot.php of the Project AddOn:

```php
rex_extension::register('QUICK_NAVI_CUSTOM', ['my_quickbutton','makebutton'], rex_extension::LATE);    
```

put into my_quickbutton.php in lib folder of project AddOn

```php   

class my_quickbutton {
    public static function makebutton($ep) {

        $subject = $ep->getSubject();
        $subject .='<div class="btn-group">';
        $subject .='<button>Hallo Welt<button>';
        $subject .='</div>';
        return $subject ;
    }
}
```


## Description 

The Quick Navigation provides a category quick selection with a filter field and lists of the last edited articles and media (last modified).

In live filter you can filter by categories, authors or yrewrite domains. For example, if one enters a domain, a tree of the respective domain is quickly displayed. 

Favorite categories can be maintained individually by the editors in the AddOn settings. The favorites list only appears when favorites have been selected. New articles can be created directly via the (+) symbol next to the category. 

Quick Navigation incorporates functions of other AddOns as well, provided they have been installed and activated. 
Current: YForm, Watson

The Quick Navigation is accessible via the access key **m**.

Admins get all functions. 
For editors the availability of the functions can be defined by role permissions. It can also be configured that an editor can also track the changes of other users.   


## Installation

1. install via installer or unzip file in AddOn folder, the folder must be named "quick_navigation".
Install and activate the AddOn.
3. configure rights for roles


## Bugtracker

Found a bug or got a nice feature? [Please create an issue]

## Changelog

[CHANGELOG.md](https://github.com/FriendsOfREDAXO/quick_navigation/blob/master/CHANGELOG.md)

## Lizenz

[LICENSE.md](https://github.com/FriendsOfREDAXO/quick_navigation/blob/master/LICENSE.md)


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
