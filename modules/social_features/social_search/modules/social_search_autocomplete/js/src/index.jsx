/**
 * @file
 * Javascript for the social search autocomplete.
 */
import React from "react";
import ReactDOM from "react-dom";
import SearchSuggestions from "./components/SearchSuggestions";

/**
 *
 * @param {string} text
 *   The text that suggestions should be fetched for.
 * @param {Function} callback
 *   The callback function that takes a search string and an array of suggestions.
 */
function fetchSuggestions(text, callback) {
  const suggestions = [
    {
      type: "Topic",
      label: "What is Open Social?",
      url: "http://social.localhost/node/1"
    },
    {
      type: "Member",
      label: "Ben Florez",
      url: "http://social.localhost/user/2"
    },
    {
      type: "Topic",
      label: "Welcome to the platform!",
      url: "http://social.localhost/node/1"
    },
    {
      type: "Event",
      label: "Celebrate Open Social's pre-birthday",
      url: "http://social.localhost/node/1",
      tags: ["You are enrolled"]
    },
    {
      type: "Discussion",
      label: "This should be good!",
      url: "http://social.localhost/node/1"
    },
    {
      type: "Topic",
      label: "This platform will grow big",
      url: "http://social.localhost/node/1"
    },
    {
      type: "Event",
      label: "Celebrate Open Social's 3rd birthday",
      url: "http://social.localhost/node/1"
    }
  ];

  if (!text.length) {
    callback(text, []);
  } else {
    setTimeout(() => {
      callback(
        text,
        suggestions.filter(
          v => v.label.toLowerCase().indexOf(text.toLowerCase()) !== -1
        )
      );
    }, 500);
  }
}

// eslint-ignore-next-line
(function($, Drupal) {
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

  /**
   * Initialises the React rendering for the search suggestions.
   *
   * Handles input events on the search field and React rendering in the
   * provided container.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.initSocialSearchAutocomplete = {
    attach(context) {
      // TODO: Only load React if it's not yet loaded.
      // TODO: Ideally only load when search is visible.

      // Find all search suggestion containers to set-up.
      const $searchSuggests = $(".social-search-suggestions", context);

      if (!$searchSuggests.length) {
        return;
      }

      $searchSuggests.each((index, element) => {
        // Resolve the search field for this suggestions box.
        const $searchField = getSearchFieldFor(element);

        // Create a callback scheduler specific to this search field.
        const scheduleUpdate = createUpdateScheduler();

        // Our React render function for this search suggestion box.
        const rerender = (query, suggestions) => {
          ReactDOM.render(
            <SearchSuggestions query={query} suggestions={suggestions} />,
            element
          );
        };

        $searchField.on("keyup", e => {
          const searchText = e.target.value;

          // On keyup, wait a moment before fetching new suggestions so we
          // don't fetch on each keystroke.
          scheduleUpdate(() => {
            fetchSuggestions(searchText, rerender);
          }, 200);
        });

        // Render our suggestions for the first time.
        rerender("", []);
      });
    }
  };
})(jQuery, Drupal);
