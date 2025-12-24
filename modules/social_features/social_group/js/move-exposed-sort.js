/**
 * @file
 * Behavior to move exposed sort form elements to the view header.
 */

(function (Drupal, once) {
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

      // Move results count from view header to extra area.
      self.insertViewsResultCount(context);
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
     */
    autoSubmitForm: function (element) {
      // Auto-submit functionality for all inputs and select elements
      // in a sorting-group.
      const formElements = element.querySelectorAll('input, select');

      formElements.forEach(function (formElement) {
        formElement.addEventListener('change', function (event) {
          const target = event.target;
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
          if (!form.closest('.views-exposed-form').hasAttribute('data-bef-auto-submit-full-form')) {
            return;
          }

          // Find the submitting button in the form and click it.
          const submitButton = form.querySelector('input[type="submit"], button[type="submit"]');

          if (submitButton) {
            submitButton.click();
          }
        });
      });
    },

    /**
     * Moves results count from view header to extra area with formatted plural.
     */
    insertViewsResultCount: function (context) {
      const resultsCountElement = document.querySelector('.view-header .results-count');
      const extraAreaElement = document.querySelector('.extra-area');

      if (!resultsCountElement || !extraAreaElement) {
        return;
      }

      // Get the count value from the result count element.
      const countText = resultsCountElement.textContent.trim();
      const count = parseInt(countText, 10);

      if (isNaN(count)) {
        return;
      }

      // Remove the original element from the view header.
      resultsCountElement.remove();

      // Create a themed span with formatted plural text.
      const formattedText = Drupal.formatPlural(count, '1 member', '@count members');
      // Insert into an extra area.
      extraAreaElement.innerHTML = Drupal.theme('viewsResultCount', formattedText);
    },
  };

  /**
   * Theme function for views' result count.
   *
   * @param {string} text
   *   The formatted plural text.
   *
   * @return {string}
   *   The themed HTML string.
   */
  Drupal.theme.viewsResultCount = function (text) {
    return '<span>' + text + '</span>';
  };

})(Drupal, once);
