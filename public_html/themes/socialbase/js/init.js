(function($){
  $(function(){

    var window_width = $(window).width();

    // convert rgb to hex value string
    function rgb2hex(rgb) {
      if (/^#[0-9A-F]{6}$/i.test(rgb)) { return rgb; }

      rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);

      if (rgb === null) { return "N/A"; }

      function hex(x) {
          return ("0" + parseInt(x).toString(16)).slice(-2);
      }

      return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
    }

    $('.dynamic-color .col').each(function () {
      $(this).children().each(function () {
        var color = $(this).css('background-color'),
            classes = $(this).attr('class');
        $(this).html(rgb2hex(color) + " " + classes);
        if (classes.indexOf("darken") >= 0 || $(this).hasClass('black')) {
          $(this).css('color', 'rgba(255,255,255,.9');
        }
      });
    });

    // Floating-Fixed table of contents
    if ($('.table-of-contents').length) {
      $('.toc-wrapper').pushpin({ top: $('.table-of-contents').offset().top, offset: 60 });
    }
    else if ($('#index-banner').length) {
      $('.toc-wrapper').pushpin({ top: $('#index-banner').height() });
    }
    else {
      $('.toc-wrapper').pushpin({ top: 0 });
    }



    // Github Latest Commit
    if ($('.repo-link').length) { // Checks if widget div exists (Index only)
      $.ajax({
        url: "https://api.github.com/repos/goalgorilla/drupal_social/commits/gh-pages",
        dataType: "json",
        success: function (data) {
          var sha = data.commit.committer.name,
              date = jQuery.timeago(data.commit.author.date);
          if (window_width < 1120) {
            sha = sha.substring(0,7);
          }
          $('.repo-link').find('.date').html(date);
          $('.repo-link').find('.sha').html(sha).attr('href', data.html_url);
        }
      });
    }


    // Toggle Flow Text
    var toggleFlowTextButton = $('#flow-toggle');
    toggleFlowTextButton.click( function(){
      $('#flow-text-demo').children('p').each(function(){
          $(this).toggleClass('flow-text');
        });
    });

    // Toggle Containers on page
    var toggleContainersButton = $('#container-toggle-button');
    toggleContainersButton.click(function(){
      $('body .browser-window .container, .had-container').each(function(){
        $(this).toggleClass('had-container');
        $(this).toggleClass('container');
        if ($(this).hasClass('container')) {
          toggleContainersButton.text("Turn off Containers");
        }
        else {
          toggleContainersButton.text("Turn on Containers");
        }
      });
    });

    // Set checkbox on forms.html to indeterminate
    var indeterminateCheckbox = document.getElementById('indeterminate-checkbox');
    if (indeterminateCheckbox !== null)
      indeterminateCheckbox.indeterminate = true;


		$('[data-toggle="tabs"] a').click(function (e) {
			e.preventDefault();
		  $(this).tab('show');
    });

    // Plugin initialization
    //$('.carousel.carousel-slider').carousel({full_width: true});
    //$('.carousel').carousel();
    //$('.slider').slider({full_width: true});
    //$('.modal-trigger').leanModal();
    $('.scrollspy').scrollSpy();
    $('.button-collapse').sideNav({'edge': 'left'});
    $('select').not('.disabled').material_select();


  }); // end of document ready
})(jQuery); // end of jQuery name space
