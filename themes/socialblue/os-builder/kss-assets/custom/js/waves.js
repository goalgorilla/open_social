$(document).ready(function() {

  if(typeof(Waves) !== 'undefined'){

    Waves.attach('.btn-default:not(.btn-icon):not(.btn-float)', ['waves-btn']);
    Waves.attach('.btn-flat:not(.btn-icon):not(.btn-float)', ['waves-btn']);
    Waves.attach('.btn-primary:not(.btn-icon):not(.btn-float)', ['waves-btn', 'waves-light']);
    Waves.attach('.btn-secondary:not(.btn-icon):not(.btn-float)', ['waves-btn', 'waves-light']);
    Waves.attach('.btn-accent:not(.btn-icon):not(.btn-float)', ['waves-btn', 'waves-light']);
    Waves.attach('.btn-icon-toggle, .btn-float');

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

});