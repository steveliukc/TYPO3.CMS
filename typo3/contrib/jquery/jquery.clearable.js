/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This file provides a jQuery plugin for generating 'clearable' input fields.
 * These fields show a "clear"-button when someone hovers over them and
 * they are not empty.
 * Options:
 *   * 'onClear':	Function that is called after clearing. Takes no arguments,
 *					'this' is set to the clearable input element. Defaults to an
 *					empty function.
 */
(function($) {
	$.fn.clearable = function(options) {

		var defaults = {
			'onClear': function() {}
		};

		// Merge defaults and given options. Given options have higher priority
		// because they are the last argument.
		var settings = $.extend({}, defaults, options);

		// Iterate over the list of inputs and make each clearable. Return
		// the list to allow chaining.
		return this.each(function() {

			// The input element to make clearable.
			var $input = $(this);

			// make sure the input field is not used twice for a clearable
			if (!$input.data('clearable')) {
				$input.data('clearable', 'loaded');

				var $inputFieldWithValue = $input;
				// the hidden field that holds the real data, used in FormEngine
				if ($input.next('input[type=hidden]').length) {
					$inputFieldWithValue = $input.next('input[type=hidden]');
				}

				// Wrap it with a div and add a span that is the trigger for
				// clearing.
				$input.wrap('<div class="t3-clearable-wrapper"/>');
				$input.after('<span class="t3-icon t3-icon-actions t3-icon-actions-input t3-icon-input-clear t3-input-clearer"/>');
				$input.addClass('t3-clearable');

				var $wrapper = $input.parent();
				var $clearer = $input.next();

				// Add some data to the wrapper indicating if it is currently being
				// hovered or not.
				$input.data('isHovering', false);
				$wrapper.hover(function() {
					$input.data('isHovering', true);
				}, function() {
					$input.data('isHovering', false);
				});

				// Register a listener the various events triggering the clearer to
				// be shown or hidden.
				var handler = function() {
					var value = $inputFieldWithValue.val();
					var hasEmptyValue = (value.length === 0);
					if (value == "0" && $inputFieldWithValue.closest('.date').length) {
						hasEmptyValue = true;
					}
					// only show the clearing button if the value is set, or if the value is not "0" on a datetime field
					if ($input.data('isHovering') && !hasEmptyValue) {
						$clearer.show();
					} else {
						$clearer.hide();
					}
				};

				$wrapper.on('mouseover mouseout', handler);
				$input.on('keypress', handler);


				// The actual clearing action. Focus the input element afterwards,
				// the user probably wants to type into it after clearing.
				$clearer.click(function() {
					$input.val('').change().focus();
					handler();

					if ('function' === typeof(settings.onClear)) {
						settings.onClear.call($input.get());
					}
				});

				// Initialize the clearer icon
				handler();
			}
		});
	};
})(jQuery || TYPO3.jQuery);
