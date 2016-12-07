(function ($) {

  Drupal.behaviors.initResponsiveDom = {
    attach: function (context, settings) {

			$( function(){
        var $complementaryTop = $('.complementary-top');
        var $complementaryBottom = $('.complementary-bottom');

        $complementaryBottom.responsiveDom({
          appendTo: $complementaryTop,
          mediaQuery: '(min-width: 960px)'
        });
			});

		}
	}

})(jQuery);
