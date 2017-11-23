( function ( mw, $ ) {
	$('.version-button-upgrade').click(function(){
		//$('body > *:not(.token-process)').css("filter","blur(3px)");
		$( '.token-process' ).show();
	});
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
			if(data.success === false){
				$('#token_checkup_result').hide();
				$('#token_checkup_result').empty();
				return;
			}
			myTemplate = mw.template.get( 'ext.blueSpiceUpgradeHelper.base', 'VersionOverviewSingle.mustache' );
			templateData = {
				package: data.payload.response_data.package_manifest.package,
				versionCode: data.payload.response_data.package_manifest.versionCode,
				package_limited: 0,
				supportHours: 0,
				adminUsername: mw.config.get( 'wgUserName' )
			};
			var html = myTemplate.render( templateData );
			$('#token_checkup_result').append(html).show();
		} ).fail( function ( data, response ) {
			console.log( data );
			console.log( response );
		} );

	} );
	$("#compare-bluespice").load("../extensions/BlueSpiceUpgradeHelper/webservices/versioncompare.php #main");
}( mediaWiki, jQuery ) );
