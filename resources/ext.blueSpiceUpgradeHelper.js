( function ( mw, $ ) {
	$( '#insert_token' ).click( function () {
		$( '.token-process' ).show();
	} );
	$( '#close_token_input' ).click( function () {
		$( '.token-process' ).hide();
	} );
	$( '#token_input' ).on( 'input', function () {
		//send data to api, return check result
		var api = new mw.Api();
		var downloadToken = $( '#token_input' ).val();
		api.postWithToken( 'csrf', {
			action: 'bs-subscription-manager',
			task: 'parsetoken',
			taskData: JSON.stringify( { token: downloadToken } )
		} ).done( function ( data ) {
			console.log( data );
		} ).fail( function ( data, response ) {
			console.log( data );
			console.log( response );
		} );

	} );
}( mediaWiki, jQuery ) );
