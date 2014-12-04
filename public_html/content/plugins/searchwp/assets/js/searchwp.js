var searchwp_settings_handler = function(){
	/* ============================================================
	 * bootstrap-dropdown.js v2.3.2
	 * http://getbootstrap.com/2.3.2/javascript.html#dropdowns
	 * ============================================================
	 * Copyright 2013 Twitter, Inc.
	 *
	 * Licensed under the Apache License, Version 2.0 (the "License");
	 * you may not use this file except in compliance with the License.
	 * You may obtain a copy of the License at
	 *
	 * http://www.apache.org/licenses/LICENSE-2.0
	 *
	 * Unless required by applicable law or agreed to in writing, software
	 * distributed under the License is distributed on an "AS IS" BASIS,
	 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 * See the License for the specific language governing permissions and
	 * limitations under the License.
	 * ============================================================ */


	!function ($) {

		"use strict"; // jshint ;_;


		/* DROPDOWN CLASS DEFINITION
		 * ========================= */

		var toggle = '[data-toggle=dropdown]'
				, Dropdown = function (element) {
					var $el = $(element).on('click.dropdown.data-api', this.toggle)
					$('html').on('click.dropdown.data-api', function () {
						$el.parent().removeClass('swp-open')
					})
				}

		Dropdown.prototype = {

			constructor: Dropdown

			, toggle: function (e) {
				var $this = $(this)
						, $parent
						, isActive

				if ($this.is('.disabled, :disabled')) return

				$parent = getParent($this)

				isActive = $parent.hasClass('swp-open')

				clearMenus()

				if (!isActive) {
					if ('ontouchstart' in document.documentElement) {
						// if mobile we we use a backdrop because click events don't delegate
						$('<div class="dropdown-backdrop"/>').insertBefore($(this)).on('click', clearMenus)
					}
					$parent.toggleClass('swp-open')
				}

				$this.focus()

				return false
			}

			, keydown: function (e) {
				var $this
						, $items
						, $active
						, $parent
						, isActive
						, index

				if (!/(38|40|27)/.test(e.keyCode)) return

				$this = $(this)

				e.preventDefault()
				e.stopPropagation()

				if ($this.is('.disabled, :disabled')) return

				$parent = getParent($this)

				isActive = $parent.hasClass('swp-open')

				if (!isActive || (isActive && e.keyCode == 27)) {
					if (e.which == 27) $parent.find(toggle).focus()
					return $this.click()
				}

				$items = $('[role=menu] li:not(.divider):visible a', $parent)

				if (!$items.length) return

				index = $items.index($items.filter(':focus'))

				if (e.keyCode == 38 && index > 0) index--                                        // up
				if (e.keyCode == 40 && index < $items.length - 1) index++                        // down
				if (!~index) index = 0

				$items
						.eq(index)
						.focus()
			}

		}

		function clearMenus() {
			$('.dropdown-backdrop').remove()
			$(toggle).each(function () {
				getParent($(this)).removeClass('swp-open')
			})
		}

		function getParent($this) {
			var selector = $this.attr('data-target')
					, $parent

			if (!selector) {
				selector = $this.attr('href')
				selector = selector && /#/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') //strip for ie7
			}

			$parent = selector && $(selector)

			if (!$parent || !$parent.length) $parent = $this.parent()

			return $parent
		}


		/* DROPDOWN PLUGIN DEFINITION
		 * ========================== */

		var old = $.fn.dropdown

		$.fn.dropdown = function (option) {
			return this.each(function () {
				var $this = $(this)
						, data = $this.data('dropdown')
				if (!data) $this.data('dropdown', (data = new Dropdown(this)))
				if (typeof option == 'string') data[option].call($this)
			})
		}

		$.fn.dropdown.Constructor = Dropdown


		/* DROPDOWN NO CONFLICT
		 * ==================== */

		$.fn.dropdown.noConflict = function () {
			$.fn.dropdown = old
			return this
		}


		/* APPLY TO STANDARD DROPDOWN ELEMENTS
		 * =================================== */

		$(document)
				.on('click.dropdown.data-api', clearMenus)
				.on('click.dropdown.data-api', '.dropdown form', function (e) { e.stopPropagation() })
				.on('click.dropdown.data-api'  , toggle, Dropdown.prototype.toggle)
				.on('keydown.dropdown.data-api', toggle + ', [role=menu]' , Dropdown.prototype.keydown)

	}(window.jQuery);

	(function($){

		var uniqid = function (prefix, more_entropy) {
			// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +    revised by: Kankrelune (http://www.webfaktory.info/)
			// %        note 1: Uses an internal counter (in php_js global) to avoid collision
			// *     example 1: uniqid();
			// *     returns 1: 'a30285b160c14'
			// *     example 2: uniqid('foo');
			// *     returns 2: 'fooa30285b1cd361'
			// *     example 3: uniqid('bar', true);
			// *     returns 3: 'bara20285b23dfd1.31879087'
			if (typeof prefix === 'undefined') {
				prefix = "";
			}

			var retId;
			var formatSeed = function (seed, reqWidth) {
				seed = parseInt(seed, 10).toString(16); // to hex str
				if (reqWidth < seed.length) { // so long we split
					return seed.slice(seed.length - reqWidth);
				}
				if (reqWidth > seed.length) { // so short we pad
					return new Array(1 + (reqWidth - seed.length)).join('0') + seed;
				}
				return seed;
			};

			// BEGIN REDUNDANT
			if (!this.php_js) {
				this.php_js = {};
			}
			// END REDUNDANT
			if (!this.php_js.uniqidSeed) { // init seed with big random int
				this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
			}
			this.php_js.uniqidSeed++;

			retId = prefix; // start with prefix, add current milliseconds hex string
			retId += formatSeed(parseInt(new Date().getTime() / 1000, 10), 8);
			retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
			if (more_entropy) {
				// for more entropy we add a float lower to 10
				retId += (Math.random() * 10).toFixed(8).toString();
			}

			return retId;
		};

		$(document).tooltip({
			items: ".swp-tooltip,.swp-tooltip-alt",
			content: function(){
				return $($(this).attr('href')).html();
			}
		});

		var excludeSelects = function() {
			$('select.swp-exclude-select').each(function(){
				$(this).select2({
					placeholder: $(this).data('placeholder')
				});
			});
		};

		excludeSelects();

		var customFieldSelects = function() {
			$('.swp-custom-field-select select').each(function(){
				$(this).select2({});
			});
		};

		customFieldSelects();

		var updateTabContentHeights = function( $parent ){
			// first check to make sure the tabs don't exceed the height
			var $parent_tab_content = $parent.find('.swp-tab-content');
			var $parent_tab_pane = $parent_tab_content.find('.swp-tab-pane');
			var $first_tab_pane = $parent_tab_pane.first();
			var $parent_nav = $parent.find('.swp-nav');
			if($parent_nav.height()>$first_tab_pane.height()){
				$first_tab_pane.height($parent_nav.height());
			}

			// make sure our tab content is at least the proper height
			// while doing that, hide each tab pane
			var tallest = 0;
			$parent_tab_pane.each(function(){
				if($(this).height()>tallest){
					tallest = $(this).height();
				}
			});
			$parent_tab_content.height(tallest+50);
		};

		var initTabs = function( $grandparent ){
			$grandparent .find('.swp-tabbable').each(function(){

				var $parent = $(this);

				// prevent clicking labels from toggling the checkbox
				$parent.find('.swp-tabs label').unbind('click').click(function(e){
					e.preventDefault();
				});

				updateTabContentHeights($parent);
				$parent.find('.swp-tab-content .swp-tab-pane').hide();

				// hook the clicks
				$parent.find('.swp-tabs > li').click(function(){
					$parent.find('.swp-tabs > li.swp-tab-active').removeClass('swp-tab-active');
					$parent.find('.swp-tab-content .swp-tab-pane').hide();
					$(this).addClass('swp-tab-active');
					$('#'+$(this).data('swp-engine')).show();
				});

				// make sure the first tab is active
				if(!$parent.find('.swp-tabs .swp-tab-active').length){
					$parent.find('.swp-tabs > li:eq(0)').trigger('click');
				}

			});
		};

		initTabs( $('.swp-default-engine') );
		$('.swp-supplemental-engine').each(function(){
			initTabs( $(this) );
		});

		var $body = $('body');

		// allow addition of custom fields
		$body.on('click','a.swp-add-custom-field', function(){
			_.templateSettings = {
				variable : 'swp',
				interpolate : /\{\{(.+?)\}\}/g
			};

			var template = _.template($('script#tmpl-swp-custom-fields').html());

			var swp = {
				arrayFlag: uniqid( 'swp' ),
				postType: $(this).data('posttype'),
				engine: $(this).data('engine')
			};

			$(this).parents('tbody').find('tr:last').before(template(swp));

			// apply select2
			$(this).parents('tbody').find('.swp-custom-field:last .swp-custom-field-select select').select2({});

			updateTabContentHeights($(this).parents('.swp-tabbable'));

			return false;
		});

		$body.on('click','.swp-delete',function(){
			$(this).parents('tr').remove();
			return false;
		});

		$body.on('click','.swp-supplemental-engine-edit-trigger',function(){
			$(this).parents('.swp-supplemental-engine').addClass('swp-supplemental-engine-edit');
			updateTabContentHeights($(this).parents('.swp-supplemental-engine'));
			return false;
		});

		$body.on('click','.swp-del-supplemental-engine',function(){
			$(this).parents('.swp-supplemental-engine').remove();
			return false;
		});

		$body.on('click','.swp-add-supplemental-engine',function(e){
			e.preventDefault();
			_.templateSettings = {
				variable : 'swp',
				interpolate : /\{\{(.+?)\}\}/g
			};

			var engineSettingsTemplate = _.template($('script#tmpl-swp-engine').html());
			var supplementalTemplate = _.template($('script#tmpl-swp-supplemental-engine').html());

			var swp = {
				engine: uniqid( 'swpengine' ),
				engineLabel: 'Supplemental'
			};

			swp.engineSettings = engineSettingsTemplate(swp);

			$(this).parents('.swp-supplemental-engines-wrapper').find('.swp-supplemental-engines').append(supplementalTemplate(swp));
			$(this).parents('.swp-supplemental-engines-wrapper').find('.swp-supplemental-engines .swp-supplemental-engine:last .swp-supplemental-engine-name > a').trigger('click');
			$(this).parents('.swp-supplemental-engines-wrapper').find('.swp-supplemental-engines .swp-supplemental-engine:last .swp-supplemental-engine-name > input').focus().select();
			initTabs( $('.swp-supplemental-engines .swp-supplemental-engine:last' ) );
			excludeSelects();
			customFieldSelects();
			return false;
		});

		$('.swp-dropdown-toggle').dropdown();

	})(jQuery);
};
