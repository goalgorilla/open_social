
(function($){
  $(function(){

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

    // Plugin initialization
    //$('.scrollspy').scrollSpy();
    var scrollSpyItem = '#scrollspy';
    $('body').scrollspy({
      target: scrollSpyItem,
      offset: 200
    });

    $(scrollSpyItem).find('a').on('click', function(event){
      if (this.hash !== "") {
        event.preventDefault();

        var hash = this.hash;

        $('html, body').animate({
          scrollTop: $(hash).offset().top - 200
        }, 400, function () {
          window.location.hash = hash;
        });
      }
    });

    $('.button-collapse').sideNav({'edge': 'left'});


  }); // end of document ready
})(jQuery); // end of jQuery name space
