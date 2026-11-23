/* Countdowns on quotes. The expiry time comes from the server; this only draws it. */
( function () {
	function left( ms ) {
		var t = Math.max( 0, ms - Date.now() );
		return { ms: t, d: Math.floor( t / 864e5 ), h: Math.floor( t / 36e5 ) % 24, m: Math.floor( t / 6e4 ) % 60, s: Math.floor( t / 1e3 ) % 60 };
	}
	function pad( n ) { return String( n ).padStart( 2, '0' ); }
	function tick() {
		document.querySelectorAll( '.vb-clock[data-expires]' ).forEach( function ( el ) {
			var t = left( +el.dataset.expires );
			if ( ! t.ms ) { el.textContent = 'Expired. Refresh to get a fresh quote.'; return; }
			el.innerHTML = [ [ t.d, 'days' ], [ t.h, 'hrs' ], [ t.m, 'min' ], [ t.s, 'sec' ] ].map( function ( p ) {
				return '<div><b>' + pad( p[ 0 ] ) + '</b><span>' + p[ 1 ] + '</span></div>';
			} ).join( '' );
		} );
		document.querySelectorAll( '.vb-left[data-expires]' ).forEach( function ( el ) {
			var t = left( +el.dataset.expires );
			el.textContent = t.ms ? t.d + 'd ' + t.h + 'h ' + t.m + 'm left' : 'Expired';
			el.classList.toggle( 'is-urgent', t.ms > 0 && t.ms < 864e5 );
		} );
	}
	tick();
	setInterval( tick, 1000 );
} )();
