/**
 * @file
 * Overrides core/misc/progress.js.
 */
(function ($, Modernizr, Drupal) {

  "use strict";

  /**
   * The collapsible details object represents a single collapsible details element.
   */
  function CollapsibleDetails(node) {
    this.$node = $(node);
    this.$node.data('details', this);
    // Expand details if there are errors inside, or if it contains an
    // element that is targeted by the URI fragment identifier.
    var anchor = location.hash && location.hash !== '#' ? ', ' + location.hash : '';
    if (this.$node.find('.error' + anchor).length) {
      this.$node.attr('open', true);
    }
    // Initialize and setup the summary,
    this.setupSummary();
    // Initialize and setup the legend.
    this.setupLegend();
  }

  /**
   * Extend CollapsibleDetails function.
   */
  $.extend(CollapsibleDetails, {
    /**
     * Holds references to instantiated CollapsibleDetails objects.
     */
    instances: []
  });

  /**
   * Extend CollapsibleDetails prototype.
   */
  $.extend(CollapsibleDetails.prototype, {
    /**
     * Initialize and setup summary events and markup.
     */
    setupSummary: function () {
      this.$summary = $('<span class="summary"></span>');
      this.$node
        .on('summaryUpdated', $.proxy(this.onSummaryUpdated, this))
        .trigger('summaryUpdated');
    },
    /**
     * Initialize and setup legend markup.
     */
    setupLegend: function () {
      // Turn the summary into a clickable link.
      var $legend = this.$node.find('> summary');

      $('<span class="details-summary-prefix visually-hidden"></span>')
        .append(this.$node.attr('open') ? Drupal.t('Hide') : Drupal.t('Show'))
        .prependTo($legend)
        .after(document.createTextNode(' '));

      // .wrapInner() does not retain bound events.
      $('<a class="details-title"></a>')
        .attr('href', '#' + this.$node.attr('id'))
        .prepend($legend.contents())
        .appendTo($legend)
        .on('click', $.proxy(this.onLegendClick, this));
      $legend.append(this.$summary);
    },
    /**
     * Handle legend clicks
     */
    onLegendClick: function (e) {
      this.toggle();
      e.preventDefault();
    },
    /**
     * Update summary
     */
    onSummaryUpdated: function () {
      var text = $.trim(this.$node.drupalGetSummary());
      this.$summary.html(text ? ' (' + text + ')' : '');
    },
    /**
     * Toggle the visibility of a details element using smooth animations.
     */
    toggle: function () {
      var isOpen = !!this.$node.attr('open');
      var $summaryPrefix = this.$node.find('> summary span.details-summary-prefix');
      if (isOpen) {
        $summaryPrefix.html(Drupal.t('Show'));
      }
      else {
        $summaryPrefix.html(Drupal.t('Hide'));
      }
      this.$node.attr('open', !isOpen);
    }
  });

  /**
   * Scroll a given fieldset into view as much as possible.
   */
  Drupal.collapseScrollIntoView = function (node) {
    var h = document.documentElement.clientHeight || document.body.clientHeight || 0;
    var offset = document.documentElement.scrollTop || document.body.scrollTop || 0;
    var posY = $(node).offset().top;
    var fudge = 55;
    if (posY + node.offsetHeight + fudge > h + offset) {
      if (node.offsetHeight > h) {
        window.scrollTo(0, posY);
      }
      else {
        window.scrollTo(0, posY + node.offsetHeight - h + fudge);
      }
    }
  };

  Drupal.behaviors.collapse = {
    attach: function (context) {
      var $fieldset = $(context).find('fieldset.collapsible').once('collapse');

      // Expand fieldset if there are errors inside, or if it contains an
      // element that is targeted by the URI fragment identifier.
      var anchor = location.hash && location.hash != '#' ? ', ' + location.hash : '';
      if ($fieldset.find('.error' + anchor).length) {
        $fieldset.removeClass('collapsed');
      }

      var summary = $('<span class="summary"></span>');
      $fieldset.
        bind('summaryUpdated', function () {
          var text = $.trim($fieldset.drupalGetSummary());
          summary.html(text ? ' (' + text + ')' : '');
        })
        .trigger('summaryUpdated');

      // Turn the legend into a clickable link, but retain span.fieldset-legend
      // for CSS positioning.
      var $legend = $('> legend .fieldset-legend', this);

      $('<span class="fieldset-legend-prefix element-invisible"></span>')
        .append($fieldset.hasClass('collapsed') ? Drupal.t('Show') : Drupal.t('Hide'))
        .prependTo($legend);

      $fieldset.find('[data-toggle=collapse]').on('click', function (e) {
        e.preventDefault();
      });

      // Bind Bootstrap events with Drupal core events.
      $fieldset
        .append(summary)
        .on('show.bs.collapse', function () {
          $fieldset
            .removeClass('collapsed')
            .find('> legend span.fieldset-legend-prefix').html(Drupal.t('Hide'));
        })
        .on('shown.bs.collapse', function () {
          $fieldset.trigger({ type: 'collapsed', value: false });
          Drupal.collapseScrollIntoView($fieldset.get(0));
        })
        .on('hide.bs.collapse', function () {
          $fieldset
            .addClass('collapsed')
            .find('> legend span.fieldset-legend-prefix').html(Drupal.t('Show'));
        })
        .on('hidden.bs.collapse', function () {
          $fieldset.trigger({ type: 'collapsed', value: true });
        });

      if ($fieldset.length) {
        for (var i = 0; i < $fieldset.length; i++) {
          CollapsibleDetails.instances.push(new CollapsibleDetails($fieldset[i]));
        }
      }
    }
  };

  // Expose constructor in the public space.
  Drupal.CollapsibleDetails = CollapsibleDetails;

})(jQuery, Modernizr, Drupal);
