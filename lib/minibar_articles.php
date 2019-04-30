<?php
/**
 * @package redaxo\core\minibar
 */
class rex_minibar_element_quicknavi extends rex_minibar_element
{
    public function render()
    {    
        // get article history from fragment
		$drophistory = new rex_fragment();
		$drophistory->setVar('limit', '8');
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
        return rex_minibar_element::RIGHT;
    }
}


