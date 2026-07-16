/**
 * @file
 * Shared Views AJAX / browser URL helpers extracted from Safety Issues.
 *
 * Extracted from iata_capture/safety-issues-facets-url.js so Facets/overview
 * consumers can reuse the same plumbing. Callers use
 * Drupal.socialCore.viewsAjaxUrl.*; Facets-specific beforeSend and link
 * rewriting stay in iata_capture.
 *
 * @todo PROD-35565 - Replace browser URL sync and Views instance URL sync with
 *   core SetBrowserUrl after upgrade to Drupal 11.3+
 *   (https://www.drupal.org/node/343535).
 */

(function (Drupal, once, drupalSettings) {

  'use strict';

  Drupal.socialCore = Drupal.socialCore || {};

  var STRIP_URL_PARAMS = ['q', 'page', 'render'];

  /**
   * Public API for Facets and overview consumers.
   *
   * Function declarations below are hoisted; order follows the stepdown rule:
   * primary operations, then view/AJAX identification, AJAX read/write, then
   * URL primitives.
   */
  Drupal.socialCore.viewsAjaxUrl = {
    STRIP_URL_PARAMS: STRIP_URL_PARAMS,
    sanitizeQueryParams: sanitizeQueryParams,
    parseHrefQueryParams: parseHrefQueryParams,
    getPathBase: getPathBase,
    getExposedFormSelector: getExposedFormSelector,
    isGetAjaxRequest: isGetAjaxRequest,
    parseAjaxData: parseAjaxData,
    parseAjaxUrlAndData: parseAjaxUrlAndData,
    writeAjaxData: writeAjaxData,
    writeParamsToViewAjax: writeParamsToViewAjax,
    isViewAjax: isViewAjax,
    getViewDisplayId: getViewDisplayId,
    buildHrefFromParams: buildHrefFromParams,
    syncViewsAjaxUrlFromLocation: syncViewsAjaxUrlFromLocation,
    syncExposedSortControlsFromUrl: syncExposedSortControlsFromUrl,
    replaceBrowserUrl: replaceBrowserUrl,
  };

  // ---------------------------------------------------------------------------
  // Primary operations (what consumers call for URL sync)
  // ---------------------------------------------------------------------------

  /**
   * Updates the browser location via history.replaceState.
   *
   * @todo PROD-35565 - Prefer core SetBrowserUrl once on Drupal 11.3+
   *   (https://www.drupal.org/node/343535).
   */
  function replaceBrowserUrl(href) {
    window.history.replaceState(null, document.title, href);
  }

  /**
   * Keeps Drupal.views.instances AJAX URLs aligned with window.location.search.
   *
   * @param {string} [viewName]
   *   Optional view machine name to limit syncing.
   */
  function syncViewsAjaxUrlFromLocation(viewName) {
    if (typeof drupalSettings === 'undefined' ||
      !drupalSettings.views ||
      !drupalSettings.views.ajaxViews ||
      !Drupal.views ||
      !Drupal.views.instances) {
      return;
    }

    var ajaxPath = drupalSettings.views.ajax_path;
    if (!ajaxPath) {
      return;
    }
    if (Array.isArray(ajaxPath)) {
      ajaxPath = ajaxPath[0];
    }

    var queryString = window.location.search || '';
    if (queryString !== '') {
      var params = new URLSearchParams(queryString);
      STRIP_URL_PARAMS.forEach(function (param) {
        params.delete(param);
      });
      var paramStr = params.toString();
      if (paramStr !== '') {
        queryString = (/\?/.test(ajaxPath) ? '&' : '?') + paramStr;
      }
      else {
        queryString = '';
      }
    }

    var syncedUrl = ajaxPath + queryString;

    Object.keys(drupalSettings.views.ajaxViews).forEach(function (instanceKey) {
      var instance = Drupal.views.instances[instanceKey];
      if (!instance || !instance.settings) {
        return;
      }
      if (viewName && instance.settings.view_name !== viewName) {
        return;
      }

      instance.element_settings.url = syncedUrl;

      if (instance.exposedFormAjax && instance.exposedFormAjax.length) {
        instance.exposedFormAjax.forEach(function (ajax) {
          if (!ajax) {
            return;
          }
          ajax.url = syncedUrl;
          if (ajax.options) {
            ajax.options.url = syncedUrl;
          }
        });
      }
    });
  }

  /**
   * Aligns .title-with-sorts controls with the current query string.
   *
   * @param {function(HTMLSelectElement, Element, boolean, Object)=} onSynced
   *   Optional callback(select, toolbar, syncDefaultOrder, params) for
   *   consumer-specific label updates after controls are aligned.
   */
  function syncExposedSortControlsFromUrl(onSynced) {
    var toolbar = document.querySelector('.title-with-sorts');
    if (!toolbar) {
      return;
    }

    var params = sanitizeQueryParams(parseHrefQueryParams(window.location.href));
    var selectElement = toolbar.querySelector('select[name="sort_by"]');

    if (selectElement && params.sort_by) {
      selectElement.value = params.sort_by;
    }

    if (params.sort_order) {
      toolbar.querySelectorAll('input[name="sort_order"]').forEach(function (input) {
        input.checked = input.value === params.sort_order;
      });
    }

    if (typeof onSynced === 'function' && selectElement) {
      onSynced(selectElement, toolbar, !params.sort_order, params);
    }
  }

  /**
   * Builds a path + query string from merged params.
   *
   * @param {string} pathBase
   * @param {Object} params
   * @param {Object} [options]
   * @param {boolean} [options.omitEmpty=true]
   *   Drop empty values from the query string.
   * @param {string[]} [options.stripParams]
   *   Param names to omit (defaults to q/page/render).
   */
  function buildHrefFromParams(pathBase, params, options) {
    options = options || {};
    var omitEmpty = options.omitEmpty !== false;
    var stripParams = options.stripParams || STRIP_URL_PARAMS;
    var cleanParams = {};

    Object.keys(params).forEach(function (key) {
      if (stripParams.indexOf(key) !== -1) {
        return;
      }
      if (omitEmpty && (params[key] === '' || params[key] === undefined || params[key] === null)) {
        return;
      }
      cleanParams[key] = params[key];
    });

    var query = toQueryString(cleanParams);
    return query ? pathBase + '?' + query : pathBase;
  }

  // ---------------------------------------------------------------------------
  // View / AJAX identification
  // ---------------------------------------------------------------------------

  /**
   * Returns whether the AJAX request targets the given view machine name.
   */
  function isViewAjax(settings, viewName) {
    if (!settings || !viewName) {
      return false;
    }

    var url = settings.url || '';
    if (url.indexOf('/views/ajax') !== -1) {
      if (url.indexOf('view_name=' + viewName) !== -1 ||
        url.indexOf('view_name%3D' + viewName) !== -1) {
        return true;
      }
    }

    var data = settings.extraData || settings.data || {};
    if (typeof data === 'string') {
      if (data.length === 0) {
        return false;
      }
      return data.indexOf('view_name=' + viewName) !== -1 ||
        data.indexOf('view_name%3D' + viewName) !== -1;
    }

    return data.view_name === viewName;
  }

  /**
   * Resolves the view display ID from AJAX settings.
   */
  function getViewDisplayId(options, defaultDisplay) {
    var data = options.data || options.extraData || {};
    if (typeof data === 'string') {
      var match = data.match(/view_display_id(?:%3D|=)([^&]+)/);
      if (match) {
        return decodeURIComponent(match[1]);
      }
      return defaultDisplay;
    }

    if (data.view_display_id) {
      return data.view_display_id;
    }
    if (options.extraData && options.extraData.view_display_id) {
      return options.extraData.view_display_id;
    }
    return defaultDisplay;
  }

  // ---------------------------------------------------------------------------
  // AJAX read / write
  // ---------------------------------------------------------------------------

  /**
   * Returns true when the AJAX options use the GET method.
   */
  function isGetAjaxRequest(options) {
    var method = options.method || options.type || 'GET';
    return String(method).toUpperCase() === 'GET';
  }

  /**
   * Normalizes AJAX data to a flat param map.
   */
  function parseAjaxData(data) {
    if (typeof data === 'string') {
      var query = data.charAt(0) === '?' ? data : '?' + data;
      return sanitizeQueryParams(Drupal.Views.parseQueryString(query));
    }

    if (typeof data === 'object' && data !== null) {
      return Object.assign({}, data);
    }

    return {};
  }

  /**
   * Merges query params from options.url and options.data into one map.
   */
  function parseAjaxUrlAndData(options) {
    var result = {};

    if (options.url) {
      var queryIndex = options.url.indexOf('?');
      if (queryIndex !== -1) {
        Object.assign(
          result,
          sanitizeQueryParams(Drupal.Views.parseQueryString(options.url.substring(queryIndex)))
        );
      }
    }

    Object.assign(result, parseAjaxData(options.data));

    return result;
  }

  /**
   * Writes merged params back onto the AJAX options object.
   */
  function writeAjaxData(options, params) {
    if (typeof options.data === 'string') {
      options.data = toQueryString(params);
      return;
    }

    options.data = params;
  }

  /**
   * Writes params onto a Views AJAX request (GET query or POST data).
   */
  function writeParamsToViewAjax(options, params) {
    if (options.url && options.url.indexOf('/views/ajax') !== -1 && isGetAjaxRequest(options)) {
      var base = options.url.split('?')[0];
      options.url = base + '?' + toQueryString(params);
      delete options.data;
      return;
    }

    writeAjaxData(options, params);
  }

  // ---------------------------------------------------------------------------
  // URL primitives
  // ---------------------------------------------------------------------------

  /**
   * Serializes a flat param map to a query string.
   *
   * Array values are emitted as repeated keys (e.g. foo[]=1&foo[]=2), matching
   * PHP multi-value query parsing. Nested objects are not supported.
   */
  function toQueryString(params) {
    var searchParams = new URLSearchParams();

    Object.keys(params).forEach(function (key) {
      var value = params[key];
      if (Array.isArray(value)) {
        value.forEach(function (item) {
          searchParams.append(key, item == null ? '' : String(item));
        });
        return;
      }
      if (value === undefined || value === null) {
        return;
      }
      searchParams.append(key, String(value));
    });

    return searchParams.toString();
  }

  /**
   * Drops bogus keys created when a full URL was passed to parseQueryString().
   */
  function sanitizeQueryParams(params) {
    Object.keys(params).forEach(function (key) {
      if (/^https?:\/\//i.test(key)) {
        delete params[key];
      }
    });
    return params;
  }

  /**
   * Parses query args from a full or partial URL.
   */
  function parseHrefQueryParams(href) {
    var queryIndex = href.indexOf('?');
    if (queryIndex === -1) {
      return {};
    }
    return Drupal.Views.parseQueryString(href.substring(queryIndex));
  }

  /**
   * Path portion of a URL without query string or hash.
   */
  function getPathBase(href) {
    var path = href;
    var hashIndex = path.indexOf('#');
    if (hashIndex !== -1) {
      path = path.substring(0, hashIndex);
    }
    var queryIndex = path.indexOf('?');
    if (queryIndex !== -1) {
      path = path.substring(0, queryIndex);
    }
    return path;
  }

  /**
   * Builds the exposed form selector for a view display.
   */
  function getExposedFormSelector(viewName, displayId) {
    return '#views-exposed-form-' + viewName.replace(/_/g, '-') + '-' + displayId.replace(/_/g, '-');
  }

})(Drupal, once, drupalSettings);
