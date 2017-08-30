(function ($) {

  Drupal.behaviors.initWaves = {
    attach: function (context, settings) {

      var config = {
        // How long Waves effect duration
        // when it's clicked (in milliseconds)
        duration: 750,

        // Delay showing Waves effect on touch
        // and hide the effect if user scrolls
        // (0 to disable delay) (in milliseconds)
        delay: 200
      };

      // Initialise Waves with the config
      Waves.init(config);

    }
  }

})(jQuery);