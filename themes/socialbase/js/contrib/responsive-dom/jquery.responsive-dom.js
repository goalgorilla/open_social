/**
 * A jQuery plugin to move elements in the DOM based on media queries
 * @param options
 */
(function(factory) {
	'use strict';

	if (typeof define === 'function' && define.amd) {
		define(['jquery'], factory);
	} else {
		factory(jQuery);
	}
}(function($) {
	'use strict';

	$.fn.responsiveDom = function (options) {
		// The settings object provides default settings.
		// The options argument can override them.
		var settings = $.extend({
			appendTo: 'body',             // The provided object will be moved here...
			mediaQuery: '(min-width: 0)', // ...when this media query is true.
			callback: null                // If provided, the callback will run after DOM updates.
		}, options);

		var sourceEl = this;
		var placeholder = null;
		var isMoved = false;

		/**
		 * Initializes the plugin
		 */
		var init = function() {
			// Update the DOM now...
			updateDom();

			// ...and again when the window resizes
			$(window).on('resize.responsiveDom', debounce(updateDom, 100));
		};

		/**
		 * Moves or reverts element DOM position if the media query conditions are met
		 */
		var updateDom = function() {
			// Check if media query conditions are met
			if (!isMoved && matchMedia(settings.mediaQuery).matches) {
				moveElement();
				isMoved = true;
			} else if (isMoved && !matchMedia(settings.mediaQuery).matches) {
				revertElement();
				isMoved = false;
			} else {
				return;
			}

			// Run callback function if provided
			if (typeof settings.callback === 'function') {
				settings.callback(isMoved);
			}
		};

		/**
		 * Creates a placeholder at the element's current DOM position and moves the
		 * element to its new position
		 */
		var moveElement = function() {
			// Verify the source element still exists in the DOM
			if (!document.contains || document.contains(sourceEl[0])) {
				// Create placeholder so we can move it back if needed
				placeholder = $('<span class="js-responsive-dom-placeholder"/>');
				sourceEl.after(placeholder);

				// Move element
				$(settings.appendTo).eq(0).append(sourceEl);
			}
		};

		/**
		 * Returns element to its previous position in the DOM and removes the
		 * placeholder element
		 */
		var revertElement = function() {
			// Verify the placeholder still exists in the DOM
			if (placeholder !== null && (!document.contains || document.contains(placeholder[0]))) {
				// Move element back and remove placeholder
				placeholder.after(sourceEl);

				placeholder.remove();
				placeholder = null;
			}
		};

		/**
		 * Returns a function that cannot be called in succession unless a specified
		 * amount of time has passed
		 * @param func - The function to debounce
		 * @param wait - The wait time (ms) before running the function again
		 * @returns The debounced function
		 */
		var debounce = function(func, wait) {
			var timeout;

			return function() {
				clearTimeout(timeout);

				timeout = setTimeout(function() {
					func();
				}, wait);
			};
		};

		// Let's go!
		init();

		// Always return the target object to allow chaining.
		return this;
	};
}));
