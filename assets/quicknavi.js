$(function () {
    quicknavi_filter_init();
    $(document).on('pjax:end', function() {
       quicknavi_filter_init();
    });
});	


function quicknavi_filter_init() {
	$('#qsearch').keyup(function(){	
		var current_query = $('#qsearch').val();
		if (current_query !== "") {
			$(".quicknavi.list-group li").hide();
			$(".quicknavi.list-group li").each(function(){
				var current_keyword = $(this).text();
				 var upercase = current_query.substr(0,1).toUpperCase() + current_query.substr(1);
			    if ((current_keyword.indexOf(current_query) >=0) ||  (current_keyword.indexOf(upercase) >=0)) {
				$(this).show();    	 	
				};
				
			});    	
		} else {
			$(".quicknavi.list-group li").show();
		};
	});
};
