<?php

namespace FriendsOfRedaxo\QuickNavigation\Media;

use rex;
use rex_addon;
use rex_extension_point;
use rex_request;

class MediaSorter
{
    /**
     * Verändert die Sortierung der Medienliste je nach Einstellung im Cookie
     *
     * @param rex_extension_point $ep
     * @return string
     */
    public static function modifyMediaListQuery(rex_extension_point $ep): string
    {
        // Nur ausführen, wenn der Benutzer die Berechtigung hat und die Funktion aktiviert ist
        $user = rex::getUser()->getId();
        $package = rex_addon::get('quick_navigation');

        if (!rex::getUser()) {
            return $ep->getSubject();
        }

        // Aktuellen Sortierstatus aus dem Cookie auslesen
        $sortMode = rex_request::cookie('media_sort_alphabetical', 'string', 'false');

        // Wenn alphabetische Sortierung gewünscht ist
        if ($sortMode === 'true') {
            $subject = $ep->getSubject();

            // Ab REDAXO 5.13.3 ist das korrekte Feld 'f.filename'
            $subject = str_replace('f.updatedate', 'f.filename, f.updatedate', $subject);
            $subject = str_replace('desc', 'asc', $subject);

            return $subject;
        }

        // Ansonsten die Standardsortierung beibehalten
        return $ep->getSubject();
    }
}
