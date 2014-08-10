(function($){
	/*
	var $headers = $('.month-year');
	$(window).scroll(function() {
		var scrollTop = $(window).scrollTop();
		$headers.each(function() {
			var offset = $(this).offset();
			if (offset.top > scrollTop && offset.top < scrollTop < ) {
				$(this).addClass('floating');
			}
			else {
				$(this).removeClass('floating');
			}
		});
	});
	*/

	function UpdateTableHeaders() {
	   $(".month").each(function() {
	   
		   var el             = $(this),
			   offset         = el.offset(),
			   scrollTop      = $(window).scrollTop(),
			   floatingHeader = $(".floatingHeader", this)
		   
		   if ((scrollTop > offset.top) && (scrollTop < offset.top + el.height())) {
			   floatingHeader.css({
				"visibility": "visible"
			   });
		   } else {
			   floatingHeader.css({
				"visibility": "hidden"
			   });      
		   };
	   });
	}

	// DOM Ready      
	$(function() {
		return;

	   var clonedHeaderRow;

	   $(".month").each(function() {
			clonedHeaderRow = $(".month-year", this);
			clonedHeaderRow
				.before(clonedHeaderRow.clone())
				.addClass("floatingHeader");			 
	   });
	   
	   $(window)
		.scroll(UpdateTableHeaders)
		.trigger("scroll");
	   
	});

})(jQuery);