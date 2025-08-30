$(document).on('rex:ready', function() {
    var root = $("#quick-navigation-structure");
    if (root) {
        $.get(root.data('url')).done(function(quickNav) {
            root.html(quickNav);
            root.find('[data-quick-navigation-toggle="tooltip"]').tooltip();
            $(document).trigger('quick-navigation:ready');
        });
    }
    
    // Mediasort Button Handling
    $("#qn-mediasort-toggle").on('click', function() {
        // Nächsten Sortiermodus setzen (vom PHP-Code übergeben)
        var currentMode = getCookie("media_sort_mode") || "date";
        var nextMode;
        
        switch (currentMode) {
            case 'filename':
                nextMode = 'title';
                break;
            case 'title':
                nextMode = 'date';
                break;
            case 'date':
            default:
                nextMode = 'filename';
                break;
        }
        
        // Cookie setzen
        document.cookie = "media_sort_mode=" + nextMode + "; path=/";
        
        // Seite neu laden
        window.location.reload();
    });
});

// Cookie-Hilfsfunktion
function getCookie(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
}

$(document).on("shown.bs.dropdown", function() {
    quickNavigationFilterInit();
    rex_searchfield_init('#quick-navigation-search');
    var ctype = quickNavigationGetUrlVars()["ctype"];
    if (ctype) {
        $(".quick-navigation-item a").attr('href', function(i, h) {
            return h + (h.indexOf('?') != -1 ? "&ctype=" + ctype : "?ctype=" + ctype);
        });
    }

    $(this).find('.quick-navigation-menu a.quick-navigation-current').focus();

    $('#quick-navigation-search input').delay(200).fadeIn(function() {
        $(this).focus();
    });

});

function quickNavigationFilterInit() {
    $('#quick-navigation-search input').keyup(function() {
        var current_query = $('#quick-navigation-search input').val();
        if (current_query !== "") {
            $('.quick-navigation-menu-body li').hide();
            $('.quick-navigation-menu-body li').each(function() {
                var current_keyword = $(this).text();
                var current_keyword_uppercase = current_keyword.toUpperCase();
                var uppercase = current_query.toUpperCase();

                if ((current_keyword.indexOf(current_query) >= 0) || (current_keyword_uppercase.indexOf(uppercase) >= 0)) {
                    $(this).show();
                }

            });
        } else {
            $('.quick-navigation-menu-body li').show();
        }

        $('.form-clear-button input[type="text"]').on('input propertychange', function() {
            var $this = $(this);
            var visible = Boolean($this.val());
            $this.siblings('.form-control-clear').toggleClass('hidden', !visible);
        }).trigger('propertychange');

        $('.form-control-clear, .clear-button').click(function() {
            event.stopPropagation();
            $(this).siblings('input[type="text"]').val('').trigger("keyup")
                .trigger('propertychange').focus();
        });

    });
}

function quickNavigationGetUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
        vars[key] = value;
    });
    return vars;
}

