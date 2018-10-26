$(document).on('rex:ready', function() {
    var root = $("#rex-quicknavigation-structure");
    if (root) {
        $.post(root.data('url')).done(function(quickNav) {
            root.html(quickNav);
        });
    }
});

$(document).on('rex:ready', function() {
	quicknavi_filter_init();

	var ctype = getUrlVars()["ctype"];
	if (ctype) {
		$(".quicknavi a").attr('href', function(i, h) {
			return h + (h.indexOf('?') != -1 ? "&ctype=" + ctype : "?ctype=" + ctype);
		});
	}

});

$(document).on("shown.bs.dropdown", function() {
	$(this).find(".dropdown-menu li.bg-primary a").focus();
	$(this).find('.dropdown-menu.quicknavi li:first-child input').focus();
});

function quicknavi_filter_init() {
	$('#qsearch').keyup(function() {
		var current_query = $('#qsearch').val();
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
	});
}

function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
		vars[key] = value;
	});
	return vars;
}
