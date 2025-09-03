<?php

namespace FriendsOfRedaxo\QuickNavigation\ApiFunction;

use rex;
use rex_api_function;
use rex_api_result;
use rex_media;
use rex_media_manager;
use rex_media_service;
use rex_request;
use rex_response;
use rex_formatter;
use rex_url;
use rex_sql;
use Exception;

/**
 * API function for media live search in Quick Navigation.
 */
class MediaSearch extends rex_api_function
{
    public function execute()
    {
        // Debug-Logging
        error_log('MediaSearch API called');

        // Content-Type vor jeder Ausgabe setzen
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // Output-Puffer leeren
        if (ob_get_level()) {
            ob_clean();
        }

        $searchTerm = rex_request('term', 'string', '');
        $categoryId = rex_request('category_id', 'int', 0);
        $openerInputField = rex_request('opener_input_field', 'string', '');
        $types = rex_request('types', 'string', '');

        // Berechtigung prüfen
        if (!rex::getUser() || !rex::getUser()->hasPerm('media[read]')) {
            $result = [
                'success' => false,
                'error' => 'Keine Berechtigung'
            ];
            echo json_encode($result);
            exit;
        }

        if (strlen($searchTerm) < 2) {
            $result = [
                'success' => false,
                'error' => 'Suchbegriff zu kurz'
            ];
            echo json_encode($result);
            exit;
        }

        try {
            // Debug: Log search parameters
            error_log('MediaSearch API: term=' . $searchTerm . ', category=' . $categoryId);

            // Medien suchen - korrigierter Filter
            $where = [];

            // Suchbegriff in Filename und Title
            $searchWildcard = '%' . $searchTerm . '%';
            $where[] = '(m.filename LIKE ? OR m.title LIKE ?)';
            $params = [$searchWildcard, $searchWildcard];

            // Kategorie-Filter
            if ($categoryId > 0) {
                $where[] = 'm.category_id = ?';
                $params[] = $categoryId;
            }

            // Types-Filter (falls vorhanden)
            if (!empty($types)) {
                $typeArray = explode(',', $types);
                $typePlaceholders = str_repeat('?,', count($typeArray) - 1) . '?';
                $where[] = 'm.filename REGEXP ?';
                $params[] = '\.(' . implode('|', array_map('preg_quote', $typeArray)) . ')$';
            }

            // SQL Query aufbauen
            $sql = rex_sql::factory();
            $query = 'SELECT m.* FROM ' . rex::getTable('media') . ' m';
            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }
            $query .= ' ORDER BY m.updatedate DESC LIMIT 10';

            error_log('MediaSearch SQL: ' . $query . ' | Params: ' . json_encode($params));

            $sql->setQuery($query, $params);

            $results = [];
            while ($sql->hasNext()) {
                $media = rex_media::get($sql->getValue('filename'));
                if ($media) {
                    $thumbnail = $this->generateThumbnail($media);

                    // Dateigröße formatieren
                    $size = rex_formatter::bytes($media->getSize());

                    // Update-Datum formatieren
                    $updatedate = rex_formatter::intlDate($media->getValue('updatedate'), 'short');

                    // Actions basierend auf opener_input_field
                    $actions = $this->generateActions($media, $openerInputField);

                    $results[] = [
                        'title' => $media->getTitle() ?: $media->getFilename(),
                        'filename' => $media->getFilename(),
                        'size' => $size,
                        'updatedate' => $updatedate,
                        'thumbnail' => $thumbnail,
                        'actions' => $actions
                    ];
                }
                $sql->next();
            }

            $result = [
                'success' => true,
                'results' => array_slice($results, 0, 10) // Maximal 10 Ergebnisse
            ];

            echo json_encode($result);
            exit;

        } catch (Exception $e) {
            $result = [
                'success' => false,
                'error' => 'Fehler bei der Suche: ' . $e->getMessage()
            ];
            echo json_encode($result);
            exit;
        }
    }

    private function generateThumbnail($media)
    {
        $ext = strtolower($media->getExtension());

        // SVG: read file and return inline SVG markup (sanitized) so MediaManager effects are not used
        if ($ext === 'svg') {
            try {
                $filePath = \rex_path::media($media->getFileName());
                if (is_file($filePath) && is_readable($filePath)) {
                    $svg = (string) @file_get_contents($filePath);
                    if ($svg !== '') {
                        // Basic sanitization: remove <script> tags and on* attributes to avoid JS execution
                        $svg = preg_replace('#<\s*script[^>]*>.*?<\s*/\s*script\s*>#is', '', $svg);
                        // remove javascript: URIs
                        $svg = preg_replace_callback('#(<[^>]+>)#', function ($m) {
                            return preg_replace('#\s(on[a-z]+)\s*=\s*("[^"]*"|' . "'[^']*'" . ')#i', '', $m[1]);
                        }, $svg);
                        // strip xml prolog
                        $svg = preg_replace('/^\s*<\?xml[^>]+>\s*/i', '', $svg);

                        return [
                            'type' => 'svg',
                            'svg' => $svg,
                            'alt' => $media->getTitle() ?: $media->getFilename()
                        ];
                    }
                }
            } catch (Exception $e) {
                // fall through to icon
            }
        }

        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($isImage) {
            try {
                $thumbnailUrl = rex_media_manager::getUrl('rex_mediabutton_preview', $media->getFileName());
                return [
                    'type' => 'image',
                    'src' => $thumbnailUrl,
                    'alt' => $media->getTitle() ?: $media->getFilename()
                ];
            } catch (Exception $e) {
                // Fallback zu Icon
            }
        }

        // Icon basierend auf Dateierweiterung (FontAwesome 6)
        $icon = $this->getFileIcon($media->getExtension());

        return [
            'type' => 'icon',
            'icon' => $icon,
            'title' => strtoupper($media->getExtension()) . ' Datei'
        ];
    }

    private function getFileIcon($extension)
    {
        $iconMap = [
            // Bilder
            'jpg' => 'fa-regular fa-image',
            'jpeg' => 'fa-regular fa-image',
            'png' => 'fa-regular fa-image',
            'gif' => 'fa-regular fa-image',
            'webp' => 'fa-regular fa-image',
            'svg' => 'fa-regular fa-image',
            'bmp' => 'fa-regular fa-image',
            'tiff' => 'fa-regular fa-image',

            // Dokumente
            'pdf' => 'fa-regular fa-file-pdf',
            'doc' => 'fa-regular fa-file-word',
            'docx' => 'fa-regular fa-file-word',
            'xls' => 'fa-regular fa-file-excel',
            'xlsx' => 'fa-regular fa-file-excel',
            'ppt' => 'fa-regular fa-file-powerpoint',
            'pptx' => 'fa-regular fa-file-powerpoint',
            'txt' => 'fa-regular fa-file-lines',
            'rtf' => 'fa-regular fa-file-lines',

            // Archive
            'zip' => 'fa-regular fa-file-zipper',
            'rar' => 'fa-regular fa-file-zipper',
            '7z' => 'fa-regular fa-file-zipper',
            'tar' => 'fa-regular fa-file-zipper',
            'gz' => 'fa-regular fa-file-zipper',

            // Code
            'html' => 'fa-regular fa-file-code',
            'css' => 'fa-regular fa-file-code',
            'js' => 'fa-regular fa-file-code',
            'php' => 'fa-regular fa-file-code',
            'xml' => 'fa-regular fa-file-code',
            'json' => 'fa-regular fa-file-code',

            // Video
            'mp4' => 'fa-regular fa-file-video',
            'avi' => 'fa-regular fa-file-video',
            'mov' => 'fa-regular fa-file-video',
            'wmv' => 'fa-regular fa-file-video',
            'flv' => 'fa-regular fa-file-video',
            'webm' => 'fa-regular fa-file-video',

            // Audio
            'mp3' => 'fa-regular fa-file-audio',
            'wav' => 'fa-regular fa-file-audio',
            'flac' => 'fa-regular fa-file-audio',
            'aac' => 'fa-regular fa-file-audio',
            'ogg' => 'fa-regular fa-file-audio',
        ];

        return $iconMap[strtolower($extension)] ?? 'fa-regular fa-file';
    }

    private function generateActions($media, $openerInputField)
    {
        $actions = [];

        // Edit-Link (Details)
        $actions['edit'] = [
            'url' => rex_url::backendPage('mediapool/media', [
                'opener_input_field' => $openerInputField,
                'file_id' => $media->getId(),
                'file_category_id' => $media->getCategoryId()
            ])
        ];

        // Wenn opener_input_field gesetzt ist, dann Auswahl-Modus
        if (!empty($openerInputField)) {
            if (strpos($openerInputField, '[]') !== false || strpos($openerInputField, 'list') !== false) {
                // Medialist
                $actions['select'] = [
                    'type' => 'medialist',
                    'filename' => $media->getFilename(),
                    'title' => $media->getTitle() ?: $media->getFilename(),
                    'label' => 'Übernehmen'
                ];
            } else {
                // Einzelmedium
                $actions['select'] = [
                    'type' => 'media',
                    'filename' => $media->getFilename(),
                    'title' => $media->getTitle() ?: $media->getFilename(),
                    'label' => 'Übernehmen'
                ];
            }
        }

        return $actions;
    }

    public function requiresCsrfProtection()
    {
        return false;
    }
}
