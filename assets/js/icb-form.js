jQuery(document).ready(function($) {

	// Do nothing if there is no Gravity form
	if(!$('.gform_wrapper form').length) return;

	// Hide the input fields
	$('.icb_to_send').attr('style', 'display:none!important');

	// Submit button
	var $_submit_button = $('.gform_wrapper').find('input[type=submit]');
	
	// Spinner
	var $_spinner = '<img class="spinner" src="https://www.icb.org.za/wp-admin/images/spinner.gif" />';

	// Array of email addresses
	var emails = [];

	// When a checkbox is changed
	$('#gform_wrapper_3 .gfield_checkbox input').on('change', function() {
	    // remove existing spinner
	    if ($('.spinner').length) {
	        $('.spinner').remove();   
	    }
	    $_submit_button.after($_spinner);

		// Disable the submit button
		$_submit_button.attr('disabled', true).css({'opacity': 0.4, 'cursor': 'not-allowed'});

		// Do nothing if there is no value
		if(!$(this).val()) return;

		// Make the post request
		$.post(aquaaid.ajax_url, {action: 'aa_ajax_fetch_from_db', aa_userInput: $(this).val()}, '', 'json')
			.then(function(data) {
				// If there is no email, do nothing, will send to default
				if (!data) { 
				    $('.spinner').remove();
				    $_submit_button.attr('disabled', false).css({'opacity': 1, 'cursor': 'pointer'});
				    return false; 
				}

				// Location string containing email address
				var string = data[0].location_extrafields;

				// Regex for extracting email address from location string
				var regex = /(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))/;

				// The matched regex
				var matches = regex.exec(string);

				// If there are no matching email addresses
				if (!matches) { 
				    matches = ['support@icb.org.za'];
				}

				// The matching email address
				var to_send = matches[0];

				// Make sure there are no duplicates
				if (emails.includes(to_send)) {
					// Remove from array if unselected
					var index = emails.indexOf(to_send);

					if (index > -1) {
						emails.splice(index, 1);
					}
				} else {
					// Otherwise add to array
					emails.push(to_send);
				}

				// join the array and add to the input feild
				$('.icb_to_send input').val(emails.join());
				
				// Re-enable submit button
				$_submit_button.attr('disabled', false).css({'opacity': 1, 'cursor': 'pointer'});
				
				// Remove the spinner
				$('.spinner').remove();
			});
	});

});


// Polyfill for .includes for older browsers
// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes) {
  Object.defineProperty(Array.prototype, 'includes', {
    value: function(searchElement, fromIndex) {

      if (this == null) {
        throw new TypeError('"this" is null or not defined');
      }

      // 1. Let O be ? ToObject(this value).
      var o = Object(this);

      // 2. Let len be ? ToLength(? Get(O, "length")).
      var len = o.length >>> 0;

      // 3. If len is 0, return false.
      if (len === 0) {
        return false;
      }

      // 4. Let n be ? ToInteger(fromIndex).
      //    (If fromIndex is undefined, this step produces the value 0.)
      var n = fromIndex | 0;

      // 5. If n ≥ 0, then
      //  a. Let k be n.
      // 6. Else n < 0,
      //  a. Let k be len + n.
      //  b. If k < 0, let k be 0.
      var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

      function sameValueZero(x, y) {
        return x === y || (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y));
      }

      // 7. Repeat, while k < len
      while (k < len) {
        // a. Let elementK be the result of ? Get(O, ! ToString(k)).
        // b. If SameValueZero(searchElement, elementK) is true, return true.
        if (sameValueZero(o[k], searchElement)) {
          return true;
        }
        // c. Increase k by 1. 
        k++;
      }

      // 8. Return false
      return false;
    }
  });
}