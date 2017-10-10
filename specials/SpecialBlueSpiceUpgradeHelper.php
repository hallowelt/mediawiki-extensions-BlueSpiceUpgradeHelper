<?php

/**
 * HelloWorld SpecialPage for BoilerPlate extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper;

use HTMLForm;
use SpecialPage;

class SpecialBlueSpiceUpgradeHelper extends SpecialPage {

	protected $filePath = "";

	static function tokenFilePath() {
		//$BLUESPICE_CONFIG_PATH/$BLUESPICE_PRO_KEY_FILE
		if ( empty( getenv( 'BLUESPICE_CONFIG_PATH' ) ) ||
		  empty( getenv( 'BLUESPICE_PRO_KEY_FILE' ) ) ) {

			putenv( "BLUESPICE_CONFIG_PATH=/etc/bluespice/" );
			putenv( "BLUESPICE_PRO_KEY_FILE=bluespice_pro_key.txt" );
		}
		return getenv( 'BLUESPICE_CONFIG_PATH' ) . getenv( 'BLUESPICE_PRO_KEY_FILE' );
	}

	public function __construct() {
		parent::__construct( 'BlueSpiceUpgradeHelper' );



		$this->filePath = self::tokenFilePath();
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

		if(empty(file_get_contents( $this->filePath ))){
			$out->addWikiMsg( 'bs-upgrade-helper-intro' );
		}

		$formDescriptor = [
			'bs_upgrade_token' => [
				'class' => 'HTMLTextField',
				'label' => $this->msg( 'bs-upgrade-helper-token-label' ),
				'default' => file_exists( $this->filePath ) ? file_get_contents( $this->filePath ) : ""
			]
		];

		// $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'testform' );
		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext(), 'tokenform' );

		$htmlForm->setSubmitText( wfMessage( 'bs-upgrade-helper-save' )->text() );
		$htmlForm->setAction( $this->getPageTitle( $sub )->getLocalUrl() );
		$htmlForm->setSubmitCallback( [ 'MediaWiki\\Extension\\BlueSpiceUpgradeHelper\\SpecialBlueSpiceUpgradeHelper', 'processInput' ] );

		$htmlForm->show();

		if(!empty(file_get_contents( $this->filePath ))){
			// $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'testform' );
			$htmlFormUpgrade = HTMLForm::factory( 'ooui', [], $this->getContext(), 'upgradeform' );

			$htmlFormUpgrade->setSubmitText( wfMessage( 'bs-upgrade-helper-upgrade' )->text() );
			$htmlFormUpgrade->setAction( $this->getPageTitle( $sub )->getLocalUrl() );
			$htmlFormUpgrade->setSubmitCallback( [ 'MediaWiki\\Extension\\BlueSpiceUpgradeHelper\\SpecialBlueSpiceUpgradeHelper', 'upgrade' ] );

			$htmlFormUpgrade->show();
		}


		// $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'testform' );
		$htmlFormDowngrade = HTMLForm::factory( 'ooui', [], $this->getContext(), 'downgradeform' );

		$htmlFormDowngrade->setSubmitText( wfMessage( 'bs-upgrade-helper-downgrade' )->text() );
		$htmlFormDowngrade->setAction( $this->getPageTitle( $sub )->getLocalUrl() );
		$htmlFormDowngrade->setSubmitCallback( [ 'MediaWiki\\Extension\\BlueSpiceUpgradeHelper\\SpecialBlueSpiceUpgradeHelper', 'downgrade' ] );

		$htmlFormDowngrade->show();
	}

	static function processInput( $formData ) {
		file_put_contents( self::tokenFilePath(), $formData[ 'bs_upgrade_token' ]);

		return false;
	}

	static function upgrade( $formData ) {
		$upgradeFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "upgrade.task";
		if ( file_exists( $upgradeFilePath ) ) {
			unlink( $upgradeFilePath );
		}
		file_put_contents( $upgradeFilePath, "" );
	}

	static function downgrade( $formData ) {
		$downgradeFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "downgrade.task";
		if ( file_exists( $downgradeFilePath ) ) {
			unlink( $downgradeFilePath );
		}
		file_put_contents( $downgradeFilePath, "" );
		return false;
	}

	protected function getGroupName() {
		return 'bluespice';
	}

}
