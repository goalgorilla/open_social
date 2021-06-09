(function ($) {

    var initialized;

    function executeRequestMembershipPopup(group_id) {
        if (!initialized) {
            initialized = true;

            var requestMembershipAjaxObject = Drupal.ajax({
                url: '/group/' + group_id + '/request-membership',
            });

            requestMembershipAjaxObject.execute();
        }
    }

    function getUrlParameter(sParam)  {
        var sPageURL = window.location.search.substring(1);
        var sURLVariables = sPageURL.split('&');
        for (var i = 0; i < sURLVariables.length; i++) {
            var sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] == sParam) {
                return sParameterName[1];
            }
        }
    }

    Drupal.behaviors.socialGroupRequestPopup = {
        attach: function (context, settings) {
            var par = getUrlParameter('requested-membership');

            if (par) {
                executeRequestMembershipPopup(par);
            }
        }
    };

})(jQuery);
