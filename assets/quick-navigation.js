$(document).on('rex:ready', function() {
    var root = $("#quick-navigation-structure");
    if (root) {
        $.get(root.data('url')).done(function(quickNav) {
            root.html(quickNav);
            root.find('[data-quick-navigation-toggle="tooltip"]').tooltip();
            $(document).trigger('quick-navigation:ready');
        });
    }
});


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

