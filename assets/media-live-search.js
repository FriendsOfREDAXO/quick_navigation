/**
 * Quick Navigation Media Live Search
 * Erweitert das bestehende Mediapool-Suchfeld um Live-Suche
 */

$(document).on('rex:ready', function() {
    initQuickNavigationMediaLiveSearch();
});

function initQuickNavigationMediaLiveSearch() {
    // Prüfen ob Live-Search vom User aktiviert ist
    if (typeof rex !== 'undefined' && rex.QUICKNAV_MEDIA_LIVESEARCH_ENABLED === false) {
        return;
    }
    
    // Prüfen ob wir im Mediapool sind
    var isMediapool = $('body').hasClass('rex-page-mediapool') || 
                     $('[data-page="mediapool"]').length > 0 || 
                     window.location.href.indexOf('page=mediapool') !== -1;
    
    if (!isMediapool) {
        return;
    }
    
    var searchInput = $('#be_search-media-name');
    var searchResults = null;
    var searchTimer = null;
    var currentRequest = null;
    
    if (searchInput.length === 0) {
        return;
    }
    
    // Container für Live-Suche Ergebnisse relativ zum bestehenden Suchfeld erstellen
    var resultsContainer = $('<div class="qn-media-live-search-results"></div>');
    var searchForm = searchInput.closest('.form-group, .input-group, form');
    searchForm.css('position', 'relative');
    searchForm.append(resultsContainer);
    
    // Event Handler für Live-Suche (zusätzlich zur normalen Suchfunktion)
    searchInput.on('input.quicknav', function() {
        var searchTerm = $(this).val().trim();
        
        // Timer zurücksetzen
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        
        // Laufende Requests abbrechen
        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }
        
        if (searchTerm.length === 0) {
            resultsContainer.hide();
            return;
        }
        
        if (searchTerm.length < 2) {
            return; // Erst ab 2 Zeichen Live-Suche
        }
        
        // Kurz warten vor der Suche (Debouncing)
        searchTimer = setTimeout(function() {
            performQuickNavMediaSearch(searchTerm, resultsContainer);
        }, 300);
    });
    
    // Klick außerhalb schließt die Ergebnisse
    $(document).on('click.quicknav', function(e) {
        if (!$(e.target).closest('.qn-media-live-search-results, #be_search-media-name').length) {
            resultsContainer.hide();
        }
    });
    
    // ESC schließt die Ergebnisse
    searchInput.on('keydown.quicknav', function(e) {
        if (e.keyCode === 27) { // ESC
            resultsContainer.hide();
        }
    });
    
    function performQuickNavMediaSearch(searchTerm, container) {
        // Loading anzeigen
        container.html('<div class="qn-live-search-loading"><i class="fa fa-spinner fa-spin"></i> Suche läuft...</div>')
               .show();
        
        // Parameter aus der aktuellen Seite holen
        var categoryId = 0; // Live-Suche immer über alle Kategorien hinweg
        var openerInputField = $('input[name="opener_input_field"]').val() || '';
        var types = $('input[name="args[types]"]').val() || '';
        
        // AJAX Request zur Quick Navigation API
        currentRequest = $.ajax({
            url: window.location.pathname + window.location.search,
            method: 'GET',
            data: {
                'rex-api-call': 'quicknavigation_media_search',
                'term': searchTerm,
                'category_id': categoryId,
                'opener_input_field': openerInputField,
                'types': types
            },
            dataType: 'json',
            success: function(response) {
                currentRequest = null;
                
                if (response.success) {
                    displayQuickNavSearchResults(response.results, container);
                } else {
                    container.html('<div class="qn-live-search-no-results">Fehler: ' + qnEscape(response.error || 'Unbekannter Fehler') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                currentRequest = null;
                
                if (status !== 'abort') {
                    container.html('<div class="qn-live-search-no-results">Fehler beim Laden der Suchergebnisse</div>');
                }
            }
        });
    }
}

    // Werte aus der Datenbank (Titel, Dateiname …) immer maskieren
    function qnEscape(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function displayQuickNavSearchResults(results, container) {
        if (results.length === 0) {
            container.html('<div class="qn-live-search-no-results">Keine Ergebnisse gefunden</div>');
            container.show();
            return;
        }

        var html = '';

        $.each(results, function(index, item) {
            var thumbnail = '';
            var thumb = item.thumbnail || {};

            if (thumb.type === 'image') {
                thumbnail = '<img src="' + qnEscape(thumb.src) + '" alt="' + qnEscape(thumb.alt) + '" loading="lazy">';
            } else if (thumb.type === 'icon') {
                thumbnail = '<i class="' + qnEscape(thumb.icon) + '" title="' + qnEscape(thumb.title) + '"></i>';
            } else if (thumb.type === 'error') {
                thumbnail = '<i class="' + qnEscape(thumb.icon) + '" title="' + qnEscape(thumb.title) + '" style="color: #dc3545;"></i>';
            }

            var detailUrl = item.actions && item.actions.edit ? item.actions.edit.url : '';
            var content = '<div class="qn-live-search-item-content">' +
                    '<div class="qn-live-search-thumb">' + thumbnail + '</div>' +
                    '<div class="qn-live-search-info">' +
                        '<div class="qn-live-search-title">' + qnEscape(item.title) + '</div>' +
                        '<div class="qn-live-search-filename">' + qnEscape(item.filename) + '</div>' +
                        '<div class="qn-live-search-meta">' + qnEscape(item.size) + ' • ' + qnEscape(item.updatedate) + '</div>' +
                    '</div>' +
                '</div>';

            if (item.actions && item.actions.select) {
                // Widget-Modus: Übernehmen + Details; Auswahl über data-Attribute (kein Inline-JavaScript)
                var select = item.actions.select;
                html += '<div class="qn-live-search-item">' + content +
                    '<div class="qn-live-search-actions">' +
                        '<button type="button" class="btn btn-xs btn-select qn-media-select"' +
                            ' data-type="' + qnEscape(select.type) + '"' +
                            ' data-filename="' + qnEscape(select.filename) + '"' +
                            ' data-title="' + qnEscape(select.title) + '">' + qnEscape(select.label) + '</button>' +
                        (detailUrl ? '<a href="' + qnEscape(detailUrl) + '" class="btn btn-xs btn-default">Details</a>' : '') +
                    '</div></div>';
            } else if (detailUrl) {
                // Normaler Modus: Komplettes Element als Link zur Detailansicht
                html += '<a href="' + qnEscape(detailUrl) + '" class="qn-live-search-item" style="color: inherit; text-decoration: none;">' + content + '</a>';
            } else {
                // Fallback ohne Link
                html += '<div class="qn-live-search-item">' + content + '</div>';
            }
        });

        container.html(html).show();
    }

    // Übernehmen-Knopf (Medien-Widget): Core-Funktionen selectMedia / selectMedialist des Medienpools
    $(document).off('click.quicknavselect').on('click.quicknavselect', '.qn-media-select', function (e) {
        e.preventDefault();
        var btn = $(this);
        if (btn.attr('data-type') === 'medialist' && typeof window.selectMedialist === 'function') {
            window.selectMedialist(String(btn.attr('data-filename')), this);
        } else if (typeof window.selectMedia === 'function') {
            window.selectMedia(String(btn.attr('data-filename')), String(btn.attr('data-title')));
        }
        btn.closest('.qn-media-live-search-results').hide();
    });