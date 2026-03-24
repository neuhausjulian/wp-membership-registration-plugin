/**
 * Frontend form handler for the WP Membership Registration plugin.
 *
 * @file
 * @package WpMembershipRegistration
 */

/* global wmrForm, wp */
( function () {
	'use strict';

	var EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	/**
	 * Show an error message below a field, or clear it.
	 *
	 * @param {HTMLElement} field - Input, select, or textarea element.
	 * @param {string}      msg   - Error message (empty to clear).
	 */
	function setError( field, msg ) {
		var row = field.closest( '.wmr-field-row' );
		if ( ! row ) {
			return;
		}
		var span = row.querySelector( '.wmr-field-error' );
		if ( span ) {
			span.textContent = msg;
		}
		field.setAttribute( 'aria-invalid', msg ? 'true' : 'false' );
	}

	/**
	 * Validate all form fields. Returns true if valid.
	 *
	 * @param {HTMLFormElement} form
	 * @returns {boolean}
	 */
	function validate( form ) {
		var valid = true;

		// Clear previous errors.
		form.querySelectorAll( '.wmr-field-error' ).forEach(
			function ( span ) {
				span.textContent = '';
			}
		);
		form.querySelectorAll( '[aria-invalid]' ).forEach(
			function ( el ) {
				el.removeAttribute( 'aria-invalid' );
			}
		);

		// Validate each input/select/textarea with data-required.
		form.querySelectorAll( '[data-required]' ).forEach(
			function ( field ) {
				if ( field.type === 'checkbox' ) {
					if ( ! field.checked ) {
						setError( field, wp.i18n.__( 'Please confirm your consent.', 'wp-membership-registration' ) );
						valid = false;
					}
				} else {
					if ( ! field.value.trim() ) {
						setError( field, wp.i18n.__( 'This field is required.', 'wp-membership-registration' ) );
						valid = false;
					}
				}
			}
		);

		// Validate email format for fields with data-email.
		form.querySelectorAll( '[data-email]' ).forEach(
			function ( field ) {
				if ( field.value.trim() && ! EMAIL_REGEX.test( field.value.trim() ) ) {
						setError( field, wp.i18n.__( 'Please enter a valid email address.', 'wp-membership-registration' ) );
						valid = false;
				}
			}
		);

		return valid;
	}

	document.addEventListener(
		'DOMContentLoaded',
		function () {
			var form = document.getElementById( 'wmr-registration-form' );
			if ( ! form ) {
				return;
			}
			var container = form.closest( '.wmr-form-container' );

			form.addEventListener(
				'submit',
				function ( event ) {
					event.preventDefault();

					if ( ! validate( form ) ) {
						return;
					}

					var submitBtn = form.querySelector( '.wmr-submit-button' );
					if ( submitBtn ) {
						submitBtn.disabled = true;
					}

					var formData = new FormData( form );
					formData.set( 'action', 'wmr_submit_form' );
					formData.set( 'nonce', wmrForm.submitNonce );

					fetch(
						wmrForm.ajaxUrl,
						{
							method: 'POST',
							body: formData,
							credentials: 'same-origin',
						}
					)
					.then(
						function ( response ) {
							return response.json();
						}
					)
					.then(
						function ( data ) {
							if ( data.success ) {
								if ( container ) {
									var successData = data.data || {};
									var html        = '<div class="wmr-success-message">';
									html           += wmrForm.successMessage;

									if ( successData.member_email_sent ) {
											html += '<p class="wmr-email-note">' +
										wp.i18n.__( 'A copy has been sent to your email address.', 'wp-membership-registration' ) +
										'</p>';
									}

									if ( successData.pdf_url ) {
										html += '<p class="wmr-download-link">' +
											'<a href="' + successData.pdf_url + '" class="button wmr-download-btn">' +
											wp.i18n.__( 'Download your completed form now', 'wp-membership-registration' ) +
											'</a>' +
											'</p>';
									}

									html               += '</div>';
									container.innerHTML = html;
								}
							} else {
								var msg      =
									( data.data && data.data.message )
								? data.data.message
								: wp.i18n.__( 'An error occurred. Please try again.', 'wp-membership-registration' );
								var errorDiv = form.querySelector( '.wmr-submit-row' );
								if ( errorDiv ) {
									var existing = form.querySelector( '.wmr-submit-error' );
									if ( existing ) {
										existing.remove();
									}
									var errSpan         = document.createElement( 'span' );
									errSpan.className   = 'wmr-submit-error wmr-field-error';
									errSpan.textContent = msg;
									errorDiv.before( errSpan );
								}
								if ( submitBtn ) {
									submitBtn.disabled = false;
								}
							}
						}
					)
					.catch(
						function () {
							if ( submitBtn ) {
									submitBtn.disabled = false;
							}
						}
					);
				}
			);
		}
	);
}() );
