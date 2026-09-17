/**
 * Quick Navigation YForm Spotlight
 * Global overlay (opened from the header "YForm" button) combining the
 * per-table list with a live dataset search across permitted YForm tables,
 * plus optional table-scoping and field-type-aware filters.
 */
$(document).on('rex:ready quick-navigation:ready', function () {
    initQuickNavigationYformOverlay();
});

function initQuickNavigationYformOverlay() {
    var trigger = $('#quick-navigation-yform-trigger');
    var overlay = $('#quick-navigation-yform-overlay');

    if (trigger.length === 0 || overlay.length === 0 || overlay.data('qn-yform-initialized')) {
        return;
    }
    overlay.data('qn-yform-initialized', true);

    var input = overlay.find('#quick-navigation-yform-input');
    var tablesPane = overlay.find('.qn-yform-overlay-tables');
    var resultsPane = overlay.find('.qn-yform-overlay-results');
    var tableSelect = overlay.find('#quick-navigation-yform-table-select');
    var filtersToggleBtn = overlay.find('#quick-navigation-yform-filters-toggle');
    var filtersPane = overlay.find('#quick-navigation-yform-filters');
    var initialTable = overlay.data('current-table') || '';

    if (tableSelect.length && typeof tableSelect.selectpicker === 'function') {
        tableSelect.selectpicker({ container: 'body' });
    }

    var scopedTable = '';
    var activeFilters = {};
    var searchTimer = null;
    var currentRequest = null;
    var currentFiltersRequest = null;

    trigger.on('click', function (e) {
        e.preventDefault();
        openOverlay();
    });

    overlay.on('click', '[data-qn-yform-close]', function (e) {
        e.preventDefault();
        closeOverlay();
    });

    $(document).on('keydown.qnyformoverlay', function (e) {
        if (overlay.prop('hidden')) {
            return;
        }
        if (e.keyCode === 27) {
            closeOverlay();
        }
    });

    // Arrow-key/Tab navigation between the search input and the currently
    // visible list (tables or results), Spotlight-style.
    input.on('keydown.qnyformnav', function (e) {
        var activeList = tablesPane.prop('hidden') ? resultsPane : tablesPane;
        var items = activeList.find('a.qn-yform-overlay-table-link, a.qn-yform-search-item');

        if ((e.key === 'Tab' && !e.shiftKey) || e.key === 'ArrowDown') {
            if (items.length) {
                e.preventDefault();
                items.first().trigger('focus');
            }
        } else if (e.key === 'Enter') {
            if (items.length) {
                e.preventDefault();
                window.location.href = items.first().attr('href');
            }
        }
    });

    overlay.on('keydown.qnyformnav', 'a.qn-yform-overlay-table-link, a.qn-yform-search-item', function (e) {
        var items = $(this).closest('.qn-yform-overlay-tables, .qn-yform-overlay-results')
            .find('a.qn-yform-overlay-table-link, a.qn-yform-search-item');
        var index = items.index(this);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items.eq(Math.min(index + 1, items.length - 1)).trigger('focus');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (index === 0) {
                input.trigger('focus');
            } else {
                items.eq(index - 1).trigger('focus');
            }
        } else if (e.key === 'Tab' && e.shiftKey && index === 0) {
            e.preventDefault();
            input.trigger('focus');
        }
    });

    input.on('input', function () {
        var term = $(this).val().trim();

        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        abortSearch();

        if (term.length === 0 && isFiltersEmpty()) {
            showTables();
            return;
        }

        if (term.length > 0 && term.length < 2) {
            return;
        }

        showLoading();
        searchTimer = setTimeout(function () {
            performSearch(term);
        }, 300);
    });

    // Table-name click: normal navigation (unchanged behaviour).
    // The dedicated magnifier button next to it scopes the search instead
    // (and keeps the always-visible table select in sync).
    overlay.on('click', '[data-quick-navigation-yform-scope]', function (e) {
        e.preventDefault();
        var tableName = $(this).data('quick-navigation-yform-scope');
        setTableSelectValue(tableName);
        setScope(tableName);
        input.trigger('focus');
    });

    tableSelect.on('change', function () {
        setScope($(this).val() || '');
        input.trigger('focus');
    });

    filtersToggleBtn.on('click', function (e) {
        e.preventDefault();
        filtersPane.prop('hidden', !filtersPane.prop('hidden'));
        filtersToggleBtn.toggleClass('active', !filtersPane.prop('hidden'));
    });

    filtersPane.on('change', 'input, select', function () {
        var name = $(this).data('filter-name');
        var value = $(this).val();
        if (value) {
            activeFilters[name] = value;
        } else {
            delete activeFilters[name];
        }
        var term = input.val().trim();
        if (term.length >= 2 || !isFiltersEmpty()) {
            showLoading();
            performSearch(term);
        } else {
            showTables();
        }
    });

    function isFiltersEmpty() {
        return Object.keys(activeFilters).length === 0;
    }

    function openOverlay() {
        overlay.prop('hidden', false);
        input.val('');
        setTableSelectValue(initialTable);
        setScope(initialTable);
        setTimeout(function () {
            input.trigger('focus');
        }, 30);
    }

    function closeOverlay() {
        overlay.prop('hidden', true);
    }

    function setTableSelectValue(tableName) {
        tableSelect.val(tableName || '');
        if (typeof tableSelect.selectpicker === 'function') {
            tableSelect.selectpicker('refresh');
        }
    }

    function setScope(tableName) {
        scopedTable = tableName || '';
        activeFilters = {};
        filtersPane.prop('hidden', true).empty();
        filtersToggleBtn.removeClass('active');

        if (scopedTable) {
            filtersToggleBtn.removeClass('hidden');
            loadFilters(scopedTable);
        } else {
            filtersToggleBtn.addClass('hidden');
        }

        var term = input.val().trim();
        if (term.length >= 2) {
            showLoading();
            performSearch(term);
        } else {
            showTables();
        }
    }

    function showTables() {
        abortSearch();
        resultsPane.prop('hidden', true).empty();
        tablesPane.prop('hidden', false);
    }

    function showLoading() {
        tablesPane.prop('hidden', true);
        resultsPane.prop('hidden', false).html('<div class="qn-yform-search-loading"><i class="fa fa-spinner fa-spin"></i></div>');
    }

    function abortSearch() {
        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }
    }

    function loadFilters(tableName) {
        if (currentFiltersRequest) {
            currentFiltersRequest.abort();
        }
        currentFiltersRequest = $.ajax({
            url: window.location.pathname + window.location.search,
            method: 'GET',
            cache: false,
            data: {
                'rex-api-call': 'quicknavigation_yform_search',
                mode: 'filters',
                table_name: tableName
            },
            dataType: 'json',
            success: function (response) {
                currentFiltersRequest = null;
                renderFilters(response.filters || []);
            },
            error: function (xhr, status) {
                currentFiltersRequest = null;
            }
        });
    }

    function renderFilters(filters) {
        if (!filters || filters.length === 0) {
            filtersPane.html('<div class="qn-yform-filters-empty">Keine Filter verfügbar</div>');
            return;
        }

        var html = '';
        filters.forEach(function (f) {
            html += '<div class="qn-yform-filter-field form-group">';
            html += '<label class="control-label">' + escapeHtml(f.label) + '</label>';

            if (f.input === 'select') {
                html += '<select class="form-control" data-filter-name="' + escapeHtml(f.name) + '"><option value="">' + '—' + '</option>';
                Object.keys(f.options || {}).forEach(function (value) {
                    html += '<option value="' + escapeHtml(value) + '">' + escapeHtml(f.options[value]) + '</option>';
                });
                html += '</select>';
            } else if (f.input === 'date' || f.input === 'datetime') {
                html += '<input class="form-control" type="' + (f.input === 'datetime' ? 'datetime-local' : 'date') + '" data-filter-name="' + escapeHtml(f.name) + '">';
            } else if (f.input === 'number') {
                html += '<input class="form-control" type="number" data-filter-name="' + escapeHtml(f.name) + '">';
            } else {
                html += '<input class="form-control" type="text" data-filter-name="' + escapeHtml(f.name) + '">';
            }

            html += '</div>';
        });

        filtersPane.html(html);
    }

    function performSearch(term) {
        abortSearch();
        currentRequest = $.ajax({
            url: window.location.pathname + window.location.search,
            method: 'GET',
            cache: false,
            data: {
                'rex-api-call': 'quicknavigation_yform_search',
                term: term,
                table_name: scopedTable || '',
                filters: activeFilters
            },
            dataType: 'json',
            success: function (response) {
                currentRequest = null;
                if (response.success) {
                    renderResults(response.groups);
                } else {
                    resultsPane.html('<div class="qn-yform-search-empty">' + escapeHtml(response.error || 'Fehler') + '</div>');
                }
            },
            error: function (xhr, status) {
                currentRequest = null;
                if (status !== 'abort') {
                    resultsPane.html('<div class="qn-yform-search-empty">Fehler bei der Suche</div>');
                }
            }
        });
    }

    function renderResults(groups) {
        if (!groups || groups.length === 0) {
            resultsPane.html('<div class="qn-yform-search-empty"><i class="fa fa-ghost" aria-hidden="true"></i> Keine Treffer</div>');
            return;
        }

        var html = '';
        groups.forEach(function (group) {
            html += '<div class="qn-yform-search-group">';
            html += '<div class="qn-yform-search-group-label">' + escapeHtml(group.table_label) + '</div>';
            group.results.forEach(function (item) {
                // Only plain text-like highlighted fields (name/title/...) become the
                // bold title line — a highlighted choice/boolean/tag field is still
                // shown, but as a normal row below, not glued onto the title text.
                var titleFields = (item.preview || []).filter(function (f) { return f.highlighted && f.kind === 'text'; });
                var rest = (item.preview || []).filter(function (f) { return !(f.highlighted && f.kind === 'text'); });
                var titleHtml = renderFieldValue(titleFields);

                html += '<a class="qn-yform-search-item" href="' + item.url + '">';
                html += '<div class="qn-yform-search-item-head">';
                if (titleHtml) {
                    html += '<span class="qn-yform-search-title">' + titleHtml + '</span>';
                }
                html += '<span class="qn-yform-search-id">#' + item.id + '</span>';
                html += '</div>';
                if (rest.length) {
                    html += '<div class="qn-yform-search-preview">' + renderPreviewFields(rest) + '</div>';
                }
                html += '</a>';
            });
            html += '</div>';
        });

        resultsPane.html(html);
    }

    function renderFieldValue(fields) {
        return fields.map(function (field) {
            return renderValueOnly(field);
        }).filter(Boolean).join(' · ');
    }

    function renderValueOnly(field) {
        if (field.kind === 'boolean') {
            return field.on
                ? '<span class="qn-badge qn-badge-on"><i class="fa fa-circle-check" aria-hidden="true"></i> Ja</span>'
                : '<span class="qn-badge qn-badge-off"><i class="fa fa-circle-xmark" aria-hidden="true"></i> Nein</span>';
        }
        if (field.kind === 'lang_json') {
            return '<div class="qn-lang-stack">' + (field.languages || []).map(function (l) {
                return '<div class="qn-lang-chip"><span class="qn-lang-code">' + escapeHtml(l.lang_label) + '</span><span class="qn-lang-text">' + escapeHtml(l.value) + '</span></div>';
            }).join('') + '</div>';
        }
        if (field.kind === 'choice') {
            return '<span class="qn-badge qn-badge-choice">' + escapeHtml(field.text) + '</span>';
        }
        if (field.kind === 'tags') {
            return (field.tags || []).map(function (t) {
                var color = /^#[0-9a-fA-F]{3,8}$/.test(t.color) ? t.color : '#7f8c8d';
                return '<span class="qn-badge qn-badge-tag" style="background:' + color + '">' + escapeHtml(t.text) + '</span>';
            }).join(' ');
        }
        return escapeHtml(field.text || '');
    }

    function renderPreviewFields(preview) {
        if (!preview || preview.length === 0) {
            return '';
        }

        var rows = preview.map(function (field) {
            return '<div class="qn-yform-search-field-label">' + escapeHtml(field.label) + '</div>'
                + '<div class="qn-yform-search-field-value">' + renderValueOnly(field) + '</div>';
        }).join('');

        return '<div class="qn-yform-search-field-grid">' + rows + '</div>';
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }
}
