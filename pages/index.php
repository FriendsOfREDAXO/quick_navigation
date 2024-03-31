<?php

$package = rex_addon::get('quick_navigation');
echo rex_view::title($package->i18n('quicknavi_title'));
rex_be_controller::includeCurrentPageSubPath();
