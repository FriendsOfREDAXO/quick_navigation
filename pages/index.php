<?php
echo rex_view::title($this->i18n('quicknavi_title')); 
include rex_be_controller::getCurrentPageObject()->getSubPath();
