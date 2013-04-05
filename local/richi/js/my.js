(function($){
	$(document).ready(function() {
		var themes, i, pageWrapper;
		themes = ['a','b','c','d','e'], i = -1;

		$('.jqm-page').bind('refresh', function(e, newTheme) {
				/* Default to the "a" theme. */
				var oldTheme = $(this).attr('data-theme') || 'a';
				newTheme = newTheme || 'a';
				
				theme_refresh( $(this), oldTheme, newTheme );
	
				$(this).find('*').each(function() {
				  theme_refresh( $(this), oldTheme, newTheme );
				});
			});
		pageWrapper = $('#my-page-wrapper');
		window.setTimeout(function(){changeTheme(themes, i, pageWrapper);}, 2000);
	});

	/*
	 * Change theme
	 */
	var changeTheme = function changeTheme(themes, i, pageWrapper){
		console.log('change theme!');
		var j = ++i%themes.length;
		pageWrapper = pageWrapper || $('#my-page-wrapper');
		pageWrapper.trigger('refresh', themes[j]);
		 window.setTimeout(function(){changeTheme(themes, i, pageWrapper);}, 2000);
		//myCarList.setAttribute('data-theme', themes[j]);
		//myCarList.setAttribute('class', 'ui-content ui-body-'+themes[j]);
		//$('#my-car-list').trigger("pageshow");
	};
	
	function theme_refresh( element, oldTheme, newTheme ) {
				/* Update the page's new data theme. */
				if( $(element).attr('data-theme') ) {
					$(element).attr('data-theme', newTheme);
				}
 
				if( $(element).attr('class') ) {
					/* Theme classes end in "-[a-z]$", so match that */
					var classPattern = new RegExp('-' + oldTheme + '$');
					newTheme = '-' + newTheme;
 
					var classes =  $(element).attr('class').split(' ');
 
					for( var key in classes ) {
						if( classPattern.test( classes[key] ) ) {
							classes[key] = classes[key].replace( classPattern, newTheme );
						}
					}
 
					$(element).attr('class', classes.join(' '));
				}
			}


})(jQuery);