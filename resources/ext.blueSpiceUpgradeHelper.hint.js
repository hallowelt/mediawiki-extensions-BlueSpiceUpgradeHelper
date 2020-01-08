( function( d, mw, $ ){
	mw.loader.using( 'ext.bluespice', function() {
		if ( $.cookie( 'bs-bluespiceupgradehelper-firstload' ) === null){
			$('#bs-bluespiceupgradehelper').delay( 1500 ).fadeIn( 'slow' );

			$.cookie( 'bs-bluespiceupgradehelper-firstload', 'false', {
					path: '/',
					expires: 7 // remind once a week
				});
		} else {
			$('#bs-bluespiceupgradehelper').show();
		}

		if ( $.cookie( 'bs-bluespiceupgradehelper-hide' ) === 'true' ){
			$('#bs-bluespiceupgradehelper').hide();
		} else{
			$('#bs-bluespiceupgradehelper-closebutton').click(function(){
				if( confirm( $(this).attr('data-confirm-msg') ) ) {
					$.ajax({
						dataType: "json",
						type: 'post',
						url: mw.util.wikiScript( 'api' ),
						data: {
							action: 'bs-subscription-manager',
							task: 'disableHint',
							format: 'json',
							token: mw.user.tokens.get('editToken', '')
						}
					});
				}
				$( '#bs-bluespiceupgradehelper' ).fadeOut( 'fast' );
				$.cookie( 'bs-bluespiceupgradehelper-hide', 'true', {
					path: '/',
					expires: 7 // remind once a week
				});
			});
		}
	} );
}( document, mediaWiki, jQuery ) );