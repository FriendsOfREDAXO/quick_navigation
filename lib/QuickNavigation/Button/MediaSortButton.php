<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex;
use rex_addon;
use rex_i18n;
use rex_request;

class MediaSortButton implements ButtonInterface
{
    public function get(): string
    {
        // Prüfen, ob der Benutzer die Berechtigung hat und ob die Funktion aktiviert ist
        $user = rex::getUser()->getId();
        $package = rex_addon::get('quick_navigation');

        if (!rex::getUser()) {
            return '';
        }

        // Aktuellen Sortierstatus aus dem Cookie oder Session auslesen
        $sortMode = rex_request::cookie('media_sort_alphabetical', 'string', 'false');

        // Icon und Titel basierend auf dem aktuellen Status setzen
        $icon = $sortMode === 'true' ? 'fa-sort-alpha-asc' : 'fa-sort-numeric-desc';
        $title = $sortMode === 'true' ? rex_i18n::msg('quick_navigation_media_sort_date') : rex_i18n::msg('quick_navigation_media_sort_alpha');

        return '<div class="btn-group">
                  <a class="btn btn-default" id="qn-mediasort-toggle" title="' . $title . '">
                    <i class="fa ' . $icon . '"></i>
                  </a>
                </div>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var sortButton = document.getElementById("qn-mediasort-toggle");
                    if (sortButton) {
                        sortButton.addEventListener("click", function() {
                            // Cookie umschalten
                            var currentSort = getCookie("media_sort_alphabetical") === "true";
                            document.cookie = "media_sort_alphabetical=" + (!currentSort) + "; path=/";
                            
                            // Seite neu laden
                            window.location.reload();
                        });
                    }
                    
                    function getCookie(name) {
                        var value = "; " + document.cookie;
                        var parts = value.split("; " + name + "=");
                        if (parts.length === 2) return parts.pop().split(";").shift();
                        return "false";
                    }
                });
                </script>';
    }
}
