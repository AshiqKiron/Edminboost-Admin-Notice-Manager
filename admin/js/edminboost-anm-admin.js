/**
 * Admin Notice Manager — plugin admin scripts.
 *
 * @package EdminBoost\AdminNoticeManager
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var wrap = document.querySelector( '.edminboost-anm-wrap' );

		if ( ! wrap ) {
			return;
		}

		wrap.classList.add( 'is-ready' );
	} );
}() );
