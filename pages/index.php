<?php
// Die layout/top.php und layout/bottom.php werden automatisch eingebunden
// Die Subpages müssen dem Titel nicht mehr übergeben werden
echo rex_view::title($this->i18n('quicknavi_title')); // $this->i18n('title') ist eine Kurzform für rex_i18n::msg('demo_addon_title')
// Die Subpages werden nicht mehr über den "subpage"-Parameter gesteuert, sondern über "page" (getrennt mit einem Slash, z. B. page=demo_addon/config)
// Die einzelnen Teile des page-Pfades können mit der folgenden Funktion ausgelesen werden.
#$subpage = rex_be_controller::getCurrentPagePart(1);

// Subpages können über diese Methode eingebunden werden. So ist sichergestellt, dass auch Subpages funktionieren,
// die von anderen Addons/Plugins hinzugefügt wurden
include rex_be_controller::getCurrentPageObject()->getSubPath();