$(document).ready(function() {
	bradt_load_js_vars();
	Bradt.init();
});

var Bradt = {
	template_url : '',
	
	init: function() {
		Bradt.menu();
		Bradt.portfolio.init();
		Bradt.portfolio_list.init();
		Bradt.contact.init();
		Bradt.about.init();
		Bradt.photos.init();
		
		$('.active-plugins .plugin').gridify();
	},

	photos: {
		init: function() {
			if (!$('#content.photos').get(0)) return;
			
			$('.photo-set').gridify();
		}
	},
	
	about: {
		init: function() {
			if (!$('.page-about').get(0)) return;
		
			$('.more-history').before('<a href="" class="more-history-btn">Show more work history...</a>');
			$('.more-history-btn').click(function() {
				$('.more-history').fadeIn();
				$(this).remove();
				return false;
			});
		}
	},
	
	contact: {
		init: function() {
			if (!$('.page-contact').get(0)) return;
			
			var init_events = function() {
				
				/*
				$('.field-budget').after($('.section-budget'));
				
				if ($('input[name=what]:checked').val() != 'work') {
					$('.field-budget, .field-schedule').hide();
				}

				$('input[name=budget]').change(function() {
					var val = $(this).val();
					switch (val) {
						case 'too_low':
							$('#message-details, .button, .section, .field-schedule').hide();
							$('.section-budget').show();
							break;
						default:
							$('.section').hide();
							$('#message-details, .button, .field-schedule').show();
					}
				});
	
				$('input[name=schedule]').change(function() {
					var val = $(this).val();
					switch (val) {
						case '1-4':
							$('#message-details, .button, .section').hide();
							$('.section-schedule').show();
							break;
						default:
							$('.section').hide();
							$('#message-details, .button').show();
					}
				});
				*/
				
				$('input[name=what]').change(function() {
					$('.error').hide();
					var val = $(this).val();
					switch (val) {
						case 'plugin':
						case 'personal':
						case 'work':
							//$('#message-details, .button, .section, .field-budget, .field-schedule').hide();
							$('#message-details, .button, .section').hide();
							$('.section-' + val).show();
							break;
						/*
						case 'work':
							$('.section').hide();
							$('#message-details, .button, .field-budget, .field-schedule').show();
							break;
						*/
						case 'wpappstore':
						case 'general':
							//$('.section, .field-budget, .field-schedule').hide();
							$('.section').hide();
							$('#message-details, .button').show();
							break;
						default:
							$('#message-details, .button').hide();
					}
				});

				$('li.option-bot').hide();
			};
			
			init_events();
			
			$('input[name=what]').trigger('change');
			
			$('form').submit(function() {
				var form = $(this);
				var data = form.serialize();
				var url = form.attr('action') + '?ajax=1';
				$.post(url, data, function(data) {
					form.html(data);
					init_events();
					if ($('p.error-msg').get(0)) {
						$.scrollTo('.indicates', 500);
						$('p.error, p.error-msg').hide().fadeIn();
					}
				});
				
				return false;
			});
		}
	},
	
	menu: function() {
		$('#header ul.nav li a').click(function() {
			$('#header ul.nav li a').removeClass('active');
			$(this).addClass('active');
		});
	},

	portfolio_list: {
		resizing: 0,
	
		init: function() {
			if (!$('.page-portfolio-list').get(0))
				return;

			Bradt.portfolio.roles.init();
			
			$('.project').gridify();

			setTimeout(Bradt.portfolio_list.resize, 1000);
			
			$(window).resize(function() {
				Bradt.portfolio_list.resizing = 1;
			});
			
			$('.project').each(function() {
				$(this).hover(function() {
					$(this).stop().animate({"opacity": "0.7"}, 200);
				},
				function() {
					$(this).stop().animate({"opacity": "1"}, 200);
				});
			});
		},
		
		resize: function() {
			if (Bradt.portfolio_list.resizing) {
				$('.project').css('height', 'auto').gridify();
				Bradt.portfolio_list.resizing = 0;
			}
			setTimeout(Bradt.portfolio_list.resize, 1000);
		}
		
	},
	
	portfolio: {
	
		init: function() {
			if (!$('.page-portfolio').get(0))
				return;
			
			$.localScroll.hash();
			
			var img = new Image();
			img.src = Bradt.template_url + '/images/indicator.gif';

			var scr = $('.scr > a');
		
			$('ul.screenshots li a').click(function() {
				var anchor = $(this);
				
				$('ul.screenshots li a').removeClass('current');
				anchor.addClass('current');

				if (!$('.loading', scr).get(0)) {
					scr.append('<div class="loading"></div><img src="' + Bradt.template_url + '/images/indicator.gif" width="16" height="16" alt="Loading..." class="loading" />');
				}
				else {
					$('.loading', scr).show();
				}
				
				$('.loading', scr).each(function() {
					var ld = $(this);
					var top = (scr.height()/2) - (ld.height()/2);
					var left = scr.width()/2 - (ld.width()/2);
					ld.css('top', top + 'px');
					ld.css('left', left + 'px');
				});
				
				var img = new Image();
				$(img).load(function() {
					$('.loading', scr).hide();
					
					if (img.height > scr.height()) {
						scr.css('background-image', 'url(' + anchor.attr('href') + ')');
						scr.animate({ height: img.height });
						$('.project').css('max-width', img.width + 'px');
					}
					else {
						scr.animate({ height: img.height }, function() {
							scr.css('background-image', 'url(' + anchor.attr('href') + ')');
							$('.project').css('max-width', img.width + 'px');
						});
					}

					var url = anchor.attr('href').replace(/\-[0-9]+x[0-9]+\.jpg/, '.jpg');
					scr.attr('href', url);
					
					$.scrollTo('.scr', 500, {'offset' : {'top' : -20}});
			   });
			   img.src = anchor.attr('href');
	
			   return false;
			});
		
			// Preload screenshots
			$('ul.screenshots li a').each(function() {
				var img = new Image();
				img.src = $(this).attr('href');
			});
		},
		
		roles: {
			
			init: function() {
				$('.tabs li').click(function() {
					var selected = $(this).attr('class');
					
					$('.tabs li a').removeClass('current');
					$('a', this).addClass('current');
					
					if (selected == 'all') {
						$('.project')
							.removeClass('hidden')
							.show()
							.css('height', 'auto')
							.gridify();
						$('.old-portfolio').show();
						return;
					}
					else {
						$('.old-portfolio').hide();
					}
					
					$('.project').addClass('hidden').hide();

					if (selected == 'featured') {
						$('.project.featured')
							.removeClass('hidden')
							.show()
							.css('height', 'auto')
							.gridify();
						return;
					}
					
					$('.project').each(function() {
						var found = 0;
						$('.roles li span', this).each(function() {
							var txt = $(this).text().toLowerCase();
							txt = txt.replace(/[^a-z0-9]/, '-');
							if (txt == selected) {
								found = 1;
							}
						});
						
						if (found) {
							$(this).removeClass('hidden').show();
						}
					});
					
					$('.project').css('height', 'auto').gridify();
				});
				
				var hash = document.location.hash;
				if (hash) {
					hash = hash.replace('#', '');
					var tab = $('.tabs li.' + hash).get(0);
					if (tab) {
						$(tab).trigger('click');
					}
				}
				else {
					$('.tabs li:first').trigger('click');					
				}
			}
		}
	}
};



(function($){
    
    $.fn.gridify = function(per_row) {
		var items = this.not('.hidden');
		
        if (!per_row && items.size()) {
            var container = items.parent();
            var item = items.eq(0);
            per_row = Math.floor(container.width() / item.outerWidth());
        }
        
		var row_num = 1;
        return items.each(function(i) {
            if ((i+1) % per_row == 0) {
                var max_height = 0;
                var el = $(this);
                for (var x = 1; x <= per_row; x++) {
                    var h = el.height();
                    if (h > max_height)
                        max_height = h;
                    el = el.prev();
					while (el.hasClass('hidden')) {
						el = el.prev();
					}
                }
                
                var el = $(this);
                for (var x = 1; x <= per_row; x++) {
                    el.height(max_height);
                    el = el.prev();
					while (el.hasClass('hidden')) {
						el = el.prev();
					}
                }
				
				row_num++;
            }
        });
    };
    
})(jQuery);
