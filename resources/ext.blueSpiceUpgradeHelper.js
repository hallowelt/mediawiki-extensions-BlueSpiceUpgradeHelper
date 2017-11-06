( function ( mw, $ ) {
	$( '#insert_token' ).click( function () {
		console.log( 'clicked' );
		$( '.token-process' ).show();
	} );
	$( '#close_token_input' ).click( function () {
		console.log( 'clicked' );
		$( '.token-process' ).hide();
	} );
	$('#token_input').on('input', function(){
		//send data to api, return check result
		console.log($('#token_input').val());
	});
}( mediaWiki, jQuery ) );