// Structure Tree Off-Canvas functionality
$(document).on('rex:ready', function() {
    var StructureTree = {
        sidebar: null,
        overlay: null,
        searchInput: null,
        
        init: function() {
            
            // Use vanilla JS to check
            var sidebarElement = document.getElementById('structure-tree-sidebar');
            this.sidebar = $('#structure-tree-sidebar');
            this.trigger = $('.quick-navigation-button[data-target="#structure-tree-sidebar"]');
            this.searchInput = $('.structure-tree-search-input');
            // Force show for testing
            if (this.sidebar.length > 0) {
            }
            setTimeout(function() {
            }, 1000);
            this.bindEvents();
        },
        
        initializeExpandedStates: function() {
            // Alle expanded Items überprüfen und deren direkte Kinder sichtbar machen
            $('.structure-tree-item.expanded').each(function() {
                var $item = $(this);
                var $directChildren = $item.children('.structure-tree-children');
                $directChildren.show(); // Direkt anzeigen ohne Animation beim Laden
            });
        },
        
        bindEvents: function() {
            var self = this;
            
            // Open sidebar
            $(document).on('click', '[data-target="#structure-tree-sidebar"]', function(e) {
                e.preventDefault();
                self.open();
            });
            
            // Close sidebar
            $(document).on('click', '.structure-tree-close, .structure-tree-backdrop', function(e) {
                e.preventDefault();
                self.close();
            });
            
            // ESC key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.sidebar && self.sidebar.is(':visible')) {
                    self.close();
                }
            });
            
            // Tree toggle
            $(document).on('click', '.structure-tree-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleNode($(this));
            });
            
            // Search functionality
            $(document).on('input', '.structure-tree-search-input', function() {
                self.search($(this).val());
            });
            
            // Clear search
            $(document).on('click', '.structure-tree-clear-search', function() {
                self.clearSearch();
            });
            
            // Toggle expand/collapse all
            $(document).on('click', '.structure-tree-expand-toggle', function(e) {
                self.toggleExpandAll();
            });
            
            // Navigate on item click
            $(document).on('click', '.structure-tree-item a', function(e) {
                if (!$(e.target).closest('.structure-tree-toggle').length) {
                    // Navigate to category
                    self.close();
                    // Let the default link behavior handle navigation
                }
            });
        },
        
        open: function() {
            if (this.sidebar) {
                this.sidebar.fadeIn(300);
                $('body').addClass('structure-tree-open');
                
                // Create backdrop if it doesn't exist
                if (!$('.structure-tree-backdrop').length) {
                    $('body').append('<div class="structure-tree-backdrop"></div>');
                }
                $('.structure-tree-backdrop').fadeIn(300);
                
                if (this.searchInput) {
                    this.searchInput.focus();
                }
            }
        },
        
        close: function() {
            if (this.sidebar) {
                this.sidebar.fadeOut(300);
                $('.structure-tree-backdrop').fadeOut(300, function() {
                    $(this).remove();
                });
                $('body').removeClass('structure-tree-open');
            }
        },
        
        toggleNode: function($toggleBtn) {
            var $item = $toggleBtn.closest('.structure-tree-item');
            var $children = $item.children('.structure-tree-children');
            
            if ($item.hasClass('expanded')) {
                $item.removeClass('expanded');
                $children.slideUp(300);
                $toggleBtn.attr('aria-expanded', 'false');
            } else {
                $item.addClass('expanded');
                $children.slideDown(300);
                $toggleBtn.attr('aria-expanded', 'true');
            }
        },
        
        search: function(query) {
            var $items = $('.structure-tree-item');
            
            if (!query.trim()) {
                $items.show();
                $('.structure-tree-clear-search').hide();
                return;
            }
            
            $('.structure-tree-clear-search').show();
            query = query.toLowerCase();
            
            $items.each(function() {
                var $item = $(this);
                var text = $item.find('a').text().toLowerCase();
                
                if (text.indexOf(query) !== -1) {
                    $item.show();
                    // Show parent items
                    $item.parents('.structure-tree-children').show();
                    $item.parents('.structure-tree-item').show();
                } else {
                    $item.hide();
                }
            });
        },
        
        clearSearch: function() {
            if (this.searchInput) {
                this.searchInput.val('');
                this.search('');
                this.searchInput.focus();
            }
        },
        
        toggleExpandAll: function() {
            var $expandableItems = $('.structure-tree-item:has(.structure-tree-children)');
            var $expandedItems = $('.structure-tree-item.expanded');
            var $toggleBtn = $('.structure-tree-expand-toggle');
            var $icon = $toggleBtn.find('i');
            
            // Wenn mehr als die Hälfte expanded ist, collapse all, sonst expand all
            var shouldCollapse = $expandedItems.length > ($expandableItems.length / 2);
            
            if (shouldCollapse) {
                // Collapse All
                $expandedItems.each(function() {
                    var $item = $(this);
                    var $children = $item.children('.structure-tree-children');
                    
                    $item.removeClass('expanded');
                    $children.hide();
                    $item.find('.structure-tree-toggle').attr('aria-expanded', 'false');
                });
                
                // Icon auf "expand" ändern
                $icon.removeClass('fa-compress-arrows-alt').addClass('fa-expand-arrows-alt');
                $toggleBtn.attr('title', 'Alle aufklappen');
                
            } else {
                // Expand All
                $expandableItems.each(function() {
                    var $item = $(this);
                    var $children = $item.children('.structure-tree-children');
                    
                    if (!$item.hasClass('expanded')) {
                        $item.addClass('expanded');
                        $children.show();
                        $item.find('.structure-tree-toggle').attr('aria-expanded', 'true');
                    }
                });
                
                // Icon auf "collapse" ändern
                $icon.removeClass('fa-expand-arrows-alt').addClass('fa-compress-arrows-alt');
                $toggleBtn.attr('title', 'Alle zuklappen');
            }
        }
    };
    
    var structureTreeInitialized = false;
    
    function initializeStructureTree() {
        if (structureTreeInitialized) {
            return;
        }
        
        if ($('#structure-tree-sidebar').length > 0) {
            structureTreeInitialized = true;
            StructureTree.init();
        }
    }
    
    // Initialize Structure Tree when QuickNavigation is ready
    $(document).on('quick-navigation:ready', function() {
        setTimeout(initializeStructureTree, 100);
    });
    
    // Also try to initialize on document ready as fallback
    $(document).ready(function() {
        setTimeout(initializeStructureTree, 500);
    });
});

