
(function($){
  $(function(){

    var window_width = $(window).width();

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

    $('.button-collapse').sideNav({'edge': 'left'});


  }); // end of document ready
})(jQuery); // end of jQuery name space
