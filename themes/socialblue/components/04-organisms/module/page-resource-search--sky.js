(function ($) {

  /**
   * Attach waves effect to buttons.
   */
  Drupal.behaviors.searchPage = {
    attach: function (context) {

      // Collapse block.
      var collapseBlock = $('.js-collapse-block');

      setTimeout(function () {
        collapseBlock.once('searchBlock').each(function () {
          var collapseBlockHeight = $(this).height();

          var toggleOpen = $('.js-toggle-filter-open');
          var toggleClose = $('.js-toggle-filter-close');
          var generalHeight = 88;
          var countSitems = $(this).find('> *');
          var maxHeight = 0;

          // General behavior.
          toggleClose.css('display', 'none');

          // Toggle filter.
          function toggleFilter(button, height, buttonShow) {
            button.once('toggleClick').on('click', function (e) {
              e.preventDefault();
              collapseBlock.css({'height': height});
              $(this).css('display', 'none');
              buttonShow.css('display', 'flex');
            });
          }

          var select = $('.select2', context);
          var selectHeight = select.height();

          if ($(window).width() >= 1200) {
            countSitems.eq(1).addClass('show-item');
            countSitems.eq(2).addClass('show-item');
            countSitems.eq(3).addClass('show-item');

            $(this).find('.show-item').each(function () {
              if ($(this).find('.select2').height() > maxHeight) {
                maxHeight = $(this).find('.select2').height();
              }
            });

            function newHeight() {
              var newGeneralHeight = generalHeight + (maxHeight - 35);

              collapseBlock.css({'height': newGeneralHeight});

              // Hide filters.
              toggleFilter(toggleClose, newGeneralHeight, toggleOpen);
            }


            newHeight();

            if (selectHeight === 35) {
              collapseBlock.css({'height': generalHeight});

              // Hide filters.
              toggleFilter(toggleClose, generalHeight, toggleOpen);

            }
            else if (maxHeight > 62 && maxHeight !== 'undefined') {
              newHeight();
            }
            else {
              newHeight();
            }
          }
          else if ($(window).width() >= 900 && $(window).width() < 1200) {
            countSitems.eq(1).addClass('show-item');
            countSitems.eq(2).addClass('show-item');

            $(this).find('.show-item').each(function () {
              if ($(this).find('.select2').height() > maxHeight) {
                maxHeight = $(this).find('.select2').height();
              }
            });

            var newGeneralHeight1 = generalHeight + (maxHeight - 35);

            collapseBlock.css({'height': newGeneralHeight1});

            // Hide filters.
            toggleFilter(toggleClose, newGeneralHeight1, toggleOpen);

          }
          else if ($(window).width() >= 600 && $(window).width() < 900) {
            var firstSelect = countSitems.eq(1)
              .find('.select2').height();

            var secondGeneralHeight = generalHeight + (firstSelect - 35);

            collapseBlock.css({'height': secondGeneralHeight});

            // Hide filters.
            toggleFilter(toggleClose, secondGeneralHeight, toggleOpen);
          }
          else {
            collapseBlock.css({'height': generalHeight});

            // Hide filters.
            toggleFilter(toggleClose, generalHeight, toggleOpen);
          }

          // Show filters.
          toggleFilter(toggleOpen, collapseBlockHeight, toggleClose);

          // Open filter block on focus(click) input/select
          //Styles for open/close filter.
          function stylesFilter() {
            collapseBlock.css({'height': collapseBlockHeight});
            toggleOpen.css('display', 'none');
            toggleClose.css('display', 'flex');
          }

          function stylesFilterClear() {
            stylesFilter();

            setTimeout(function () {
              collapseBlock.css({'height': 'auto'});
            }, 500);
          }

          $(this)
            .once('show-filter')
            .find('input, select')
            .on('click', function () {
              stylesFilterClear();
            });

          if(select.find('.select2-selection__choice').length !== 0) {
            stylesFilter();
          }
        });
      }, 0);
    }

  };

})(jQuery);
