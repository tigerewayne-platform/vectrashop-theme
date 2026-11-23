/* Office clocks: local time, and open or closed (Mon to Fri, 08:00 to 17:00). */
( function () {
	function run() {
		document.querySelectorAll( '.vg-otime' ).forEach( function ( el ) {
			el.textContent = new Date().toLocaleTimeString( 'en-ZA', { hour: '2-digit', minute: '2-digit', timeZone: el.dataset.tz } );
		} );
		document.querySelectorAll( '.vg-ostate' ).forEach( function ( el ) {
			var p = new Intl.DateTimeFormat( 'en-GB', { weekday: 'short', hour: 'numeric', hour12: false, timeZone: el.dataset.tz } ).formatToParts( new Date() );
			var wd = p.find( function ( x ) { return x.type === 'weekday'; } ).value;
			var hr = +p.find( function ( x ) { return x.type === 'hour'; } ).value;
			var open = [ 'Sat', 'Sun' ].indexOf( wd ) < 0 && hr >= 8 && hr < 17;
			el.textContent = open ? 'Open now' : 'Closed, opens 08:00';
			el.classList.toggle( 'is-open', open );
		} );
	}
	run();
	setInterval( run, 30000 );
} )();
