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
	protected $manifestAttributes = [
		"versionCode" => true,
		"versionName" => true,
		"repository" => true,
		"branch" => true,
		"package" => true,
		"system" => true,
		"installLocation" => false,
		"configLocation" => false,
		"dataLocation" => false,
		"pro" => [
			"convert" => "exists if yes"
		]
	];

	public function __construct() {
		$this->filePath = self::tokenFilePath();
		parent::__construct( 'SubscriptionManager', Hooks\Main::$permissionViewSpecial );
	}

	public function getTokenFilePath() {
		return $this->filePath;
	}

	public function isPro() {
		$manifestData = $this->getManifestData();
		if ( strpos( strtolower( $manifestData[ "package" ] ), "pro" ) === false ) {
			return false;
		} else {
			return true;
		}
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

		$templateParser = new \MediaWiki\Extension\BlueSpiceUpgradeHelper\TemplateParser( __DIR__ . '/../../templates' );

		$this->setHeaders();

		$out = $this->getOutput();

		$currentVersionData = array_merge( $this->readManifestFile(), $this->readTokenData() );

		if ( !isset( $currentVersionData[ 'support_hours' ] ) ) {
			$currentVersionData[ 'support_hours' ] = "";
		}

		//package_description
		$currentVersionData[ 'package_limited' ] = (strpos( strtolower( $currentVersionData[ "package" ] ), "free" ) !== false) ? wfMessage( "bs-ugradehelper-unlimited" ) : wfMessage( "bs-ugradehelper-limited" );
		$currentVersionData[ 'supportHours' ] = intval( $currentVersionData[ 'support_hours' ] );
		$currentVersionData[ 'adminUsername' ] = $this->getUser()->getName();

		$out->addHTML( $templateParser->processTemplate(
			'VersionOverview', $currentVersionData
		) );

		$out->addHTML( \Html::element( "div", [ "id" => "compare-head" ], wfMessage( "bs-upgradehelper-compare-head-label" ) ) );
		$out->addHTML( \Html::element( "div", [ "id" => "compare-bluespice" ] ) );

		$out->addHTML( $templateParser->processTemplate(
			'TokenButton', $currentVersionData
		) );
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

	protected function getDefaultManifestPath() {
		global $IP;
		return $IP . "/BlueSpiceManifest.xml";
	}

	protected function readManifestFile( $path = null ) {
		if ( empty( $path ) ) {
			$path = $this->getDefaultManifestPath();
		}
		if ( file_exists( $path ) ) {
			$domDoc = new \DOMDocument( '1.0', 'UTF-8' );
			if ( !$domDoc->load( $path ) ) {
				return false;
			}
			$domRoot = $domDoc->documentElement;

			$aReturn = $this->parseAttributes( $domRoot );

			return $aReturn;
		}
		return false;
	}

	public function getManifestData() {
		return $this->readManifestFile();
	}

	protected function parseAttributes( $domRoot ) {
		$aReturn = [];
		foreach ( $this->manifestAttributes as $attribute => $required ) {
			if ( !$domRoot->hasAttribute( $attribute ) && $required === true ) {
				return false;
			}
			if ( is_array( $required ) && isset( $required[ "convert" ] ) && $required[ "convert" ] == "exists if yes" ) {
				($domRoot->getAttribute( $attribute ) == "yes") ? $aReturn[ $attribute ] = true : "";
			} else {
				$aReturn[ $attribute ] = $domRoot->getAttribute( $attribute );
			}
		}
		return $aReturn;
	}

	public static function parseToken( $domRoot ) {
		$aReturn = [];
		foreach ( $this->manifestAttributes as $attribute => $required ) {
			if ( !$domRoot->hasAttribute( $attribute ) && $required === true ) {
				return false;
			}
			if ( is_array( $required ) && isset( $required[ "convert" ] ) && $required[ "convert" ] == "exists if yes" ) {
				($domRoot->getAttribute( $attribute ) == "yes") ? $aReturn[ $attribute ] = true : "";
			} else {
				$aReturn[ $attribute ] = $domRoot->getAttribute( $attribute );
			}
		}
		return $aReturn;
	}

	public function readTokenData() {
		$arrRet = [];
		if ( file_exists( $this->filePath ) ) {
			try {
				$token = (new Parser() )->parse( ( string ) file_get_contents( $this->filePath ) ); // Parses from a string

				$arrRet[ "nbf" ] = date( 'd.m.Y', $token->getClaim( 'nbf' ) );
				$arrRet[ "exp" ] = date( 'd.m.Y', $token->getClaim( 'exp' ) );
				$arrRet[ "max_user" ] = $token->getClaim( 'max_user' );
				$arrRet[ "support_hours" ] = $token->getClaim( 'support_hours' );
			} catch ( \Exception $e ) {
				$arrRet[ "token_error" ] = $e->getMessage();
			}
		}

		return $arrRet;
	}

}
