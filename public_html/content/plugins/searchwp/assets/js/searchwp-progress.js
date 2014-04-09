jQuery(document).ready(function($){
	var progress = [ -1 ];

	var data = {
		action: 'swp_progress',
		nonce: ajax_object.nonce
	};

	function getProgress() {
		$.post(ajax_object.ajax_url, data, function(response) {
			progress.push(parseFloat(response));
			// check to see if the last 10 progress updates were the same
			if(progress.length>10) {
				var recentProgressUpdates = progress.slice(progress.length-10);
				var uniqueProgressPoints = _.uniq(recentProgressUpdates, false);
				if(uniqueProgressPoints.length==1&&uniqueProgressPoints[0]!==100) {
					$.get('options-general.php?page=searchwp&swpjumpstart', function(data){});
				}
			}
			if(response==100) {
				setTimeout(function(){
					$('.swp-in-progress').addClass('swp-in-progress-done');
				},1000);
			} else {
				last = response;
				$('.swp-in-progress').removeClass('swp-in-progress-done');
				$('.swp-label > span').text(response+'%');
				$('.swp-progress-bar').css('width',response+'%');
			}
			setTimeout(function(){
				getProgress();
			},5000);
		});
	}
	getProgress();
});
