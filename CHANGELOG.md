
Quick Navigation - Changelog
================================================================================

5.1.0 (29.06.2021)
--------------------------------------------------------------------------------
- yForm 4.x support added
- yForm 4.x fix perm 
- yform 4.x added csrf for add new dataset 

5.0.0 (27.03.2021)
--------------------------------------------------------------------------------
- Calendar menu replaced by FOR calendar


4.0.0 (23.02.2021) 
--------------------------------------------------------------------------------
- Logic moved form fragments to php class
- old fragments removed 
- New: fragment quick_button for button output
- Search field output via the core search fragment
- requires: REDAXO >=5.12
- changed some css definitions
- New: Setting enable/disbale article browsing
- interweave-media and @gharlan Svensk översättning



3.9.5 (09.12.2020)
--------------------------------------------------------------------------------
Install-Fix for PHP8

3.9.4 (13.11.2020)
--------------------------------------------------------------------------------
Browse categories  Next / previous category, some fixes

3.8.0 (10.10.2020)
--------------------------------------------------------------------------------
- Next / previous media in mediapool category

3.7.2 (03.03.2020)
--------------------------------------------------------------------------------
- Watson button fixed

3.7.1 (09.10.2019)
--------------------------------------------------------------------------------
cosmetic fix for sked button

3.7.0 (09.10.2019)
--------------------------------------------------------------------------------
Changed CSS styles inspired by yakme @tbaddade

Adds hack for #111


3.6.0 (04.05.2019)
--------------------------------------------------------------------------------
new: Article history for minibar, if AddOn available

replaces: $this by rex_addon::get('quick_navigation')


3.5.8 (23.03.2019)
--------------------------------------------------------------------------------
Avoid double detection via yrewrite thx @staabm


3.5.7 (15.03.2019)
--------------------------------------------------------------------------------
Fixed: Translations

Fixed: README for AutoToc

3.5.5 (31.01.2019)
--------------------------------------------------------------------------------
Fixed: https://github.com/FriendsOfREDAXO/quick_navigation/issues/103

3.5.4 (22.12.2018)
--------------------------------------------------------------------------------
update: Traducción en castellano @nandes2062

update: Svensk översättning @interweave-media 

3.5.3 (15.12.2018)
--------------------------------------------------------------------------------
focus on active link corrected


3.5.2 (13.12.2018)
--------------------------------------------------------------------------------
minor css changes

3.5.1
--------------------------------------------------------------------------------
fixed Warning: Undefined variable "mode"

3.5.0 (28.10.2018)
--------------------------------------------------------------------------------
Performance boost by @staabm

watson button needs watson >= 2.1.0

3.4.0 (19.07.2018)
--------------------------------------------------------------------------------
New: ExtensionPoint for custom linkmap butttons

Changed: index.php now uses includeCurrentPageSubPath - thx @christophboecker

3.3.1 (07.07.2018)
--------------------------------------------------------------------------------
New: Pick and preview recently added or changed articles in linkmap (history)

Added legend to readme

3.2.1 (06.07.2018)
--------------------------------------------------------------------------------
New: shows status of recently added or changed articles

3.1.0 (04.07.2018)
--------------------------------------------------------------------------------
- favorites in Linkmap

3.0.1 (02.07.2018)
--------------------------------------------------------------------------------
- fix notice mediapool


3.0.0 (02.07.2018)
--------------------------------------------------------------------------------
- changed: cats dropdown as fragment, so it can be easy replaced
- changed: rename of methods
- new: cats fragment got a mode var
- new: linkmap with cat select

2.5.0 (01.07.2018)
--------------------------------------------------------------------------------
- New extension point QUICK_NAVI_CUSTOM to add own buttons
- New extension point QUICK_NAVI_CUSTOM_MEDIA to add own buttons
- fixed: PHP 7.2 count warning fixed https://github.com/FriendsOfREDAXO/quick_navigation/issues/75 
- New and changed: user right for history view. Admin should reconfigure rights
  default view now: only own recently changed data
- deleted: tranlations because of changed right setting
- fixed: show yform botton only if tables are active and visible
- some new code comments
- css color changes
- css buttons borderless and without background colors


2.4.2 (02.06.2018)
--------------------------------------------------------------------------------
Compatibility fixes for REDAXO 5.6 - Danke @gharlan

Traducción en castellano - Danke @nandes2062

cs fixes

2.4.1 (14.05.2018)
--------------------------------------------------------------------------------
Traducción en castellano Danke @nandes2062 

Filter placeholder now in lng-file

2.4.0 (23.02.2018)
--------------------------------------------------------------------------------
per user setting to ignore offline categories

2.3.4 (12.02.2018)
--------------------------------------------------------------------------------
don't show hidden tables of yform

