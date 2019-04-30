<?php
/**
 * @package redaxo\core\minibar
 */
class rex_minibar_element_quicknavi extends rex_minibar_element
{
    public function render()
    {
        $status = 'rex-syslog-ok';

        $sysLogFile = rex_logger::getPath();
        $lastModified = filemtime($sysLogFile);
        // "last-seen" will be updated, when the user looks into the syslog
        $lastSeen = rex_session('rex_syslog_last_seen');

        // when the user never looked into the file (e.g. after login), we dont have a timely reference point.
        // therefore we check for changes in the file within the last 24hours
        if (!$lastSeen) {
            if ($lastModified > strtotime('-24 hours')) {
                $status = '';
            }
        } elseif ($lastModified && $lastModified > $lastSeen) {
            $status = '';
        }
        
        // get article history from fragment
		$drophistory = new rex_fragment();
		$drophistory->setVar('limit', '15');
		$drophistory->setVar('mode', 'minibar');
		$drophistory = $drophistory->parse('quick_articles.php');
        return
        '
        <style>
        ul.minibar-quickfiles  { display: block; font-size: 16px; padding: 0; margin: 0; min-width: 300px;}
        .minibar-quickfiles li { display: block; clear both; font-size: 16px; margin: 0; line-height: 16px; }
        .minibar-quickfiles small { clear: both; display: block; color: #ccc; font-size: 10px;}
        .quicknavi_left {
	width: 88%;
	display: inline-block;
}


.minibar-quickfiles li a.quicknavi_left.qn_status_1 {
    color:#4b9ad9;
    border-left: 3px solid #3bb594; 
    padding-bottom: 10px;
}

.minibar-quickfiles li a.quicknavi_left.qn_status_0 {
    opacity: 0.6;
    border-left: 3px solid #ccc;
}

.minibar-quickfiles .quicknavi_right {
	width: 12%;
	display: inline-block;
}
         .minibar-quickfiles a {color: #FFF; text-decoration: none; padding-left: 5px; padding-bottom: 7px;}
        </style>

        <div class="rex-minibar-item">
            <span class="rex-minibar-icon">
                <i class="rex-minibar-icon--fa rex-minibar-icon--fa-clock-o"></i>
            </span>
            <span class="rex-minibar-value">
            &nbsp;
            </span>
        </div>
        <div class="rex-minibar-info">
            <div class="rex-minibar-info-group">
             '.$drophistory.'
            </div>
           
        </div>
        ';
    }

    public function getOrientation()
    {
        return rex_minibar_element::LEFT;
    }
}


