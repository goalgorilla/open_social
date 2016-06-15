(function ($) {

  Drupal.behaviors.searchBoxResize = {

    attach: function (context, settings) {

      clearSearchTimer = null;

      // Listen for the nav search button click
  		$('#navbar-search .form-submit').on('click', function (e) {
        handleButtonClick(e);
  		});

  		// When the search field loses focus
  		$('#navbar-search .form-control').on('blur', function (e) {
        handleFieldBlur(e);
  		});

      function handleButtonClick(e) {

    		e.preventDefault();
    		var form = $(e.currentTarget).closest('form');
    		var input = form.find('.form-control');
    		var keyword = input.val();

    		if ($.trim(keyword) === '') {
    			// When there is no keyword, just open the bar
    			form.addClass('is-open');
    			input.focus();
    		}

    		else {
    			// When there is a keyword, submit the keyword
    			form.addClass('is-open');
    			form.submit();

    			// Clear the timer that removes the keyword
    			clearTimeout(this.clearSearchTimer);
    		}
    	};

      function handleFieldBlur(e) {
    		// When the search field loses focus
    		var input = $(e.currentTarget);
    		var form = input.closest('form');

    		// Collapse the search field
    		form.removeClass('is-open');

    		// Clear the textfield after 300 seconds (the time it takes to collapse the field)
    		clearTimeout(this.clearSearchTimer);
    		this.clearSearchTimer = setTimeout(function () {
    			input.val('');
    		}, 300);
    	};

    }

  };
})(jQuery);
