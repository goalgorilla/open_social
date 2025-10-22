(function ($, Drupal, once) {

  Drupal.behaviors.socialCourseNav = {
    attach: function attach(context) {
      var toggleNav = $('.course_nav-toggle', context);

      $(once('socialCourseNav', toggleNav)).each(function () {
        var el = $(this);
        var icon = el.find('svg use');
        var content = el.parents('.course__navigation').find('.card__block--list');

        el.on('click', function (e) {
          e.preventDefault();

          el.toggleClass('is-active');
          content.slideToggle(500);

          if (el.hasClass('is-active')) {
            icon.attr('xlink:href', '#icon-arrow_drop_down');
          }
          else {
            icon.attr('xlink:href', '#icon-arrow_drop_up');
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);
