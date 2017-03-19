$(document).on('rex:ready', function() {
       quicknavi_filter_init();

       var ctype = getUrlVars()["ctype"];
       if (ctype)
       {  
	   $(".quicknavi a").attr('href', function(i, h) {
	     return h + (h.indexOf('?') != -1 ? "&ctype="+ctype : "?ctype="+ctype);
	   		});
       }

});

function quicknavi_filter_init() {
	$('#qsearch').keyup(function(){	
		var current_query = $('#qsearch').val();
		if (current_query !== "") {
			$(".quicknavi.list-group li.quickitem").hide();
			$(".quicknavi.list-group li.quickitem").each(function(){
				var current_keyword = $(this).text();
				 var upercase = current_query.substr(0,1).toUpperCase() + current_query.substr(1);
			    if ((current_keyword.indexOf(current_query) >=0) ||  (current_keyword.indexOf(upercase) >=0)) {
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
var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
vars[key] = value;
});
return vars;
}

$(document).on('shown.bs.dropdown', function(event) {
    var dropdown = $(event.target);
    
    dropdown.find('.dropdown-menu').attr('aria-expanded', true);
    
    setTimeout(function() {
        dropdown.find('.dropdown-menu li:first-child #qsearch').focus();
    }, 10);
});
