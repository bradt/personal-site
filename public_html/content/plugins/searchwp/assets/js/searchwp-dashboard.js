jQuery(document).ready(function($){
	// implement the tabs
	$('.searchwp-dashboard-stats').searchwpTabs();

	// implement table column overflow handling
	var searchwp_resize_columns = function() {
		var searchwp_stat_width = $('.searchwp-stats-segment:first').outerWidth();
		$('.searchwp-stats-segment td div').css('max-width',Math.floor(searchwp_stat_width/2) - 10 );
	};
	searchwp_resize_columns();
	jQuery(window).resize(function(){
		searchwp_resize_columns();
	})
});
