<?php

/**
 * HelloWorld SpecialPage for BoilerPlate extension
 *
 * @file
 * @ingroup Extensions
 */

namespace BlueSpice\UpgradeHelper\Special;

use BsSpecialPage;
use Lcobucci\JWT\Parser;

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
		parent::__construct( 'SubscriptionManager', 'bluespice-upgradehelper-viewspecialpage' );
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

	public static function tokenFilePath() {
		// $BLUESPICE_CONFIG_PATH/$BLUESPICE_PRO_KEY_FILE
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

		$templateParser = new \BlueSpice\UpgradeHelper\TemplateParser(
			__DIR__ . '/../../resources/templates'
		);

		$this->setHeaders();

		$out = $this->getOutput();

		$out->addModules( "ext.blueSpiceUpgradeHelper.base" );

		$currentVersionData = array_merge( $this->readManifestFile(), $this->readTokenData() );

		if ( !isset( $currentVersionData[ 'support_hours' ] ) ) {
			$currentVersionData[ 'support_hours' ] = "";
		}

		// package_description
		$currentVersionData[ 'package_limited' ]
			= strpos( strtolower( $currentVersionData[ "package" ] ), "free" ) !== false
				? wfMessage( "bs-ugradehelper-unlimited" )
				: wfMessage( "bs-ugradehelper-limited" );
		$currentVersionData[ 'supportHours' ] = intval( $currentVersionData[ 'support_hours' ] );
		$currentVersionData[ 'adminUsername' ] = $this->getUser()->getName();
		$currentVersionData[ 'blueSpiceVersion' ] = $this->getConfig()->get( 'BlueSpiceExtInfo' )[ 'version' ];
		if ( strpos( strtolower( $currentVersionData[ "package" ] ), "pro" ) !== false ) {
			// licensedUsers, max_user
			$currentVersionData[ 'licensedUsers' ] = $currentVersionData[ 'max_user' ];
			// bs-upgradehelper-package-button-upgrade
			$currentVersionData[ 'bs-upgradehelper-package-button-upgrade' ]
				= wfMessage( "bs-upgradehelper-package-button-upgrade-users" );
		} else {
			$currentVersionData[ 'licensedUsers' ] = "unlimited";
			$currentVersionData[ 'bs-upgradehelper-package-button-upgrade' ]
				= wfMessage( "bs-upgradehelper-package-button-upgrade" );
		}

		$out->addHTML( $templateParser->processTemplate(
			'VersionOverview', $currentVersionData
		) );

		$out->addHTML( \Html::element( "div", [ "id" => "compare-bluespice" ] ) );

		$out->addHTML( $templateParser->processTemplate(
			'TokenButton', $currentVersionData
		) );

		$out->enableOOUI();
	}

	public static function readManifest() {
		$filePath = $GLOBALS['IP'] . "/BlueSpiceManifest.xml";
		$arrRet = [];
		if ( file_exists( $filePath ) ) {
			$domDoc = new \DOMDocument;
			$domDoc->load( $filePath );
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
			$arrRet[ "versionCode" ] = $domRoot->getAttribute( "versionCode" );
			$arrRet[ "versionName" ] = $domRoot->getAttribute( "versionName" );
			$arrRet[ "repository" ] = $domRoot->getAttribute( "repository" );
			$arrRet[ "branch" ] = $domRoot->getAttribute( "branch" );
			$arrRet[ "package" ] = $domRoot->getAttribute( "package" );
			$arrRet[ "system" ] = $domRoot->getAttribute( "system" );
			$arrRet[ "installLocation" ] = $domRoot->getAttribute( "installLocation" );
			$arrRet[ "configLocation" ] = $domRoot->getAttribute( "configLocation" );
			$arrRet[ "dataLocation" ] = $domRoot->getAttribute( "dataLocation" );
			$arrRet[ "solrLocation" ] = $domRoot->getAttribute( "solrLocation" );
		}
		return $arrRet;
	}

	// phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	// phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public static function base64url_decode( $data ) {
		return base64_decode( str_pad(
			strtr( $data, '-_', '+/' ),
			strlen( $data ) % 4,
			'=',
			STR_PAD_RIGHT
		) );
	}

	public static function validateTokenField( $bsUpgradeTokenField, $allData = null ) {
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

	public static function processInput( $formData ) {
		if ( !empty( $formData[ "upgrade" ] ) && $formData[ "upgrade" ] ) {
			$upgradeFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "upgrade.task";
			if ( file_exists( $upgradeFilePath ) ) {
				unlink( $upgradeFilePath );
			}
			file_put_contents( $upgradeFilePath, "" );
		} elseif ( !empty( $formData[ "downgrade" ] ) && $formData[ "downgrade" ] ) {
			$downgradeFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "downgrade.task";
			if ( file_exists( $downgradeFilePath ) ) {
				unlink( $downgradeFilePath );
			}
			file_put_contents( $downgradeFilePath, "" );
		} elseif ( !empty( $formData[ "save_token" ] ) && $formData[ "save_token" ]
			&& !empty( $formData[ 'bsUpgradeTokenField' ] ) ) {

			file_put_contents( self::tokenFilePath(), $formData[ 'bsUpgradeTokenField' ] );
		}

		$context = \RequestContext::getMain();
		$context->getOutput()->redirect( $context->getTitle()->getFullUrl() );
		return false;
	}

	protected function getGroupName() {
		return 'bluespice';
	}

	protected function getDefaultManifestPath() {
		return $GLOBALS['IP'] . "/BlueSpiceManifest.xml";
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
			if ( is_array( $required ) && isset( $required[ "convert" ] )
				&& $required[ "convert" ] == "exists if yes" ) {
				$domRoot->getAttribute( $attribute ) == "yes" ? $aReturn[ $attribute ] = true : "";
			} else {
				$aReturn[ $attribute ] = $domRoot->getAttribute( $attribute );
			}
		}
		return $aReturn;
	}

	public function parseToken( $domRoot ) {
		$aReturn = [];
		foreach ( $this->manifestAttributes as $attribute => $required ) {
			if ( !$domRoot->hasAttribute( $attribute ) && $required === true ) {
				return false;
			}
			if ( is_array( $required ) && isset( $required[ "convert" ] )
				&& $required[ "convert" ] == "exists if yes" ) {
				$domRoot->getAttribute( $attribute ) == "yes" ? $aReturn[ $attribute ] = true : "";
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
				// Parses from a string
				$token = ( new Parser() )->parse( (string)file_get_contents( $this->filePath ) );

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
