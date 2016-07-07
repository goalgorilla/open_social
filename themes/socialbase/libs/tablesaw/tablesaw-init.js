(function ($) {

  Drupal.behaviors.initTableSaw = {
    attach: function (context, settings) {

			// DOM-ready auto-init of plugins.
			// Many plugins bind to an "enhance" event to init themselves on dom ready, or when new markup is inserted into the DOM
			$( function(){
				$( document ).trigger( "enhance.tablesaw" );
			});

		}
	}

})(jQuery);
