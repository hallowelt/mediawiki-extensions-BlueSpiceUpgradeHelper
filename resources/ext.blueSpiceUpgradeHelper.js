( function ( mw, $ ) {

	// slight update to account for browsers not supporting e.which
	function disableF5( e ) {
		if ( ( e.which || e.keyCode ) === 116 )
			e.preventDefault();
	}

	$( '.version-button-upgrade-handler' ).click( function () {
		//$('body > *:not(.token-process)').css("filter","blur(3px)");
		$( '.token-process' ).show();

	} );
	$( '#insert_token' ).click( function () {
		$( '.token-process' ).show();
	} );
	$( '#close_token_input' ).click( function () {
		$( '.token-process' ).hide();
	} );

	$( '.button-do-upgrade' ).click( function () {
		$( ".close-button" ).hide();
		$( '.button-do-upgrade' ).hide();
		$( '.upgrade_status_element' ).show();
		$( '#token_input' ).prop( "disabled", true );
		$( document ).on( "keydown", disableF5 );

		var api = new mw.Api();
		var downloadToken = $( '#token_input' ).val();
		api.postWithToken( 'csrf', {
			action: 'bs-subscription-manager',
			task: 'triggerUpgrade',
			taskData: JSON.stringify( { token: downloadToken } )
		} ).done( function ( data ) {
			if ( data.success ) {
				console.log( "upgrade process started" );

			} else {
				console.log( "error while starting upgrade process" );

			}
		} );
	} );

	var titleLogin = new mw.Title( 'Userlogin', -1 );
	$( '#reload_wiki' ).click( function () {
		window.location.href = titleLogin.getUrl( { redirect: 'Special:SubscriptionManager' } );
	} );

	var getUrl = window.location;
	const socket = io( 'http://' + getUrl.host + ':3000' );
	var errorsOnUpgrade = false;
	var statusElements = [ 'token_available', 'token_check', 'download', 'backup', 'do_upgrade' ];
	var fileTypes = [ 'task', 'error' ];
	var actionTypes = [ 'created', 'deleted' ];
	socket.on( 'upgradehelper statusupdate', function ( data ) {
		if ( data.file === undefined ) {
			return;
		}
		var res = data.file.split( "." );
		var iconText;
		if ( res[1] === 'task' && statusElements.indexOf( res[0] ) !== -1 && data.action === "created" ) {
			iconText = $( '<li class="' + res[0] + '"><div class="fa fa-spinner fa-spin"></div> ' + res[0] + '</li>' );
			$( '#upgrade_status' ).append( iconText );
		} else if ( res[1] === 'task' && statusElements.indexOf( res[0] ) !== -1 && data.action === "deleted" ) {
			$( '.' + res[0] + ' .fa-spinner' ).attr( 'class', 'fa fa-check' );
		} else if ( res[1] === 'error' && statusElements.indexOf( res[0] ) !== -1 && data.action === "created" ) {
			$( '.' + res[0] + ' .fa-spinner' ).attr( 'class', 'fa fa-exclamation-triangle' );
		}

		if ( data.action === "created" && data.file.indexOf( '.error' ) !== -1 ) {
			errorsOnUpgrade = true;
			$( ".close-button" ).show();
		}

		if ( data.action === "deleted" && data.file === "upgrade.task" && !errorsOnUpgrade ) {
			$( '#upgrade_complete' ).show();
			$( '#reload_wiki' ).show();
			$( document ).off( "keydown", disableF5 );
		} else if ( data.action === "deleted" && data.file === "upgrade_token_only.task" && !errorsOnUpgrade ) {
			$( '#upgrade_complete' ).show();
			$( '#reload_wiki' ).show();
			$( document ).off( "keydown", disableF5 );
		} else if ( data.action === "deleted" && data.file === "upgrade.task" && errorsOnUpgrade ) {
			$( '#upgrade_error' ).show();
			$( document ).off( "keydown", disableF5 );
		}
		//window.scrollTo( 0, document.body.scrollHeight );
	} );

	$( '.version-button-resign-handler' ).click( function () {
		OO.ui.confirm( 'Are you sure?' ).done( function ( confirmed ) {
			if ( confirmed ) {
				var api = new mw.Api();
				api.postWithToken( 'csrf', {
					action: 'bs-subscription-manager',
					task: 'triggerDowngrade'
				} ).done( function ( data ) {
					if ( data.success ) {
						console.log( "downgrade process started" );
					} else {
						console.log( "error while starting downgrade process" );
					}
				} );
			} else {
				console.log( 'User clicked "Cancel" or closed the dialog.' );
			}
		} );
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
			$( '#token_checkup_result' ).show();
			if ( data.success === false ) {
				$( '.tocken_check_result' ).html( mw.message( 'bs-upgradehelper-token-check-result-error' ).text() );
				$( '.token_data' ).empty();
				$( ".button-do-upgrade" ).hide();
				$( '.upgrade_status_element' ).hide();
				return;
			} else {
				$( '.tocken_check_result' ).html( mw.message( 'bs-upgradehelper-token-check-result-ok' ).text() );
				$( ".button-do-upgrade" ).show();
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
			$( '.token_data' ).html( html );
		} ).fail( function ( data, response ) {
			console.log( data );
			console.log( response );
		} );

	} );
	var sLang = mw.config.get( "wgUserLanguage" );
	$( "#compare-bluespice" ).load( "../extensions/BlueSpiceUpgradeHelper/webservices/versioncompare.php?lang=" + sLang + " #main" );

	$( ".close-button" ).click( function () {
		$( '.token-process' ).hide();
	} );
}( mediaWiki, jQuery ) );
