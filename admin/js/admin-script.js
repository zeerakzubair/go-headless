/**
 * GoHeadless Admin Scripts
 *
 * @package Headless_Mode
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initToggleVisibility();
		initEnableConfirmation();
		initResetConfirmation();
	} );

	/**
	 * Toggle visibility of dependent fields based on checkbox state.
	 */
	function initToggleVisibility() {
		var toggles = document.querySelectorAll( '[data-toggles]' );

		toggles.forEach( function ( toggle ) {
			var targetId = toggle.getAttribute( 'data-toggles' );
			var target = document.getElementById( targetId );

			if ( ! target ) {
				return;
			}

			function updateVisibility() {
				if ( toggle.checked ) {
					target.classList.remove( 'headless-mode-hidden' );
				} else {
					target.classList.add( 'headless-mode-hidden' );
				}
			}

			toggle.addEventListener( 'change', updateVisibility );
		} );
	}

	/**
	 * Confirm before enabling headless mode.
	 */
	function initEnableConfirmation() {
		var enableCheckbox = document.getElementById( 'headless_mode_enabled' );

		if ( ! enableCheckbox ) {
			return;
		}

		enableCheckbox.addEventListener( 'change', function () {
			if ( this.checked && typeof headlessModeAdmin !== 'undefined' ) {
				if ( ! window.confirm( headlessModeAdmin.confirmEnable ) ) { // eslint-disable-line no-alert
					this.checked = false;
				}
			}
		} );
	}

	/**
	 * Confirm before resetting to defaults.
	 */
	function initResetConfirmation() {
		var resetForm = document.querySelector( '.headless-mode-reset-form' );

		if ( ! resetForm ) {
			return;
		}

		resetForm.addEventListener( 'submit', function ( e ) {
			if ( typeof headlessModeAdmin !== 'undefined' ) {
				if ( ! window.confirm( headlessModeAdmin.confirmReset ) ) { // eslint-disable-line no-alert
					e.preventDefault();
				}
			}
		} );
	}
} )();
