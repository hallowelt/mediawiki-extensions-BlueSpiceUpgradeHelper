<?php

/**
 * HelloWorld SpecialPage for BoilerPlate extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Specials;

use HTMLForm;
use BsSpecialPage;
use Lcobucci\JWT\Parser;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Hooks;

class UpgradeHelper extends BsSpecialPage {

	protected $filePath = "";

	public function __construct() {
		$this->filePath = self::tokenFilePath();
		parent::__construct( 'BlueSpiceUpgradeHelper', Hooks\Main::$permissionViewSpecial );
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
		parent::execute( $sub );

		$templateParser = new \TemplateParser( __DIR__ . '/../../templates' );

		$this->setHeaders();

		$out = $this->getOutput();

		$arrCurrentData = self::readManifest();

		//$out->setPageTitle( $this->msg( 'bs-upgrade-helper-token-label' ) );


		if ( empty( $this->filePath ) ) {
			$out->addWikiMsg( "bs-upgrade-helper-env-not-ok" );
		}

		//read in token meta..

		if ( !empty( file_get_contents( $this->filePath ) ) ) {
			$token = (new Parser() )->parse( ( string ) file_get_contents( $this->filePath ) ); // Parses from a string

			$nbf = $token->getClaim( 'nbf' );
			$exp = $token->getClaim( 'exp' );
			$maxUser = $token->getClaim( 'max_user' );
			$arrProductData = explode( "/", $token->getClaim( 'product_name' ) );

			/*
			  $out->addHtml( "<h3>Token data</h3>" );
			  $out->addWikiText( "Created: " . date( 'd.m.Y', $nbf ) ); //issue timestamp
			  $out->addWikiText( "Expire at: " . date( 'd.m.Y', $exp ) ); //expire timestamp
			  $out->addWikiText( "User allowed: " . $maxUser ); //expire timestamp

			  $out->addWikiText( "System: " . $arrProductData[ 0 ] ); //expire timestamp
			  $out->addWikiText( "Version: " . $arrProductData[ 1 ] ); //expire timestamp
			  $out->addWikiText( "Package: " . trim( ucwords( implode( " ", explode( "_", $arrProductData[ 2 ] ) ) ), ".zip" ) );
			 *
			 */

			$package = trim( ucwords( implode( " ", explode( "_", $arrProductData[ 2 ] ) ) ), ".zip" );
			$system = $arrProductData[ 0 ];
			$validFrom = date( 'd.m.Y', $nbf );
			$validUntil = date( 'd.m.Y', $exp );
			$usersAllowed = ($maxUser == 0) ? "unlimited" : $maxUser;

			global $bsgBlueSpiceExtInfo, $IP;

			$currentPackage = "";
			if ( !empty( getenv( "BLUESPICE_FREE_FILE" ) ) && file_exists( "$IP/" . getenv( "BLUESPICE_FREE_FILE" ) ) ) {
				$currentPackageTemp = basename( file_get_contents( "$IP/" . getenv( "BLUESPICE_FREE_FILE" ) ), ".zip" );
				$currentPackage = ucwords( implode( " ", explode( "_", $currentPackageTemp ) ) );
			} else {
				$currentPackage = "Free";
			}
			$out->addHTML( $templateParser->processTemplate(
				'VersionOverview', [
				  "version_head" => "Current version",
				  "version_package" => $currentPackage,
				  "version_version" => (empty( getenv( "BLUESPICE_BASE_VERSION" ) )) ? $bsgBlueSpiceExtInfo[ "version" ] : getenv( "BLUESPICE_BASE_VERSION" ),
				  "version_system" => ucwords( (empty( getenv( "BLUESPICE_BASE_ENV" ) )) ? PHP_OS : getenv( "BLUESPICE_BASE_ENV" ) ),
				  "version_pro" => (strpos( $currentPackage, "Pro" ) !== FALSE),
				  "version_valid_from" => $validFrom,
				  "version_valid_until" => $validUntil,
				  "version_user_allowed" => $usersAllowed
				]
			) );

			if ( $currentPackage !== $package && strtotime( $validFrom ) <= time() && time() <= strtotime( $validUntil ) + 3600 * 24 ) {
				$out->addHTML( $templateParser->processTemplate(
					'VersionOverview', [
					  "version_head" => "Available Version for Upgrade",
					  "version_package" => $package,
					  "version_version" => (empty( getenv( "BLUESPICE_BASE_VERSION" ) )) ? $bsgBlueSpiceExtInfo[ "version" ] : getenv( "BLUESPICE_BASE_VERSION" ),
					  "version_system" => ucwords( (empty( getenv( "BLUESPICE_BASE_ENV" ) )) ? PHP_OS : getenv( "BLUESPICE_BASE_ENV" ) ),
					  "version_pro" => true,
					  "version_valid_from" => $validFrom,
					  "version_valid_until" => $validUntil,
					  "version_user_allowed" => $usersAllowed
					]
				) );
			}
		}

		$out->addHelpLink( 'How to buy BlueSpice Pro' );

		if ( empty( file_get_contents( $this->filePath ) ) ) {
			$out->addWikiMsg( 'bs-upgrade-helper-intro' );
		}

		$formDescriptor = [
			'bsUpgradeTokenField' => [
				'class' => 'HTMLTextField',
				'label' => $this->msg( 'bs-upgrade-helper-token-label' ),
				'default' => file_exists( $this->filePath ) ? file_get_contents( $this->filePath ) : "",
				'validation-callback' => [ 'MediaWiki\\Extension\\BlueSpiceUpgradeHelper\\Specials\\UpgradeHelper', 'validateTokenField' ],
			]
		];

		$formDescriptor[ 'save_token' ] = array(
			'type' => 'submit',
			'buttonlabel' => $this->msg( 'bs-upgrade-helper-save' ),
		);


		if ( self::validateTokenField( file_get_contents( $this->filePath ) ) ) {
			$formDescriptor[ 'upgrade' ] = array(
				'type' => 'submit',
				'buttonlabel' => $this->msg( 'bs-upgrade-helper-upgrade' ),
				''
			);
		}

		/*
		  $formDescriptor[ 'downgrade' ] = array(
		  'type' => 'submit',
		  'buttonlabel' => $this->msg( 'bs-upgrade-helper-downgrade' ),
		  );
		 *
		 */



		// $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'testform' );
		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext(), 'tokenform' );

		$htmlForm->suppressDefaultSubmit();

		$htmlForm->setSubmitText( wfMessage( 'bs-upgrade-helper-save' )->text() );
		$htmlForm->setAction( $this->getPageTitle( $sub )->getLocalUrl() );
		$htmlForm->setSubmitCallback( [ 'MediaWiki\\Extension\\BlueSpiceUpgradeHelper\\Specials\\UpgradeHelper', 'processInput' ] );

		//$htmlForm->show();
	}

	static function readManifest() {
		global $IP;
		$filePath = $IP . "/BlueSpiceManifest.xml";
		$arrRet = [];
		if ( file_exists( $filePath ) ) {
			$domDoc = new \DOMDocument;
			$domDoc->load($filePath);
			$domRoot = $domDoc->documentElement;
			/*
			  <manifest
			  versionCode="2.27.3"
			  versionName="v2.27.3"
			  repository="https://github.com/hallowelt/mediawiki"
			  branch="REL1_27_docker"
			  package="BlueSpice Free Docker"
			  system="docker"
			  installLocation="/var/www/bluespice"
			  configLocation="/etc/bluespice"
			  dataLocation="/var/bluespice"
			  solrLocation="/opt/bluespice"
			  />
			 */
			$arrRet["versionCode"] = $domRoot->getAttribute("versionCode");
			$arrRet["versionName"] = $domRoot->getAttribute("versionName");
			$arrRet["repository"] = $domRoot->getAttribute("repository");
			$arrRet["branch"] = $domRoot->getAttribute("branch");
			$arrRet["package"] = $domRoot->getAttribute("package");
			$arrRet["system"] = $domRoot->getAttribute("system");
			$arrRet["installLocation"] = $domRoot->getAttribute("installLocation");
			$arrRet["configLocation"] = $domRoot->getAttribute("configLocation");
			$arrRet["dataLocation"] = $domRoot->getAttribute("dataLocation");
			$arrRet["solrLocation"] = $domRoot->getAttribute("solrLocation");
		}
	}

	static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	static function base64url_decode( $data ) {
		return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) );
	}

	static function validateTokenField( $bsUpgradeTokenField, $allData = null ) {
		if ( !is_string( $bsUpgradeTokenField ) ) {
			return "token is empty";
		}

		$data = explode( '.', $bsUpgradeTokenField );

		if ( count( $data ) != 3 ) {
			return "token must have three dots";
		}

		for ( $i = 0; $i < 2; $i++ ) {
			if ( self::base64url_encode( self::base64url_decode( $data[ $i ] ) ) !== $data[ $i ] ) {
				return "Invalid Data in token ($i)";
			}
		}
		return true;
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
		} else if ( !empty( $formData[ "save_token" ] ) && $formData[ "save_token" ] && !empty( $formData[ 'bsUpgradeTokenField' ] ) ) {

			file_put_contents( self::tokenFilePath(), $formData[ 'bsUpgradeTokenField' ] );
		}

		global $wgOut, $wgTitle;
		$wgOut->redirect( $wgTitle->getFullUrl() );
		return false;
	}

	protected function getGroupName() {
		return 'bluespice';
	}

}
