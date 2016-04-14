(function ($) {
  $(document).ready(function() {


    // Text based inputs
    var input_selector = 'input[type=text], input[type=password], input[type=email], input[type=url], input[type=tel], input[type=number], input[type=search], textarea';

    // Textarea Auto Resize
    var hiddenDiv = $('.hiddendiv').first();
    if (!hiddenDiv.length) {
      hiddenDiv = $('<div class="hiddendiv common"></div>');
      $('body').append(hiddenDiv);
    }
    var text_area_selector = '.materialize-textarea';

    function textareaAutoResize($textarea) {
      // Set font properties of hiddenDiv

      var fontFamily = $textarea.css('font-family');
      var fontSize = $textarea.css('font-size');

      if (fontSize) { hiddenDiv.css('font-size', fontSize); }
      if (fontFamily) { hiddenDiv.css('font-family', fontFamily); }

      if ($textarea.attr('wrap') === "off") {
        hiddenDiv.css('overflow-wrap', "normal")
                 .css('white-space', "pre");
      }

      hiddenDiv.text($textarea.val() + '\n');
      var content = hiddenDiv.html().replace(/\n/g, '<br>');
      hiddenDiv.html(content);


      // When textarea is hidden, width goes crazy.
      // Approximate with half of window size

      if ($textarea.is(':visible')) {
        hiddenDiv.css('width', $textarea.width());
      }
      else {
        hiddenDiv.css('width', $(window).width()/2);
      }

      $textarea.css('height', hiddenDiv.height());
    }

    $(text_area_selector).each(function () {
      var $textarea = $(this);
      if ($textarea.val().length) {
        textareaAutoResize($textarea);
      }
    });

    $('body').on('keyup keydown autoresize', text_area_selector, function () {
      textareaAutoResize($(this));
    });

    $('select.form-control').wrap('<div class="material-select"></div>')


    /****************
    *  Range Input  *
    ****************/

    var range_type = 'input[type=range]';
    var range_mousedown = false;
    var left;

    $(range_type).each(function () {
      var thumb = $('<span class="thumb"><span class="value"></span></span>');
      $(this).after(thumb);
    });

    var range_wrapper = '.range-field';
    $(document).on('change', range_type, function(e) {
      var thumb = $(this).siblings('.thumb');
      thumb.find('.value').html($(this).val());
    });

    $(document).on('input mousedown touchstart', range_type, function(e) {
      var thumb = $(this).siblings('.thumb');
      var width = $(this).outerWidth();

      // If thumb indicator does not exist yet, create it
      if (thumb.length <= 0) {
        thumb = $('<span class="thumb"><span class="value"></span></span>');
        $(this).after(thumb);
      }

      // Set indicator value
      thumb.find('.value').html($(this).val());

      range_mousedown = true;
      $(this).addClass('active');

      if (!thumb.hasClass('active')) {
        thumb.velocity({ height: "30px", width: "30px", top: "-20px", marginLeft: "-15px"}, { duration: 300, easing: 'easeOutExpo' });
      }

      if (e.type !== 'input') {
        if(e.pageX === undefined || e.pageX === null){//mobile
           left = e.originalEvent.touches[0].pageX - $(this).offset().left;
        }
        else{ // desktop
           left = e.pageX - $(this).offset().left;
        }
        if (left < 0) {
          left = 0;
        }
        else if (left > width) {
          left = width;
        }
        thumb.addClass('active').css('left', left);
      }

      thumb.find('.value').html($(this).val());
    });

    $(document).on('mouseup touchend', range_wrapper, function() {
      range_mousedown = false;
      $(this).removeClass('active');
    });

    $(document).on('mousemove touchmove', range_wrapper, function(e) {
      var thumb = $(this).children('.thumb');
      var left;
      if (range_mousedown) {
        if (!thumb.hasClass('active')) {
          thumb.velocity({ height: '30px', width: '30px', top: '-20px', marginLeft: '-15px'}, { duration: 300, easing: 'easeOutExpo' });
        }
        if (e.pageX === undefined || e.pageX === null) { //mobile
          left = e.originalEvent.touches[0].pageX - $(this).offset().left;
        }
        else{ // desktop
          left = e.pageX - $(this).offset().left;
        }
        var width = $(this).outerWidth();

        if (left < 0) {
          left = 0;
        }
        else if (left > width) {
          left = width;
        }
        thumb.addClass('active').css('left', left);
        thumb.find('.value').html(thumb.siblings(range_type).val());
      }
    });

    $(document).on('mouseout touchleave', range_wrapper, function() {
      if (!range_mousedown) {

        var thumb = $(this).children('.thumb');

        if (thumb.hasClass('active')) {
          thumb.velocity({ height: '0', width: '0', top: '10px', marginLeft: '-6px'}, { duration: 100 });
        }
        thumb.removeClass('active');
      }
    });


  /**************************
		 * Auto complete plugin  *
		 *************************/
		$(input_selector).each(function() {
			var $input = $(this);

				if( $input.hasClass('autocomplete') ) {
					var $array = $input.data('array'),
							$inputDiv = $input.closest('.input-field'); // Div to append on

					// Check if "data-array" isn't empty
					if( $array !== '' ) {
						// Create html element
						var $html = '<ul class="autocomplete-content hidden">';

						for( var i = 0; i < $array.length; i++ ) {
							// If path and class aren't empty add image to auto complete else create normal element
							if( $array[i]['path'] !== '' && $array[i]['class'] !== '' ) {
								$html += '<li class="autocomplete-option"><img src="'+$array[i]['path']+'" class="'+$array[i]['class']+'"><span>'+$array[i]['value']+'</span></li>';
							} else {
								$html += '<li class="autocomplete-option"><span>'+$array[i]['value']+'</span></li>';
							}
						}

						$html += '</ul>';
						$inputDiv.append($html); // Set ul in body
						// End create html element

						function highlight(string) {
							$('.autocomplete-content li').each(function () {
								var matchStart = $(this).text().toLowerCase().indexOf("" + string.toLowerCase() + ""),
									 	matchEnd = matchStart + string.length - 1,
									 	beforeMatch = $(this).text().slice(0, matchStart),
									 	matchText = $(this).text().slice(matchStart, matchEnd + 1),
									 	afterMatch = $(this).text().slice(matchEnd + 1);
								$(this).html("<span>" + beforeMatch + "<span class='highlight'>" + matchText + "</span>" + afterMatch + "</span>");
							});
						}

						// Perform search
						$(document).on('keyup', $input, function () {
							var $val = $input.val().trim(),
									$select = $('.autocomplete-content');
							// Check if the input isn't empty
							if ($val != '') {
								$select.children('li').addClass('hidden');
								$select.children('li').filter(function() {
									$select.removeClass('hidden'); // Show results

									// If text needs to highlighted
									if( $input.hasClass('highlight-matching') ) {
										highlight($val);
									}

									return $(this).text().indexOf($val) !== -1;
								}).removeClass('hidden');
							} else {
								$select.children().addClass('hidden');
							}
						});

						// Set input value
						$('.autocomplete-option').click(function() {
							$input.val($(this).text().trim());
						});
					} else {
						return false;
					}
				}
			});

  }); // End of $(document).ready

}( jQuery ));
