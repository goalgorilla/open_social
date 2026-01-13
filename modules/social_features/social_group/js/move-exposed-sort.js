/**
 * @file
 * Behavior to move exposed sort form elements to the view header.
 */

(function (Drupal, $, once) {
  'use strict';

  /**
   * Moves elements with ".sorting-group" class to ".view-header".
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior to move exposed form elements.
   */
  Drupal.behaviors.moveExposedSortToViewHeader = {
    attach: function (context, settings) {
      // Find all elements with the form="edit-views-exposed-form" attribute.
      const sortingGroup = once('move-exposed-form', '.sorting-group', context);
      const self = this;

      sortingGroup.forEach(function (element) {
        // Find the view header element.
        const block = document.querySelector('.title-with-sorts');

        // If both element and view header exist, append the element to view header.
        if (block) {
          block.appendChild(element);
        }

        const selectElement = element.querySelector('select[name="sort_by"]');

        // Ensure selectElement and block exist before proceeding.
        if (!selectElement || !block) {
          return;
        }

        // Update "sort" input data on change.
        selectElement.addEventListener('change', () => self.updateOptions(selectElement, block));

        // Update "sort" input data on initial load.
        self.updateOptions(selectElement, block);

        // Triggers auto-submit functionality on each change of a form element.
        self.autoSubmitForm(element);
      });

      // Update the title with the member count on initial load.
      const viewsInContext =
        (context.querySelectorAll && context.querySelectorAll('.view')) ||
        document.querySelectorAll('.view');

      viewsInContext.forEach(function (viewElement) {
        self.insertViewsResultCount(viewElement);
      });

      // Update the title with the member count after AJAX search.
      once('move-exposed-sort-ajax', 'body', context).forEach(function () {
        // Uses global jQuery from Drupal core.
        if (typeof $ !== 'undefined') {
          $(document).ajaxComplete(function (event, xhr, settings) {
            // Only react to Views AJAX requests.
            if (settings && settings.extraData && settings.extraData.view_name) {
              const views = document.querySelectorAll('.view');
              views.forEach(function (viewElement) {
                Drupal.behaviors.moveExposedSortToViewHeader.insertViewsResultCount(viewElement);
              });
            }
          });
        }
      });
    },

    /**
     * Update "order" inputs according to the selects options attributes.
     */
    updateOptions: function (selectElement, newPlace) {
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      if (!selectedOption) {
        return;
      }

      const selectedValue = selectedOption.value;
      if (!selectedValue) {
        return;
      }

      // Get label values from a selected option.
      const ascLabel = selectedOption.getAttribute('data-sort-order-label-asc');
      const descLabel = selectedOption.getAttribute('data-sort-order-label-desc');
      const defaultSort = selectedOption.getAttribute('data-sort-order-default');
      const isHidden = selectedOption.hasAttribute('data-sort-order-hide');

      const sortOrderInputs = newPlace.querySelectorAll('input[name="sort_order"]');

      sortOrderInputs.forEach(function (input) {
        const label = newPlace.querySelector('label[for="' + input.id + '"]');
        if (label && !isHidden) {
          if (input.getAttribute('value') === 'ASC' && ascLabel) {
            label.textContent = ascLabel;
          }
          if (input.getAttribute('value') === 'DESC' && descLabel) {
            label.textContent = descLabel;
          }
        }

        if (isHidden) {
          input.closest('.form-item').classList.add('hidden');
        }
        else {
          input.closest('.form-item').classList.remove('hidden');
        }

        if (input.getAttribute('value') === defaultSort) {
          input.click();
        }
      });
    },

    /**
     * Automatically submits the form when a form element changes.
     *
     * For text inputs, uses debounced input event for real-time search.
     * For select/radio elements, uses change event for immediate submit.
     */
    autoSubmitForm: function (element) {
      const self = this;
      // Auto-submit functionality for all inputs and select elements
      // in a sorting-group.
      const formElements = element.querySelectorAll('input, select');

      formElements.forEach(function (formElement) {
        // For text inputs, add a debounced input event for real-time search.
        if (formElement.type === 'text' || formElement.type === 'search') {
          let debounceTimer;
          const delay = 100;
          const minLength = 1;

          formElement.addEventListener('input', function (event) {
            clearTimeout(debounceTimer);
            const value = event.target.value;

            // Only auto-submit if the value is empty or meets
            // the minimum length.
            if (value.length > 0 && value.length < minLength) {
              return;
            }

            debounceTimer = setTimeout(function () {
              self.submitForm(event.target);
            }, delay);
          });
        }

        // Keep change event for select/radio and as fallback for text inputs.
        formElement.addEventListener('change', function (event) {
          self.submitForm(event.target);
        });
      });
    },

    /**
     * Submits the form associated with the given element.
     *
     * @param {HTMLElement} target
     *   The form element that triggered the submit.
     */
    submitForm: function (target) {
      const formId = target.getAttribute('form');
      if (!formId) {
        return;
      }

      // Find the form by ID.
      const form = document.getElementById(formId);
      if (!form) {
        return;
      }

      // Make sure auto-submit is enabled.
      const exposedForm = form.closest('.views-exposed-form');
      if (!exposedForm || !exposedForm.hasAttribute('data-bef-auto-submit-full-form')) {
        return;
      }

      // Find the submitting button in the form and click it.
      const submitButton = form.querySelector('input[type="submit"], button[type="submit"]');

      if (submitButton) {
        submitButton.click();
      }
    },

    /**
     * Updates the title with the member count from search results.
     *
     * @param {HTMLElement} viewElement
     *   The root element of the view.
     */
    insertViewsResultCount: function (viewElement) {
      const titleElement = document.querySelector('.sorting-group__title');

      if (!titleElement || !viewElement) {
        return;
      }

      let count = 0;

      // Try to find an existing results-count element within this view.
      let resultsCountElement = viewElement.querySelector('.view-header .results-count');

      if (resultsCountElement) {
        // Get the count value from the result count element.
        const countText = resultsCountElement.textContent.trim();
        const parsedCount = parseInt(countText, 10);

        if (!isNaN(parsedCount)) {
          count = parsedCount;
        }

        // Remove the original element from the view header.
        resultsCountElement.remove();
      }
      else {
        // Fallback when there is no result.
        const rows = viewElement.querySelectorAll('.view-content .views-row');
        count = rows.length;
      }

      // Create formatted plural text and update the title element.
      const formattedText = Drupal.formatPlural(count, '1 member', '@count members');
      // Update the title with the count. Include the divider to maintain the
      // visual separator between the count and the search field.
      titleElement.innerHTML = formattedText + ' <span class="participants_title_divider">|</span>';
    },
  };

})(Drupal, jQuery, once);
