(function ($, once) {

  Drupal.behaviors.dropDownTable = {
    attach: function (context, settings) {
      // Find out a table responsive on a page.
      $(once('dropDownTable', '.table-responsive', context)).each(function () {
        var $currentTableResponsive = $(this);
        var $dropDownToggle = $currentTableResponsive.find('.dropdown-toggle');

        // Remove a preview popup if you use back button in the browser.
        $(window).bind("pageshow", function(event) {
          if (event.originalEvent.persisted) {
            $('body').find('> .dropdown-popover').remove();
          }
        });

        // Find the `dropdown-toggle` element inside the table responsive
        $dropDownToggle.each(function () {
          var $this = $(this);
          var $dropDownList = $this.next();
          var dropdownWidth = 185;
          var $toggleHeight = $(this).outerHeight();
          var $toggleWeight = $(this).outerWidth();

          // Add specific class to the dropdown menu.
          $dropDownList.addClass('dropdown-popover')

          // New behavior of the dropdown menu inside table responsive.
          $this.on('click', function () {
            if($dropDownList.hasClass('dropdown-menu') && $this.attr('aria-expanded') === 'false') {
              // Props of the dropdown toggle/menu elements.
              var $dropdownTop = $(this).offset().top;
              var $dropdownLeft = $(this).offset().left;

              // Top/Left position of the current `dropdown-toggle` element
              var sumTop = ($dropdownTop + $toggleHeight) + 'px';
              var sumLeft = $dropdownLeft + 'px';

              var marginLeft = dropdownWidth >= $toggleWeight ? -(dropdownWidth - $toggleWeight) : -185;

              // Behavior of the dropdown menu that we have inside the table.
              $dropDownList.hide();

              // Clone and put the current dropdown menu at the end before </body> element.
              $($dropDownList).clone().appendTo('body');

              // Set new styles to the new dropdown menu.
              setTimeout(function () {
                $('body > .dropdown-popover').css({
                  'display': 'block',
                  'position': 'absolute',
                  'top': sumTop,
                  'left': sumLeft,
                  'right': '0px',
                  'margin-left': marginLeft,
                });
              }, 200);
            } else {
              $('body').find('> .dropdown-popover').remove();
            }
          });

          // Remove the new dropdown menu from the DOM
          // if the user click on outside of the dropdown toggle and new dropdown menu
          $(document).mouseup(function(e) {
            var $dropdownPopover = $('body').find('> .dropdown-popover');

            // if the target of the click isn't the container nor a descendant of the container
            if (!$dropdownPopover.is(e.target) && $dropdownPopover.has(e.target).length === 0) {
              $dropdownPopover.remove();
            }
          });
        })
      });

    }
  }

})(jQuery, once);
