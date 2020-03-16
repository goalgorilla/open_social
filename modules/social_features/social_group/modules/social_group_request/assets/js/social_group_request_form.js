(function ($) {
    Drupal.behaviors.socialGroupRequestForm = {
        attach: function (context, settings) {
            var $direct = $('input[name="field_group_allowed_join_method[direct]"]');
            var $request = $('input[name="field_group_allowed_join_method[request]"]');

            var checkCheckbox = function($current, $checkbox) {
                if ($current.prop('checked') == true) {
                    $checkbox.prop('checked', false);
                }
            }

            $direct.on('change', function() {
                var $this = $(this);
                checkCheckbox($this, $request);
            });

            $request.on('change', function() {
                var $this = $(this);
                checkCheckbox($this, $direct);
            });
        }
    };
})(jQuery);
