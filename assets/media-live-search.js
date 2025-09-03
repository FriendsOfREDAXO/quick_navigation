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
        var categoryId = $('#rex_file_category').val() || 0;
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
                    container.html('<div class="qn-live-search-no-results">Fehler: ' + (response.error || 'Unbekannter Fehler') + '</div>');
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

    function displayQuickNavSearchResults(results, container) {
        if (results.length === 0) {
            container.html('<div class="qn-live-search-no-results">Keine Ergebnisse gefunden</div>');
            container.show();
            return;
        }
        
        var html = '';
        
        $.each(results, function(index, item) {
            var thumbnail = '';
            
            if (item.thumbnail.type === 'image') {
                thumbnail = '<img src="' + item.thumbnail.src + '" alt="' + item.thumbnail.alt + '" loading="lazy">';
            } else if (item.thumbnail.type === 'icon') {
                thumbnail = '<i class="' + item.thumbnail.icon + '" title="' + item.thumbnail.title + '"></i>';
            } else if (item.thumbnail.type === 'error') {
                thumbnail = '<i class="' + item.thumbnail.icon + '" title="' + item.thumbnail.title + '" style="color: #dc3545;"></i>';
            }
            
            var detailUrl = '';
            if (item.actions.edit) {
                detailUrl = item.actions.edit.url;
            }
            
            if (item.actions.select) {
                // Widget-Modus: Buttons untereinander anzeigen
                html += '<div class="qn-live-search-item">' +
                           '<div class="qn-live-search-item-content">' +
                               '<div class="qn-live-search-thumb">' + thumbnail + '</div>' +
                               '<div class="qn-live-search-info">' +
                                   '<div class="qn-live-search-title">' + item.title + '</div>' +
                                   '<div class="qn-live-search-filename">' + item.filename + '</div>' +
                                   '<div class="qn-live-search-meta">' + item.size + ' • ' + item.updatedate + '</div>' +
                               '</div>' +
                           '</div>' +
                           '<div class="qn-live-search-actions">';
                
                // Übernehmen-Button
                html += '<button type="button" class="btn btn-xs btn-select" onclick="';
                
                if (item.actions.select.type === 'media') {
                    html += 'selectMedia(\'' + item.actions.select.filename + '\', \'' + item.actions.select.title + '\'); $(this).closest(\'.qn-media-live-search-results\').hide(); return false;';
                } else if (item.actions.select.type === 'medialist') {
                    html += 'selectMedialist(\'' + item.actions.select.filename + '\', this); $(this).closest(\'.qn-media-live-search-results\').hide(); return false;';
                }
                
                html += '">' + item.actions.select.label + '</button>';
                
                // Details-Button
                if (detailUrl) {
                    html += '<a href="' + detailUrl + '" class="btn btn-xs btn-default">Details</a>';
                }
                
                html += '</div></div>';
                
            } else if (detailUrl) {
                // Normaler Modus: Komplettes Element als Link zur Detailansicht
                html += '<a href="' + detailUrl + '" class="qn-live-search-item" style="color: inherit; text-decoration: none;">' +
                           '<div class="qn-live-search-item-content">' +
                               '<div class="qn-live-search-thumb">' + thumbnail + '</div>' +
                               '<div class="qn-live-search-info">' +
                                   '<div class="qn-live-search-title">' + item.title + '</div>' +
                                   '<div class="qn-live-search-filename">' + item.filename + '</div>' +
                                   '<div class="qn-live-search-meta">' + item.size + ' • ' + item.updatedate + '</div>' +
                               '</div>' +
                           '</div>' +
                        '</a>';
            } else {
                // Fallback ohne Link
                html += '<div class="qn-live-search-item">' +
                           '<div class="qn-live-search-item-content">' +
                               '<div class="qn-live-search-thumb">' + thumbnail + '</div>' +
                               '<div class="qn-live-search-info">' +
                                   '<div class="qn-live-search-title">' + item.title + '</div>' +
                                   '<div class="qn-live-search-filename">' + item.filename + '</div>' +
                                   '<div class="qn-live-search-meta">' + item.size + ' • ' + item.updatedate + '</div>' +
                               '</div>' +
                           '</div>' +
                        '</div>';
            }
        });
        
        container.html(html).show();
    }