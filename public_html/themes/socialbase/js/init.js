
(function($){
  $(function(){

    var screen_sm_min = 767;
    var window_width = $(window).width();

    if (window_width > screen_sm_min ) {
      // Floating-Fixed table of contents
      if ($('.table-of-contents').length) {
        $('.toc-wrapper').pushpin({ top: $('.table-of-contents').offset().top, offset: 50 });
      }
      else if ($('#index-banner').length) {
        $('.toc-wrapper').pushpin({ top: $('#index-banner').height() });
      }
      else {
        $('.toc-wrapper').pushpin({ top: 0 });
      }

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



		$('[data-toggle="tabs"] a').click(function (e) {
			e.preventDefault();
		  $(this).tab('show');
    });

    // Plugin initialization
    $('.scrollspy').scrollSpy();
    $('.button-collapse').sideNav({'edge': 'left'});


  }); // end of document ready
})(jQuery); // end of jQuery name space
