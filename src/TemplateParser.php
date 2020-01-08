<?php

// derived from https://github.com/vedmaka/mediawiki-extension-Mustache_i18n
// class can be removed after integration is done in bsfoundation

namespace BlueSpice\UpgradeHelper;

class TemplateParser extends \TemplateParser {

	protected function compile( $code ) {
		if ( !class_exists( 'LightnCandy' ) ) {
			throw new \RuntimeException( 'LightnCandy class not defined' );
		}

		// die(print_r($code, true));

		return \LightnCandy::compile(
			$code, [
			  // Do not add more flags here without discussion.
			  // If you do add more flags, be sure to update unit tests as well.
			  'flags' => \LightnCandy::FLAG_ERROR_EXCEPTION,
			  'helpers' => [
				  '_' => function ( $msg ) {
					  if ( count( $msg ) > 1 ) {
						  $msgKey = array_shift( $msg );
						  return wfMessage( $msgKey, $msg )->plain();
					  } else {
						  return wfMessage( $msg )->plain();
					  }
				  },
				  '__' => function ( $msg ) {
					  return wfMessage( $msg )->parse();
				  },
			  ]
			]
		);
	}

}
