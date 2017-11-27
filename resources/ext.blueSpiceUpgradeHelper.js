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

	$('.button-do-upgrade').click(function(){
		var api = new mw.Api();
		var downloadToken = $( '#token_input' ).val();
		api.postWithToken( 'csrf', {
			action: 'bs-subscription-manager',
			task: 'triggerUpgrade',
			taskData: JSON.stringify( { token: downloadToken } )
		} ).done( function ( data ){
			if(data.success){
				console.log("upgrade process started");
				$('.button-do-upgrade').hide();
				$('.upgrade_status_element').show();
			}else{
				console.log("error while starting upgrade process");
			}
		});
	});

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
			$('#token_checkup_result').show();
			if(data.success === false){
				$('.tocken_check_result').html( mw.message( 'bs-upgradehelper-token-check-result-error' ).text() );
				$('.token_data').empty();
				$(".button-do-upgrade").hide();
				return;
			}else{
				$('.tocken_check_result').html( mw.message('bs-upgradehelper-token-check-result-ok' ).text() );
				$(".button-do-upgrade").show();
			}
			myTemplate = mw.template.get( 'ext.blueSpiceUpgradeHelper.base', 'VersionOverviewSingle.mustache' );
			templateData = {
				package: data.payload.response_data.package_manifest.package,
				versionName: data.payload.response_data.package_manifest.versionName,
				package_limited: 0,
				supportHours: 0,
				max_user: data.payload.token_data.max_user,
				adminUsername: mw.config.get( 'wgUserName' ),
				package_label: mw.message( 'bs-upgradehelper-package-term-label' ).text(),
				licensedUsers_label: mw.message( 'bs-upgradehelper-package-licensed-users-label' ).text()
			};
			var html = myTemplate.render( templateData );
			$('.token_data').html(html);
		} ).fail( function ( data, response ) {
			console.log( data );
			console.log( response );
		} );

	} );
	var sLang = mw.config.get("wgUserLanguage");
	$("#compare-bluespice").load("../extensions/BlueSpiceUpgradeHelper/webservices/versioncompare.php?lang=" + sLang + " #main");

	$(".close-button").click(function(){
		$( '.token-process' ).hide();
	});
}( mediaWiki, jQuery ) );
