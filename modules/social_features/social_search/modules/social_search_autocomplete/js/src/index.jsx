/**
 * @file
 * Javascript for the social search autocomplete.
 */
import React from "react";
import ReactDOM from "react-dom";
import App from "./components/App";

(function($, Drupal) {
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
        console.log("Attached");
        // TODO: Only load React if it's not yet loaded.
        // TODO: Ideally only load when search is visible.

        // Find all search suggestion containers to set-up.
        const $search_suggests = $('.social-search-suggestions', context);

        console.log("Looking for", $search_suggests);

        if (!$search_suggests.length) {
          return;
        }



        $search_suggests.each((index, element) => {
          // Resolve the search field for this suggestions box.
          const search_field_id = $(element).attr('data-search-suggestions-for');

          if (!search_field_id) {
            throw new Error(`Search suggestion element does not have an associated search field (${element}).`);
          }

          const $search_field = $(`#${search_field_id}`).get(0);

          if (!$search_field) {
            throw new Error(`Could not find search element with id '${search_field_id}'`);
          }

          // TODO: Add event listener to search field.

          // Render our app.
          console.log("Rendering in", element);
          ReactDOM.render(<App />, element);
        });

      },
  };
})(jQuery, Drupal);
