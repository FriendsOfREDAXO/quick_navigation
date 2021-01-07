$(document).on('rex:ready', function() {
    var root = $("#rex-quicknavigation-structure");
    if (root) {
        $.get(root.data('url')).done(function(quickNav) {
            root.html(quickNav);
        });
    }
});


$(document).on("shown.bs.dropdown", function() {
    quicknavi_filter_init();
    searchfield_init('#qsearch');
    var ctype = getUrlVars()["ctype"];
    if (ctype) {
        $(".quicknavi a").attr('href', function(i, h) {
            return h + (h.indexOf('?') != -1 ? "&ctype=" + ctype : "?ctype=" + ctype);
        });
    }

    $(this).find(".dropdown-menu li.bg-primary a").focus();

    $('#qsearch input').delay(200).fadeIn(function() {
        $('.dropdown-menu.quicknavi li:first-child input').focus();
    });

});

function quicknavi_filter_init() {
    $('#qsearch input').keyup(function() {
        var current_query = $('#qsearch input').val();
        if (current_query !== "") {
            $(".quicknavi.list-group li.quickitem").hide();
            $(".quicknavi.list-group li.quickitem").each(function() {
                var current_keyword = $(this).text();
                var current_keyword_uppercase = current_keyword.toUpperCase();
                var uppercase = current_query.toUpperCase();

                if ((current_keyword.indexOf(current_query) >= 0) || (current_keyword_uppercase.indexOf(uppercase) >= 0)) {
                    $(this).show();
                }

            });
        } else {
            $(".quicknavi.list-group li.quickitem").show();
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

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
        vars[key] = value;
    });
    return vars;
}

