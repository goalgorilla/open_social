/**
 * @file
 * Javascript for the social search autocomplete.
 */
import React from "react";
import ReactDOM from "react-dom";
import SearchSuggestions from "./components/SearchSuggestions";
import Spinner from './components/Spinner';

(($, Drupal) => {
  /**
   * Returns a function that can be used to fetch suggestions from the given url.
   *
   * @param {string} baseUrl
   *   The base url for the Drupal website, ending in /.
   * @param {string} apiUrl
   *   The url to use for fetching suggestions.
   * @return {Function}
   *   The function to call with search text and callbacks.
   */
  function createSuggestionFetcher(baseUrl, apiUrl) {
    let previousRequest = null;

    /**
     *
     * @param {string} text
     *   The text that suggestions should be fetched for.
     * @param {Function} callback
     *   The callback function that takes a search string and an array of suggestions.
     */
    return (text, callback) => {
      // If there was a previous, uncompleted request then we abort it so we
      // prevent the flashing of changing search results. Any finished request
      // can be ignored as it has already been processed.
      if (previousRequest !== null) {
        if (previousRequest.readyState !== 4) {
          previousRequest.abort();
        }
        previousRequest = null;
      }

      if (!text.length) {
        callback(text, []);
      } else {
        previousRequest = $.get({
          url: `${apiUrl}/${encodeURIComponent(text)}`,
          headers: {
            Accept: "application/json",
            "Content-Type": "application/hal+json",
          },
          success(data) {
            // Convert the content paths to absolute paths.
            const suggestions = data.map(s => ({
              type: s.type,
              label: s.label,
              url: baseUrl + s.path,
            }));

            callback(text, suggestions);
          },
        });
      }
    };
  }

  /**
   * Returns a function to overwrite the previously scheduled callback.
   *
   * The returned function can be used instead of setTimeout and will
   * automatically clear the timeout that was set with this scheduler if it had
   * not yet been fired.
   *
   * @return {Function}
   *   A unique updateScheduler that can be used like setTimeout.
   */
  function createUpdateScheduler() {
    let scheduledUpdate = null;
    return (handler, timeout) => {
      if (scheduledUpdate) {
        clearTimeout(scheduledUpdate);
        scheduledUpdate = null;
      }
      scheduledUpdate = setTimeout(() => {
        scheduledUpdate = null;
        handler();
      }, timeout);
    };
  }

  /**
   * Returns the search field corresponding to a search suggestion field.
   *
   * @param {DOMElement} element
   *   The element for which to find the search field.
   * @return {jQuery}
   *   A jQuery reference to the search field.
   */
  function getSearchFieldFor(element) {
    const searchFieldId = $(element).attr("data-search-suggestions-for");

    if (!searchFieldId) {
      throw new Error(
        `Search suggestion element does not have an associated search field (${element}).`
      );
    }

    const $searchField = $(`#${searchFieldId}`);

    if (!$searchField.length) {
      throw new Error(
        `Could not find search element with id '${searchFieldId}'`
      );
    }

    return $searchField.first();
  }

  // window.location.origin is not supported in IE so we use this instead.
  const wlOrigin = `${window.location.protocol}//${window.location.hostname}${
    window.location.port ? `:${window.location.port}` : ""
  }`;

  /**
   * Initialises the React rendering for the search suggestions.
   *
   * Handles input events on the search field and React rendering in the
   * provided container.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.initSocialSearchAutocomplete = {
    attach(context, settings) {
      // TODO: Only load React if it's not yet loaded.
      // TODO: Ideally only load when search is visible.

      // Find all search suggestion containers to set-up.
      const $searchSuggests = $(".social-search-suggestions", context);

      if (!$searchSuggests.length) {
        return;
      }

      const fetchSuggestions = createSuggestionFetcher(
        wlOrigin + settings.socialSearchAutocomplete.basePath,
        settings.socialSearchAutocomplete.searchApiUrl
      );

      $searchSuggests.each((index, element) => {
        // Resolve the search field for this suggestions box.
        const $searchField = getSearchFieldFor(element);

        // Create a callback scheduler specific to this search field.
        const scheduleUpdate = createUpdateScheduler();

        // Our React render function for this search suggestion box.
        const rerender = (query, suggestions) => {
          ReactDOM.render(
            <SearchSuggestions
              searchBase={settings.socialSearchAutocomplete.searchPath}
              query={query}
              suggestions={suggestions}
            />,
            element
          );
        };

        // Store the input value to detect actual changes.
        let lastValue = null;

        // Store the form group for this searchField. This is used to find the
        // search results for keyboard navigation.
        const $formGroup = $searchField.parent();

        $searchField.on("keyup", e => {
          const searchText = e.target.value;

          if (searchText !== lastValue) {
            // When there is a change, wait a short moment for more changes.
            scheduleUpdate(() => {
              // Render our spinner. It will get overwritten by our results.
              ReactDOM.render(<Spinner />, element);
              fetchSuggestions(searchText, rerender);
            }, 200);

            lastValue = searchText;
          }

          // Handle up arrow for keyboard navigation.
          if (e.keyCode === 38) {
            // Focus the last available search suggestion, if any.
            $formGroup
              .find(".search-suggestions .search-suggestion")
              .get(-1)
              .focus();
          }
          // Handle down arrow for keyboard navigation.
          else if (e.keyCode === 40) {
            $formGroup
              .find(".search-suggestions .search-suggestion")
              .get(0)
              .focus();
          }
        });

        // Implement keyboard navigation. This could possible be better handled
        // within React itself but then we'd also want to control the search
        // input element so we're leaving that for a later date.
        $formGroup.find(".social-search-suggestions").on("keyup", e => {
          // Handle up arrow for keyboard navigation.
          if (e.keyCode === 38) {
            let $target = $(e.target).prev();

            // Move to the search index if we go beyond the top result.
            if (!$target.length) {
              if (
                $(e.target)
                  .parent()
                  .is(".search-suggestions__all")
              ) {
                $target = $(".search-suggestions .search-suggestion").last();
              } else {
                $target = $searchField;
              }
            }

            $target.focus();
          } else if (e.keyCode === 40) {
            let $target = $(e.target).next();

            // Wrap around to all search results button or search field.
            if (!$target.length) {
              if (
                $(e.target)
                  .parent()
                  .is(".search-suggestions__all")
              ) {
                $target = $searchField;
              } else {
                $target = $(".search-suggestions__all a").first();
              }
            }

            $target.focus();
          }
        });

        // Render our suggestions for the first time.
        rerender("", []);
      });
    },
  };
})(jQuery, Drupal);
