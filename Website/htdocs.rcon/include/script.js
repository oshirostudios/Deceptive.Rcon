$(document).ready(function () {
	
	/* Scroll to section */
	hash = window.location.hash;
	
	if (hash) 
	{
		$('section.selected').removeClass('selected');
		$('section' + hash).addClass('selected');
	}
	
	$.scrollTo($('section' + hash), 1000, { 
		onAfter: function () {		
			window.location.hash = hash;
			enableScroll = true;
		},
		easing: 'easeOutQuad'
	});

	$('section.selected nav.side a').css({'margin-top': ($('section.selected .height_count').height()/2-13-10) + 'px'});

	/* Navigation */
	$('nav.main a').click(function () {
		
		/* Scroll to section */
		hash = $(this).attr('href');
		$('section.selected').removeClass('selected');
		$('section' + hash).addClass('selected');
		
		$.scrollTo($('section' + hash), 1000, { 
			onAfter: function () {		
				window.location.hash = hash;
				enableScroll = true;
			},
			easing: 'easeOutQuad'
		});

		/* Side navigation */
		$('section.selected nav.side a').css({'margin-top': ($('section.selected .height_count').height()/2-13-10) + 'px'});

		return false;
	});

	$('.back').click(function () {

		/* Select menu item */
		$('nav.main a').removeClass('selected');
		$(this).addClass('selected');

		/* Scroll to section */
		hash = $(this).attr('href');
		$.scrollTo($('section' + hash), 1000, { 
			onAfter: function () {		
				window.location.hash = hash;
				enableScroll = true;
				$('section.selected').removeClass('selected');
				$('section' + hash).addClass('selected');
			},
			easing: 'easeOutQuad'
		});
	});
	
	function checkNavigation (type) {
		pages = $('.pages[data-type=' + type + ']');
		nav = $('nav.side[data-type=' + type + ']');
		(pages.attr('data-current') == 1) ? nav.children('.previous').hide() : nav.children('.previous').show();
		(pages.attr('data-current') == pages.find('.page').length) ? nav.children('.next').hide() : nav.children('.next').show();
	}
	
	/* Prevent default (don't refresh) */
	$('nav.side a, nav.main a, .back, article a.top, article h3 a, nav.gallery_nav a, nav.portfolio_nav a').click(function (e) {
		e.preventDefault();
	});

});