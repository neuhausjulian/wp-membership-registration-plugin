/**
 * Admin settings page JavaScript for WP Membership Registration.
 *
 * Dependencies (declared in SettingsPage::enqueue_scripts):
 *   jquery, jquery-ui-sortable, wp-color-picker
 *
 * Globals:
 *   wmrSettings  { ajaxUrl, testEmailNonce }
 *   wmrExistingFields  array of { label, type, required } — PHP-bootstrapped
 */

/* global wmrSettings, wmrExistingFields, wp */

jQuery( function ( $ ) {
	'use strict';

	// -------------------------------------------------------------------------
	// 1. Tab switching
	// -------------------------------------------------------------------------
	function activateTab( tab ) {
		$( '.wmr-tab-nav .nav-tab' ).removeClass( 'nav-tab-active' );
		$( '.wmr-tab-nav .nav-tab[data-tab="' + tab + '"]' ).addClass( 'nav-tab-active' );
		$( '.wmr-tab-panel' ).hide();
		$( '#wmr-tab-' + tab ).show();
	}

	$( '.wmr-tab-nav .nav-tab' ).on( 'click', function ( e ) {
		e.preventDefault();
		var tab = $( this ).data( 'tab' );
		sessionStorage.setItem( 'wmrActiveTab', tab );
		activateTab( tab );
	} );

	// Persist active tab across the settings-save redirect.
	$( '.wmr-tab-panel form' ).on( 'submit', function () {
		var activeTab = $( '.wmr-tab-nav .nav-tab-active' ).data( 'tab' );
		if ( activeTab ) {
			sessionStorage.setItem( 'wmrActiveTab', activeTab );
		}
	} );

	// Restore active tab on page load.
	var savedTab = sessionStorage.getItem( 'wmrActiveTab' );
	if ( savedTab && $( '#wmr-tab-' + savedTab ).length ) {
		activateTab( savedTab );
	}

	// -------------------------------------------------------------------------
	// 2. Field schema row rendering
	// -------------------------------------------------------------------------

	/**
	 * Build and append a single field row to the tbody.
	 *
	 * @param {Object} [field] Optional field data to populate the row.
	 * @param {string} [field.label]    Field label value.
	 * @param {string} [field.type]     Field type value (text|email|date|tel|textarea|select).
	 * @param {boolean} [field.required] Whether the field is required.
	 */
	function addRow( field ) {
		var row = $(
			'<tr>' +
				'<td><span class="dashicons dashicons-menu wmr-drag-handle" title="Drag to reorder"></span></td>' +
				'<td><input type="text" class="wmr-label widefat" value=""></td>' +
				'<td>' +
					'<select class="wmr-type">' +
						'<option value="text">Text</option>' +
						'<option value="email">Email</option>' +
						'<option value="date">Date</option>' +
						'<option value="tel">Phone</option>' +
						'<option value="textarea">Textarea</option>' +
						'<option value="select">Select</option>' +
					'</select>' +
				'</td>' +
				'<td><input type="checkbox" class="wmr-required"></td>' +
				'<td>' +
					'<button type="button" class="button wmr-delete-row" aria-label="Delete field">' +
						'<span class="dashicons dashicons-trash" aria-hidden="true"></span>' +
					'</button>' +
				'</td>' +
			'</tr>'
		);

		if ( field ) {
			row.find( '.wmr-label' ).val( field.label || '' );
			row.find( '.wmr-type' ).val( field.type || 'text' );
			if ( field.required ) {
				row.find( '.wmr-required' ).prop( 'checked', true );
			}
		}

		$( '#wmr-fields-tbody' ).append( row );
	}

	// Render existing fields from PHP-bootstrapped data, or show empty state.
	if ( typeof wmrExistingFields !== 'undefined' && wmrExistingFields.length > 0 ) {
		$.each( wmrExistingFields, function ( i, field ) {
			addRow( field );
		} );
	} else {
		$( '#wmr-fields-tbody' ).append(
			'<tr class="wmr-empty-state">' +
				'<td colspan="5">' +
					'<strong>No fields defined yet.</strong>' +
					'<br>Click Add Field to define the first registration form field.' +
				'</td>' +
			'</tr>'
		);
	}

	// -------------------------------------------------------------------------
	// 3. jQuery UI Sortable
	// -------------------------------------------------------------------------
	$( '#wmr-fields-tbody' ).sortable( {
		handle: '.wmr-drag-handle',
		axis: 'y',
		update: serializeSchema,
	} );

	// -------------------------------------------------------------------------
	// 4. Add Field button
	// -------------------------------------------------------------------------
	$( '#wmr-add-field' ).on( 'click', function () {
		// Remove empty state row if present.
		$( '#wmr-fields-tbody .wmr-empty-state' ).remove();

		addRow( { label: '', type: 'text', required: false } );
		serializeSchema();
	} );

	// -------------------------------------------------------------------------
	// 5. Delete row (delegated)
	// -------------------------------------------------------------------------
	$( '#wmr-fields-tbody' ).on( 'click', '.wmr-delete-row', function () {
		$( this ).closest( 'tr' ).remove();
		serializeSchema();
	} );

	// -------------------------------------------------------------------------
	// 6. serializeSchema — write current rows to hidden JSON input
	// -------------------------------------------------------------------------
	function serializeSchema() {
		var rows = [];
		$( '#wmr-fields-tbody tr:not(.wmr-empty-state)' ).each( function () {
			rows.push( {
				label:    $( this ).find( '.wmr-label' ).val(),
				type:     $( this ).find( '.wmr-type' ).val(),
				required: $( this ).find( '.wmr-required' ).is( ':checked' ),
			} );
		} );
		$( '#wmr-field-schema-json' ).val( JSON.stringify( rows ) );
	}

	// Serialize on form submit so hidden input is always current.
	$( '#wmr-tab-fields form' ).on( 'submit', serializeSchema );

	// Run once on load to populate the hidden input with existing data.
	serializeSchema();

	// -------------------------------------------------------------------------
	// 7. wp-color-picker init
	// -------------------------------------------------------------------------
	$( '.wmr-color-picker' ).wpColorPicker();

	// -------------------------------------------------------------------------
	// 8. wp.media logo picker
	// -------------------------------------------------------------------------
	var mediaFrame;

	// Show preview and remove button if a logo URL is already saved.
	if ( $( '#wmr-logo-url' ).val() ) {
		$( '#wmr-logo-preview' ).show();
		$( '#wmr-remove-logo' ).show();
	}

	$( '#wmr-select-logo' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media( {
			title:    'Select Club Logo',
			button:   { text: 'Use this image' },
			multiple: false,
			library:  { type: 'image' },
		} );

		mediaFrame.on( 'select', function () {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			$( '#wmr-logo-url' ).val( attachment.url );
			$( '#wmr-logo-preview' ).attr( 'src', attachment.url ).show();
			$( '#wmr-remove-logo' ).show();
		} );

		mediaFrame.open();
	} );

	$( '#wmr-remove-logo' ).on( 'click', function () {
		$( '#wmr-logo-url' ).val( '' );
		$( '#wmr-logo-preview' ).attr( 'src', '' ).hide();
		$( this ).hide();
	} );

	// -------------------------------------------------------------------------
	// 9. Send Test Email AJAX
	// -------------------------------------------------------------------------
	$( '#wmr-send-test-email' ).on( 'click', function () {
		var $button = $( this );
		var $result = $( '#wmr-test-email-result' );
		var originalHtml = $button.html();

		// Disable button and show in-flight state.
		$button.prop( 'disabled', true ).html(
			$button.find( '.dashicons' ).prop( 'outerHTML' ) + ' Sending\u2026'
		);

		// Clear previous result.
		$result.removeClass( 'wmr-inline-notice--success wmr-inline-notice--error' ).text( '' );

		$.post(
			wmrSettings.ajaxUrl,
			{
				action: 'wmr_send_test_email',
				nonce:  wmrSettings.testEmailNonce,
			},
			function ( response ) {
				if ( response.success === true ) {
					$result
						.addClass( 'wmr-inline-notice wmr-inline-notice--success' )
						.text( 'Test email sent.' );
				} else {
					var message = ( response.data && response.data.message )
						? response.data.message
						: 'An unknown error occurred.';
					$result
						.addClass( 'wmr-inline-notice wmr-inline-notice--error' )
						.text( message );
				}
			}
		).always( function () {
			$button.prop( 'disabled', false ).html( originalHtml );
		} );
	} );
} );
