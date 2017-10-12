<?php

/**
 * HelloWorld SpecialPage for BoilerPlate extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper;

use HTMLForm;
use BsSpecialPage;

class SpecialBlueSpiceUpgradeHelper extends BsSpecialPage {

	protected $filePath = "";

	public function __construct() {
		$this->filePath = self::tokenFilePath();
		parent::__construct( 'BlueSpiceUpgradeHelper', 'wikiadmin' );
	}

	static function tokenFilePath() {
		//$BLUESPICE_CONFIG_PATH/$BLUESPICE_PRO_KEY_FILE
		if ( empty( getenv( 'BLUESPICE_CONFIG_PATH' ) ) ||
		  empty( getenv( 'BLUESPICE_PRO_KEY_FILE' ) ) ) {

			putenv( "BLUESPICE_CONFIG_PATH=/etc/bluespice" );
			putenv( "BLUESPICE_PRO_KEY_FILE=bluespice_pro_key.txt" );
		}
		return getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . getenv( 'BLUESPICE_PRO_KEY_FILE' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:HelloWorld/subpage]].
	 */
	public function execute( $sub ) {

		$this->setHeaders();

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'bs-upgrade-helper-token-label' ) );


		if ( empty( $this->filePath ) ) {
			$out->addWikiMsg( "bs-upgrade-helper-env-not-ok" );
		}

		$out->addHelpLink( 'How to buy BlueSpice Pro' );

		if ( empty( file_get_contents( $this->filePath ) ) ) {
			$out->addWikiMsg( 'bs-upgrade-helper-intro' );
		}

		$formDescriptor = [
			'bs_upgrade_token' => [
				'class' => 'HTMLTextField',
				'label' => $this->msg( 'bs-upgrade-helper-token-label' ),
				'default' => file_exists( $this->filePath ) ? file_get_contents( $this->filePath ) : ""
			]
		];

		if ( !empty( file_get_contents( $this->filePath ) ) ) {
			$formDescriptor[ 'upgrade' ] = array(
				'type' => 'submit',
				'buttonlabel' => $this->msg( 'bs-upgrade-helper-upgrade' ),
			);
		}

		$formDescriptor[ 'downgrade' ] = array(
			'type' => 'submit',
			'buttonlabel' => $this->msg( 'bs-upgrade-helper-downgrade' ),
		);

		// $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'testform' );
		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext(), 'tokenform' );

		$htmlForm->setSubmitText( wfMessage( 'bs-upgrade-helper-save' )->text() );
		$htmlForm->setAction( $this->getPageTitle( $sub )->getLocalUrl() );
		$htmlForm->setSubmitCallback( [ 'MediaWiki\\Extension\\BlueSpiceUpgradeHelper\\SpecialBlueSpiceUpgradeHelper', 'processInput' ] );

		$htmlForm->show();
	}

	static function processInput( $formData ) {
		if ( !empty( $formData[ "upgrade" ] ) && $formData[ "upgrade" ] ) {
			$upgradeFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "upgrade.task";
			if ( file_exists( $upgradeFilePath ) ) {
				unlink( $upgradeFilePath );
			}
			file_put_contents( $upgradeFilePath, "" );
		} else if ( !empty( $formData[ "downgrade" ] ) && $formData[ "downgrade" ] ) {
			$downgradeFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "downgrade.task";
			if ( file_exists( $downgradeFilePath ) ) {
				unlink( $downgradeFilePath );
			}
			file_put_contents( $downgradeFilePath, "" );
		}
		file_put_contents( self::tokenFilePath(), $formData[ 'bs_upgrade_token' ] );

		return false;
	}

	protected function getGroupName() {
		return 'bluespice';
	}

}