2.3.3 (30.01.2018)
--------------------------------------------------------------------------------
table name translations now supported

2.3.2 (25.01.2018)
--------------------------------------------------------------------------------
Spanish Transation thanks to @nandes2062


2.3.1 (21.01.2018)
--------------------------------------------------------------------------------
- Media history now part of mediapool toolbar


2.3.0 (19.01.2018)
--------------------------------------------------------------------------------
- Favorites now optional, should be activated on role definition
- Permission fix for favorites selection

2.2.6 (07.01.2018)
--------------------------------------------------------------------------------
eaCe case sensitive changes

https://github.com/FriendsOfREDAXO/quick_navigation/issues/53

2.2.5 (02.01.2018)
--------------------------------------------------------------------------------
Added some title attributes


2.2.4 (22.12.2017)
--------------------------------------------------------------------------------
New: Open History-Article on frontpage

Bugfix: Prevent install when yform <2.0 is installed 

2.2.3 (21.12.2017)
--------------------------------------------------------------------------------
Bugfixes
Article-ID-Input removed. Please use watson. 

2.2.2 (21.12.2017)
--------------------------------------------------------------------------------
New: 
- Sked integration
- Added new Setting for Sked

2.1.1 (20.12.2017)
--------------------------------------------------------------------------------
New: 
- Quick YForm-Table-Selection + Quick Add dataset
- Quick Add-Article in Favorites. 
- Minor style fixes

2.0.6 (27.10.2017)
--------------------------------------------------------------------------------
Security fix, uses rex_escape therfore min version of REDAXO: 5.4

2.0.4 (12.10.2017)
--------------------------------------------------------------------------------
Deleted own Cache-Busting

2.0.3 (11.10.2017)
--------------------------------------------------------------------------------
bugfix, check if fav exists,
sv_se.lang thanks to @interweave-media

2.0.2 (13.09.2017)
--------------------------------------------------------------------------------
Checks now if user is available

2.0.1 (11.09.2017)
--------------------------------------------------------------------------------
Bugfixes 

2.0.0 (05.09.2017)
--------------------------------------------------------------------------------
- ID-Input replaced
- Favorites 

1.3.5 (04.09.2017)
--------------------------------------------------------------------------------
Access Key m to quick open Quick Navigation

1.3.4 (17.08.2017)
--------------------------------------------------------------------------------
CSS fix, HTML fix

1.3.1 (07.08.2017)
--------------------------------------------------------------------------------
IE 11 - CSS fix

1.3.0 (07.08.2017)
--------------------------------------------------------------------------------
Fixed search, focus on active Structure-Page

1.2.0 (31.07.2017
--------------------------------------------------------------------------------
Now includes Watson button

1.1.2 (28.07.2017
--------------------------------------------------------------------------------
history shows domains only if there are more than the default and 1st entry

1.1.1 (25.07.2017
--------------------------------------------------------------------------------
yrewrite domain support for history - lists the domain of an article

1.1.0 (13.07.2017
--------------------------------------------------------------------------------
yrewrite domain support 

1.0.6 (08.06.2017
--------------------------------------------------------------------------------
language: pt_br 

1.0.5 (20.03.2017
--------------------------------------------------------------------------------
autofocus with .focus() 

1.0.4 (19.03.2017)
--------------------------------------------------------------------------------
better markup, scrolling on iOS corrected

1.0.3 (18.03.2017)
--------------------------------------------------------------------------------
rex:ready + autofocus on search

1.0.2 (01.03.2017)
--------------------------------------------------------------------------------
rex-api-call fix

1.0.1 (27.02.2017)
--------------------------------------------------------------------------------
media permission fix

1.0.0 (27.02.2017)
--------------------------------------------------------------------------------
Feature complete with media history

0.6.1 (26.02.2017)
--------------------------------------------------------------------------------
Better english translation, fixed version constraints

0.6.0 (19.02.2017)
--------------------------------------------------------------------------------
CTYPE on Links

0.5.0 (18.02.2017)
--------------------------------------------------------------------------------
Minor style changes, outsourced history as fragment

0.4.0 (7.02.2017)
--------------------------------------------------------------------------------
Translation added, some style changes, right: own files only

0.3.0 (6.02.2017)
--------------------------------------------------------------------------------
Added Last modified articles history

0.2.0 (1.02.2017)
--------------------------------------------------------------------------------
Role-Rights to enable / disable the navigation and id-input

0.1.5 (31.01.2017)
--------------------------------------------------------------------------------
Filter added

0.1.4 (30.01.2017)
--------------------------------------------------------------------------------
ID-Input added

0.0.1 (09.06.2016)
--------------------------------------------------------------------------------

* Wake up
