/**
 * @file
 * Preview for the SocialBlue theme.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.color = {
    logoChanged: false,
    callback: function (context, settings, $form) {
      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('.color-preview .navbar-brand img').attr('src', drupalSettings.color.logo);
        this.logoChanged = true;
      }

      // Remove the logo if the setting is toggled off.
      if (drupalSettings.color.logo === null) {
        $('div').remove('.navbar-brand');
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      // Navbar background.
      $colorPreview.find('.color-preview-header .navbar-default').css('backgroundColor', $colorPalette.find('input[name="palette[navbar-bg]"]').val());
      // Navbar text.
      $colorPreview.find('.color-preview-header .navbar-default .navbar-nav > li > a').css('color', $colorPalette.find('input[name="palette[navbar-text]"]').val());

      // Navbar active background.
      $colorPreview.find('.color-preview-header .navbar-default .navbar-nav > li > a.is-active').css('backgroundColor', $colorPalette.find('input[name="palette[navbar-active-bg]"]').val());
      // Navbar active text.
      $colorPreview.find('.color-preview-header .navbar-default .navbar-nav > li > a.is-active').css('color', $colorPalette.find('input[name="palette[navbar-active-text]"]').val());

      // Section navbar background.
      $colorPreview.find('.color-preview-secondary .navbar-secondary').css('backgroundColor', $colorPalette.find('input[name="palette[navbar-sec-bg]"]').val());
      // Section navbar text.
      $colorPreview.find('.color-preview-secondary .navbar-secondary .navbar-nav a').css('color', $colorPalette.find('input[name="palette[navbar-sec-text]"]').val());

      // Brand primary color.
      var primaryInput = $colorPalette.find('input[name="palette[brand-primary]"]').val();

      $colorPreview.find('.color-preview-hero .cover').css('backgroundColor', primaryInput);
      $colorPreview.find('.color-preview-main .btn-flat, .color-preview-main .card__link').css('color', primaryInput);

      var $primaryBtn = $colorPreview.find('.color-preview-main .btn-primary');
      $primaryBtn.css('border-color', primaryInput);
      $primaryBtn.css('backgroundColor', primaryInput);

      // Markers for maps.
      $colorPreview.find('.marker-cluster-small').css({'backgroundColor': primaryInput, 'opacity': 0.6});
      $colorPreview.find('.marker-cluster-small div').css({'backgroundColor': primaryInput, 'opacity': 0.8});
      $colorPreview.find('.marker-cluster div').css({'color': $colorPalette.find('input[name="palette[navbar-text]"]').val(), 'font-weight': 'bold'});

      // Brand secondary color.
      var secondaryInput = $colorPalette.find('input[name="palette[brand-secondary]"]').val();
      $colorPreview.find('.site-footer, .badge-secondary, .stream-icon-new').css('backgroundColor', secondaryInput);

      // Brand accent color.
      $colorPreview.find('.btn-accent, .badge-accent').css('backgroundColor', $colorPalette.find('input[name="palette[brand-accent]"]').val());
      $colorPreview.find('.color-preview-hero .btn-accent').css('border-color', $colorPalette.find('input[name="palette[brand-accent]"]').val());

      // Brand link color.
      $colorPreview.find('.body-text a:not(.btn)').css('color', $colorPalette.find('input[name="palette[brand-link]"]').val());

      // Hero toggle background.
      var localStorage =  window.localStorage;
      var colorPreviewLever = $('.color-preview-hero .switch .lever');
      var colorPreviewCover = $('.color-preview-hero .cover');

      if (localStorage.getItem('lever-on')) {
          colorPreviewLever.addClass('lever-on');
          colorPreviewCover.addClass('cover-img cover-img-gradient');
      }

      $colorPreview.find('.color-preview-hero .switch').once()
        .on('click', function() {
          colorPreviewLever.toggleClass('lever-on');
          colorPreviewCover.toggleClass('cover-img cover-img-gradient');

          if (colorPreviewLever.hasClass('lever-on')) {
            localStorage.setItem('lever-on', '1');
          }
          else {
            localStorage.removeItem('lever-on');
          }
        }
      );
    }
  };
})(jQuery, Drupal, drupalSettings);
