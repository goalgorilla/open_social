/**
 * @file
 * Drupal Bootstrap object.
 */

/**
 * A Bootstrap plugin constructor.
 *
 * @constructor
 * @class
 * @typedef {Constructor} BootstrapConstructor
 */


(function (_, $, Drupal, drupalSettings) {
  "use strict";

  Drupal.Bootstrap = {
    settings: drupalSettings.bootstrap || {},

    /**
     * Extends a Bootstrap plugin constructor.
     *
     * @param {string} id
     *   A Bootstrap plugin identifier located in $.fn.
     * @param {function} [callback]
     *   A callback to extend the plugin constructor.
     *
     * @return {function|boolean}
     *   The Bootstrap plugin or FALSE if the plugin does not exist.
     */
    extendPlugin: function (id, callback) {
      // Immediately return if the plugin does not exist.
      if (!$.fn[id] || !$.fn[id].Constructor) return false;

      // Extend the plugin if a callback was provided.
      if ($.isFunction(callback)) {
        var ret = callback.apply($.fn[id].Constructor, [this.settings]);
        if ($.isPlainObject(ret)) {
          $.extend(true, $.fn[id].Constructor, ret);
        }
      }

      // Add a jQuery UI like option getter/setter method.
      if ($.fn[id].Constructor.prototype.option === void(0)) {
        $.fn[id].Constructor.prototype.option = this.option;
      }

      return $.fn[id].Constructor;
    },

    /**
     * Replaces a Bootstrap jQuery plugin definition.
     *
     * @param {string} id
     *   A Bootstrap plugin identifier located in $.fn.
     * @param {function} [callback]
     *   A callback to replace the jQuery plugin definition. The callback must
     *   return a function that is used to construct a jQuery plugin.
     *
     * @return {function|boolean}
     *   The Bootstrap jQuery plugin definition or FALSE if the plugin does not
     *   exist.
     */
    replacePlugin: function (id, callback) {
      // Immediately return if plugin does not exist or not a valid callback.
      if (!$.fn[id] || !$.fn[id].Constructor || !$.isFunction(callback)) return false;
      var constructor = $.fn[id].Constructor;

      var plugin = callback.apply(constructor);
      if ($.isFunction(plugin)) {
        plugin.Constructor = constructor;

        var old = $.fn[id];
        plugin.noConflict = function () { $.fn[id] = old; return this; };
        $.fn[id] = plugin;
      }
    },

    /**
     * Provide jQuery UI like ability to get/set options for Bootstrap plugins.
     *
     * @param {string|object} key
     *   A string value of the option to set, can be dot like to a nested key.
     *   An object of key/value pairs.
     * @param {*} [value]
     *   (optional) A value to set for key.
     *
     * @returns {*}
     *   - Returns nothing if key is an object or both key and value parameters
     *   were provided to set an option.
     *   - Returns the a value for a specific setting if key was provided.
     *   - Returns an object of key/value pairs of all the options if no key or
     *   value parameter was provided.
     *
     * @see https://github.com/jquery/jquery-ui/blob/master/ui/widget.js
     *
     * @todo This isn't fully working since Bootstrap plugins don't allow
     * methods to return values.
     */
    option: function (key, value) {
      var options = key;
      var parts, curOption, i;

      // Don't return a reference to the internal hash.
      if (arguments.length === 0) {
        return $.extend({}, this.options);
      }

      // Handle a specific option.
      if (typeof key === "string") {
        // Handle nested keys, e.g., "foo.bar" => { foo: { bar: ___ } }
        options = {};
        parts = key.split(".");
        key = parts.shift();
        if (parts.length) {
          curOption = options[key] = $.extend({}, this.options[key]);
          for (i = 0; i < parts.length - 1; i++) {
            curOption[parts[i]] = curOption[parts[i]] || {};
            curOption = curOption[parts[i]];
          }
          key = parts.pop();
          if (arguments.length === 1) {
            return curOption[key] === undefined ? null : curOption[key];
          }
          curOption[key] = value;
        }
        else {
          if (arguments.length === 1) {
            return this.options[key] === undefined ? null : this.options[key];
          }
          options[key] = value;
        }
      }

      // Set the new option(s).
      for (key in options) {
        if (!options.hasOwnProperty(key)) continue;
        this.options[key] = options[key];
      }
      return this;
    }

  };


})(_, jQuery, Drupal, drupalSettings);
