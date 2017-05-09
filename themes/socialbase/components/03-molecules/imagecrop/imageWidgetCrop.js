/**
 * @file
 * Defines the custom behaviors needed for cropper integration.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  var cropperSelector = '.crop-preview-wrapper__preview-image';
  var cropperValuesSelector = '.crop-preview-wrapper__value';
  var cropWrapperSelector = '.image-data__crop-wrapper';
  var cropWrapperSummarySelector = 'summary'; // summary is not our template
  var verticalTabsSelector = '.vertical-tabs';
  var verticalTabsMenuItemSelector = '.vertical-tab-button'; // bootstrap version
  var resetSelector = '.crop-preview-wrapper__crop-reset';
  var detailsWrapper = '.details-wrapper';
  var detailsParentSelector = '.image-widget-data';
  var containerDimensions = null; // need this to apply correct size to all tabs
  var table = '.responsive-enabled';
  var cropperOptions = {
    background: false,
    zoomable: false,
    viewMode: 1,
    autoCropArea: 1,
    responsive: false,
    // Callback function, fires when crop is applied.
    cropend: function (e) {
      var $this = $(this);
      var $values = $this.siblings(cropperValuesSelector);
      var data = $this.cropper('getData');
      // Calculate delta between original and thumbnail images.
      var delta = $this.data('original-height') / $this.prop('naturalHeight');
      /*
       * All data returned by cropper plugin multiple with delta in order to get
       * proper crop sizes for original image.
       */
      $values.find('.crop-x').val(Math.round(data.x * delta));
      $values.find('.crop-y').val(Math.round(data.y * delta));
      $values.find('.crop-width').val(Math.round(data.width * delta));
      $values.find('.crop-height').val(Math.round(data.height * delta));
      $values.find('.crop-applied').val(1);
      Drupal.imageWidgetCrop.updateCropSummaries($this);
    }
  };

  Drupal.imageWidgetCrop = {};

  /**
   * Initialize cropper on the ImageWidgetCrop widget.
   *
   * @param {Object} context - Element to initialize cropper on.
   */
  Drupal.imageWidgetCrop.initialize = function (context) {
    var $cropWrapper = $(cropWrapperSelector, context);
    var $cropWrapperSummary = $cropWrapper.children(detailsWrapper).find(cropWrapperSummarySelector);
    var $verticalTabs = $(verticalTabsSelector, context);
    var $verticalTabsMenuItem = $verticalTabs.find(verticalTabsMenuItemSelector);
    var $reset = $(resetSelector, context);

    /*
     * Cropper initialization on click events on vertical tabs and details
     * summaries (for smaller screens).
     */
    $verticalTabsMenuItem.add($cropWrapperSummary).click(function () {
      var tabId = $(this).find('a').attr('href');
      var $cropper = $(this).parent().find(cropperSelector);
      if (typeof tabId !== 'undefined') {
        $cropper = $(tabId).find(cropperSelector);
      }
      var ratio = Drupal.imageWidgetCrop.getRatio($cropper);
      Drupal.imageWidgetCrop.initializeCropper($cropper, ratio);
    });

    // Handling click event for opening/closing vertical tabs.
    $cropWrapper.children(cropWrapperSummarySelector).once('imageWidgetCrop').click(function (evt) {
      // Work only on bigger screens where $verticalTabsMenuItem is not empty.
      if ($verticalTabsMenuItem.length !== 0) {
        // If detailsWrapper is not visible display it and initialize cropper.
        if (!$(this).parent().attr('open')) {
          evt.preventDefault();
          $(this).parent().attr('open', true);
          $(table).addClass('responsive-enabled--opened');
          $(this).parent().find(detailsWrapper).show();
          Drupal.imageWidgetCrop.initializeCropperOnChildren($(this).parent());
          evt.stopImmediatePropagation();
        }
        // If detailsWrapper is visible hide it.
        else {
          evt.preventDefault();
          $(this).parent().attr('open', false);
          $(table).removeClass('responsive-enabled--opened');
          $(this).parent().find(detailsWrapper).hide();
        }
      }
    });

    // This part is needed to load saved crops automatically without issues
    $(window).load(function () {
      Drupal.imageWidgetCrop.initializeCropperAutomatically($(".image-widget").next(".image-data__crop-wrapper"));
    });

    // Open crop details and apply crop automatically on image upload
    $(document).ajaxSuccess(function (event, xhr, settings) {

      // Filter by triggering element to avoid accidental calls
      if (typeof settings.extraData !== 'undefined' && settings.extraData.hasOwnProperty('_triggering_element_name')) {
        if (typeof $('button[name="' + settings.extraData._triggering_element_name + '"]') !== 'undefined') {
          Drupal.imageWidgetCrop.initializeCropperAutomatically($(".image-widget").next(".image-data__crop-wrapper"));
        }
      }
    });

    $reset.on('click', function (e) {
      e.preventDefault();
      var $element = $(this).siblings(cropperSelector);
      Drupal.imageWidgetCrop.reset($element);
      return false;
    });

    // Handling cropping when viewport resizes.
    $(window).resize(function () {
      $(detailsParentSelector).each(function () {
        // Find only opened widgets.
        var cropperDetailsWrapper = $(this).children('details[open="open"]');
        cropperDetailsWrapper.each(function () {
          // Find all croppers for opened widgets.
          var $croppers = $(this).find(cropperSelector);
          $croppers.each(function () {
            var $this = $(this);
            if ($this.parent().parent().parent().css('display') !== 'none') {
              // Get previous data for cropper.
              var canvasDataOld = $this.cropper('getCanvasData');
              var cropBoxData = $this.cropper('getCropBoxData');

              // Re-render cropper.
              $this.cropper('render');

              // Get new data for cropper and calculate resize ratio.
              var canvasDataNew = $this.cropper('getCanvasData');
              var ratio = 1;
              if (canvasDataOld.width !== 0) {
                ratio = canvasDataNew.width / canvasDataOld.width;
              }

              // Set new data for crop box.
              $.each(cropBoxData, function (index, value) {
                cropBoxData[index] = value * ratio;
              });
              $this.cropper('setCropBoxData', cropBoxData);

              Drupal.imageWidgetCrop.updateHardLimits($this);
              Drupal.imageWidgetCrop.checkSoftLimits($this);
              Drupal.imageWidgetCrop.updateCropSummaries($this);
            }
          });
        });
      });
    });

    // Correctly updating messages of summaries.
    Drupal.imageWidgetCrop.updateAllCropSummaries();
  };

  /**
   * Get ratio data and determine if an available ratio or free crop.
   *
   * @param {Object} $element - Element to initialize cropper on its children.
   */
  Drupal.imageWidgetCrop.getRatio = function ($element) {
    var ratio = $element.data('ratio');
    var regex = /:/;

    if ((regex.exec(ratio)) !== null) {
      var int = ratio.split(":");
      if ($.isArray(int) && ($.isNumeric(int[0]) && $.isNumeric(int[1]))) {
        return int[0] / int[1];
      }
      else {
        return "NaN";
      }
    }
    else {
      return ratio;
    }
  };

  /**
   * Initialize cropper on an element.
   *
   * @param {Object} $element - Element to initialize cropper on.
   * @param {number} ratio - The ratio of the image.
   */
  Drupal.imageWidgetCrop.initializeCropper = function ($element, ratio) {
    var data = null;
    var $values = $element.siblings(cropperValuesSelector);

    // Calculate minimal height for cropper container (minimal width is 200).
    var minDelta = ($element.data('original-width') / 200);
    cropperOptions['minContainerHeight'] = $element.data('original-height') / minDelta;

    var options = cropperOptions;
    var delta = $element.data('original-height') / $element.prop('naturalHeight');

    // If 'Show default crop' is checked show crop box.
    options.autoCrop = drupalSettings['crop_default'];

    if (parseInt($values.find('.crop-applied').val()) === 1) {
      data = {
        x: Math.round(parseInt($values.find('.crop-x').val()) / delta),
        y: Math.round(parseInt($values.find('.crop-y').val()) / delta),
        width: Math.round(parseInt($values.find('.crop-width').val()) / delta),
        height: Math.round(parseInt($values.find('.crop-height').val()) / delta),
        rotate: 0,
        scaleX: 1,
        scaleY: 1
      };
      options.autoCrop = true;
    }

    // React on crop move and check soft limits.
    options.cropmove = function (e) {
      Drupal.imageWidgetCrop.checkSoftLimits($(this));
    };

    options.data = data;
    options.aspectRatio = ratio;

    $element.cropper(options);

    // Hard and soft limits we need to check for fist time when cropper
    // finished it initialization.
    $element.on('built.cropper', function (e) {
      var $this = $(this);
      Drupal.imageWidgetCrop.updateHardLimits($this);
      Drupal.imageWidgetCrop.checkSoftLimits($this);

      /*
       * Temporarily set width/height for hidden wrappers,  otherwise cropper
       * library sets them to minimum values. Unset to auto after the crop has been
       * generated.
       */
      var firstContainer = $this.next('div');

      if (containerDimensions === null) {
        containerDimensions = {
          width: firstContainer.width(),
          height: firstContainer.height()
        };

        Drupal.imageWidgetCrop.initializeSecondaryCroppers($element);
      }

      // If 'Show default crop' is checked apply default crop.
      if (drupalSettings['crop_default']) {
        var dataDefault = $element.cropper('getData');
        // Calculate delta between original and thumbnail images.
        var deltaDefault = $element.data('original-height') / $element.prop('naturalHeight');
        /*
         * All data returned by cropper plugin multiple with delta in order to get
         * proper crop sizes for original image.
         */
        Drupal.imageWidgetCrop.updateCropValues($values, dataDefault, deltaDefault);
        Drupal.imageWidgetCrop.updateCropSummaries($element);
      }
    });
  };

  /**
   * Update crop values in hidden inputs.
   *
   * @param {Object} $element - Cropper values selector.
   * @param {Array} $data - Cropper data.
   * @param {number} $delta - Delta between original and thumbnail images.
   */
  Drupal.imageWidgetCrop.updateCropValues = function ($element, $data, $delta) {
    $element.find('.crop-x').val(Math.round($data.x * $delta));
    $element.find('.crop-y').val(Math.round($data.y * $delta));
    $element.find('.crop-width').val(Math.round($data.width * $delta));
    $element.find('.crop-height').val(Math.round($data.height * $delta));
    $element.find('.crop-applied').val(1);
  };

  /**
   * Converts horizontal and vertical dimensions to canvas dimensions.
   *
   * @param {Object} $element - Crop element.
   * @param {Number} x - horizontal dimension in image space.
   * @param {Number} y - vertical dimension in image space.
   */
  Drupal.imageWidgetCrop.toCanvasDimensions = function ($element, x, y) {
    var imageData = $element.data('cropper').getImageData();
    return {
      width: imageData.width * (x / $element.data('original-width')),
      height: imageData.height * (y / $element.data('original-height'))
    }
  };

  /**
   * Converts horizontal and vertical dimensions to image dimensions.
   *
   * @param {Object} $element - Crop element.
   * @param {Number} x - horizontal dimension in canvas space.
   * @param {Number} y - vertical dimension in canvas space.
   */
  Drupal.imageWidgetCrop.toImageDimensions = function ($element, x, y) {
    var imageData = $element.data('cropper').getImageData();
    return {
      width: x * ($element.data('original-width') / imageData.width),
      height: y * ($element.data('original-height') / imageData.height)
    }
  };

  /**
   * Update hard limits for given element.
   *
   * @param {Object} $element - Crop element.
   */
  Drupal.imageWidgetCrop.updateHardLimits = function ($element) {
    var cropName = $element.data('name');

    // Check first that we have configuration for this crop.
    if (!drupalSettings.image_widget_crop.hasOwnProperty(cropName)) {
      return;
    }

    var cropConfig = drupalSettings.image_widget_crop[cropName];
    var cropper = $element.data('cropper');
    var options = cropper.options;

    // Limits works in canvas so we need to convert dimensions.
    var converted = Drupal.imageWidgetCrop.toCanvasDimensions($element, cropConfig.hard_limit.width, cropConfig.hard_limit.height);
    options.minCropBoxWidth = converted.width;
    options.minCropBoxHeight = converted.height;

    // After updating the options we need to limit crop box.
    cropper.limitCropBox(true, false);
  };

  /**
   * Check soft limit for given crop element.
   *
   * @param {Object} $element - Crop element.
   */
  Drupal.imageWidgetCrop.checkSoftLimits = function ($element) {
    var cropName = $element.data('name');

    // Check first that we have configuration for this crop.
    if (!drupalSettings.image_widget_crop.hasOwnProperty(cropName)) {
      return;
    }

    var cropConfig = drupalSettings.image_widget_crop[cropName];

    var minSoftCropBox = {
      'width': Number(cropConfig.soft_limit.width) || 0,
      'height': Number(cropConfig.soft_limit.height) || 0
    };

    // We do comparison in image dimensions so lets convert first.
    var cropBoxData = $element.cropper('getCropBoxData');
    var converted = Drupal.imageWidgetCrop.toImageDimensions($element, cropBoxData.width, cropBoxData.height);

    var dimensions = ['width', 'height'];

    for (var i = 0; i < dimensions.length; ++i) {
      // @todo - setting up soft limit status in data attribute is not ideal
      // but current architecture is like that. When we convert to proper
      // one imageWidgetCrop object per crop widget we will be able to fix
      // this also. @see https://www.drupal.org/node/2660788.
      var softLimitReached = $element.data(dimensions[i] + '-soft-limit-reached');

      if (converted[dimensions[i]] < minSoftCropBox[dimensions[i]]) {
        if (!softLimitReached) {
          softLimitReached = true;
          Drupal.imageWidgetCrop.softLimitChanged($element, dimensions[i], softLimitReached);
        }
      }
      else if (softLimitReached) {
        softLimitReached = false;
        Drupal.imageWidgetCrop.softLimitChanged($element, dimensions[i], softLimitReached);
      }
    }
  };

  /**
   * React on soft limit change.
   *
   * @param {Object} $element - Crop element.
   * @param {boolean} newSoftLimitState - new soft imit state, true if it
   *   reached, or false.
   */
  Drupal.imageWidgetCrop.softLimitChanged = function ($element, dimension, newSoftLimitState) {
    var $cropperWrapper = $element.siblings('.cropper-container');
    if (newSoftLimitState) {
      $cropperWrapper.addClass('cropper--' + dimension + '-soft-limit-reached');
    }
    else {
      $cropperWrapper.removeClass('cropper--' + dimension + '-soft-limit-reached');
    }

    // @todo - use temporary storage while we are waiting for [#2660788].
    $element.data(dimension + '-soft-limit-reached', newSoftLimitState);

    Drupal.imageWidgetCrop.updateSingleCropSummary($element);
  };

  /**
   * Initialize cropper on all children of an element.
   *
   * @param {Object} $element - Element to initialize cropper on its children.
   */
  Drupal.imageWidgetCrop.initializeCropperOnChildren = function ($element) {
    var visibleCropper = $element.find(cropperSelector + ':visible');
    var ratio = Drupal.imageWidgetCrop.getRatio($(visibleCropper));
    Drupal.imageWidgetCrop.initializeCropper($(visibleCropper), ratio);
  };

  /**
   * Initialize cropper on first visible element.
   *
   * @param {Object} $element - Element that wraps crop items.
   * @todo Adjust this once/if module author provides a better solution
   */
  Drupal.imageWidgetCrop.initializeCropperAutomatically = function ($element) {
    if ($element.length !== 0) {
      var firstItem = $element.find('.in ' + cropperSelector);
      var ratio = Drupal.imageWidgetCrop.getRatio($(firstItem));

      Drupal.imageWidgetCrop.initializeCropper($(firstItem), ratio);
    }
  };

  /**
   * Initialize cropper on secondary (hidden) elements. This should always be done
   * after the first elment was initialized.
   *
   * @param {Object} $element - Element that wraps crop items.
   * @todo Adjust this once/if module author provides a better solution
   */
  Drupal.imageWidgetCrop.initializeSecondaryCroppers = function ($element) {
    var allCropperElements = $element.parent().parent().parent().siblings('.vertical-tabs-pane');
    var $allCropperElements = $(allCropperElements);

    $allCropperElements.each(function (i, item) {
      var hiddenCropper = $(item).find(cropperSelector);
      $(hiddenCropper).parent().width(containerDimensions.width);
      $(hiddenCropper).parent().height(containerDimensions.height);

      var ratio = Drupal.imageWidgetCrop.getRatio($(hiddenCropper));
      Drupal.imageWidgetCrop.initializeCropper($(hiddenCropper), ratio);
      $(hiddenCropper).parent('div').height('auto');
    });
  };

  /**
   * Update single crop summary of an element.
   *
   * @param {Object} $element - The element cropping on which has been changed.
   */
  Drupal.imageWidgetCrop.updateSingleCropSummary = function ($element) {
    var $values = $element.siblings(cropperValuesSelector);
    var croppingApplied = parseInt($values.find('.crop-applied').val());
    var summaryMessages = [];

    $element.closest('details').drupalSetSummary(function (context) {
      if (croppingApplied === 1) {
        summaryMessages.push(Drupal.t('Cropping applied.'));
      }

      if ($element.data('height-soft-limit-reached') || $element.data('width-soft-limit-reached')) {
        summaryMessages.push(Drupal.t('Soft limit reached.'));
      }

      return summaryMessages.join('<br>');
    });
  };

  /**
   * Update common crop summary of an element.
   *
   * @param {Object} $element - The element cropping on which has been changed.
   */
  Drupal.imageWidgetCrop.updateCommonCropSummary = function ($element) {
    var croppingApplied = parseInt($element.find('.crop-applied[value="1"]').length);
    var wrapperText = Drupal.t('Crop image');
    if (croppingApplied) {
      wrapperText = Drupal.t('Crop image (cropping applied)');
    }
    $element.children('summary').text(wrapperText);
  };

  /**
   * Update crop summaries after cropping cas been set or reset.
   *
   * @param {Object} $element - The element cropping on which has been changed.
   */
  Drupal.imageWidgetCrop.updateCropSummaries = function ($element) {
    var $details = $element.closest('details' + cropWrapperSelector);
    Drupal.imageWidgetCrop.updateSingleCropSummary($element);
    Drupal.imageWidgetCrop.updateCommonCropSummary($details);
  };

  /**
   * Update crop summaries of all elements.
   */
  Drupal.imageWidgetCrop.updateAllCropSummaries = function () {
    var $croppers = $(cropperSelector);
    $croppers.each(function () {
      Drupal.imageWidgetCrop.updateSingleCropSummary($(this));
    });
    var $cropWrappers = $(cropWrapperSelector);
    $cropWrappers.each(function () {
      Drupal.imageWidgetCrop.updateCommonCropSummary($(this));
    });
  };

  /**
   * Reset cropping for an element.
   *
   * @param {Object} $element - The element to reset cropping on.
   */
  Drupal.imageWidgetCrop.reset = function ($element) {
    var $valuesDefault = $element.siblings(cropperValuesSelector);
    var options = cropperOptions;
    // If 'Show default crop' is not checked re-initialize cropper.
    if (!drupalSettings['crop_default']) {
      $element.cropper('destroy');
      options.autoCrop = false;
      $element.cropper(options);
      $valuesDefault.find('.crop-applied').val(0);
      $valuesDefault.find('.crop-x').val('');
      $valuesDefault.find('.crop-y').val('');
      $valuesDefault.find('.crop-width').val('');
      $valuesDefault.find('.crop-height').val('');
    }
    else {
      // Reset cropper.
      $element.cropper('reset').cropper('options', options);
      var dataDefault = $element.cropper('getData');
      // Calculate delta between original and thumbnail images.
      var deltaDefault = $element.data('original-height') / $element.prop('naturalHeight');
      /*
       * All data returned by cropper plugin multiple with delta in order to get
       * proper crop sizes for original image.
       */
      Drupal.imageWidgetCrop.updateCropValues($valuesDefault, dataDefault, deltaDefault);
    }
    Drupal.imageWidgetCrop.updateCropSummaries($element);
  };

  Drupal.behaviors.imageWidgetCrop = {
    attach: function (context) {
      Drupal.imageWidgetCrop.initialize(context);
      Drupal.imageWidgetCrop.updateAllCropSummaries();
    }
  };

}(jQuery, Drupal, drupalSettings));
