jQuery(document).ready(function($){
	var progress = [ -1 ];

	var data = {
		action: 'swp_progress',
		nonce: ajax_object.nonce
	};

	function getProgress() {
		$.post(ajax_object.ajax_url, data, function(response) {
			progress.push(response);
			// check to see if the last 12 progress updates were the same (i.e. no progress in 60 seconds)
			if(progress.length>12) {
				var recentProgressUpdates = progress.slice(progress.length - 30);
				var uniqueProgressPoints = _.uniq(recentProgressUpdates, false);
				if(uniqueProgressPoints.length==1) {
					$.get('options-general.php?page=searchwp', function(data){});
				}
			}
			if(response==-1) {
				setTimeout(function(){
					$('.swp-in-progress').addClass('swp-in-progress-done');
				},1000);
			} else {
				last = response;
				$('.swp-in-progress').removeClass('swp-in-progress-done');
				$('.swp-label > span').text(response+'%');
				$('.swp-progress-bar').css('width',response+'%');
				setTimeout(function(){
					getProgress();
				},5000);
			}
		});
	}
	getProgress();
});
