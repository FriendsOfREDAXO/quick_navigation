<?php

namespace FriendsOfRedaxo\QuickNavigation;

use rex_addon;
use rex_be_controller;
use rex_view;

$package = rex_addon::get('quick_navigation');
echo rex_view::title($package->i18n('quicknavi_title'));
rex_be_controller::includeCurrentPageSubPath();
