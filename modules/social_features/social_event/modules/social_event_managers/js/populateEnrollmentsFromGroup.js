/**
 * @file
 */

(function ($) {

  /**
   * Behaviors.
   */
  Drupal.behaviors.populateEnrollmentsFromGroup = {
    attach: function (context, settings) {
      var nid = settings.populateEnrollmentsFromGroup.nid;
      var groupId = settings.populateEnrollmentsFromGroup.group_id;
      $('#enroll_users', context).on('click', function() {
        var enrolleesSelect = $('#edit-name');
        $.ajax({
          type: 'GET',
          url: '/node/' + nid + '/all-enrollments/add-enrollees/populate-from-group/' + groupId
        }).then(function (data) {
          var arrayLength = data.length;
          for (var i = 0; i < arrayLength; i++) {
            // create the option and append to Select2
            var option = new Option(data[i].full_name, data[i].id, true, true);
            enrolleesSelect.append(option).trigger('change');

            // manually trigger the `select2:select` event
            enrolleesSelect.trigger({
              type: 'select2:select',
              params: {
                data: data
              }
            });
          }
        });
      });
    }
  };

})(jQuery);
