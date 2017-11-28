<?php

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Api;

use Lcobucci\JWT\Parser;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Hooks;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Specials\UpgradeHelper;

class SubscriptionManager extends \BSApiTasksBase {

	protected $url = 'https://selfservice.bluespice.com/frontend/info/';
	protected $aTasks = array(
		'parsetoken' => [
			'examples' => [
				[
					'token' => 'token hash to parse'
				]
			],
			'params' => [
				'token' => [
					'desc' => 'token hash to parse',
					'type' => 'string',
					'required' => true
				]
			]
		],
		'triggerUpgrade' => [
			'examples' => [
				[
					'token' => 'token hash to use for upgrade'
				]
			],
			'params' => [
				'token' => [
					'desc' => 'token hash to use for upgrade',
					'type' => 'string',
					'required' => true
				]
			]
		],
		'triggerDowngrade' => [],
		'disableHint' => []
	);

	protected function getRequiredTaskPermissions() {
		return array(
			'parsetoken' => array( Hooks\Main::$permissionViewSpecial ),
			'triggerUpgrade' => array( Hooks\Main::$permissionViewSpecial ),
			'triggerDowngrade' => array( Hooks\Main::$permissionViewSpecial ),
			'disableHint' => array( 'wikiadmin' )
		);
	}

	protected function task_disableHint() {
		$oReturn = $this->makeStandardReturn();
		BsConfig::set( Hooks\Main::$configNameHint, false );
		BsConfig::saveSettings();
		$oReturn->success = true;
		return $oReturn;
	}

	protected function task_triggerDowngrade() {
		$oReturn = $this->makeStandardReturn();

		$downgradeTaskFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "downgrade.task";
		file_put_contents( $downgradeTaskFilePath, "" );

		$oReturn->success = true;
		return $oReturn;
	}

	protected function task_triggerUpgrade( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		if ( !isset( $oTaskData->token ) ) {
			$oResponse->success = false;
			return $oResponse;
		}

		//$oTaskData->token
		$upgradeHelper = new \MediaWiki\Extension\BlueSpiceUpgradeHelper\Specials\UpgradeHelper();
		file_put_contents( $upgradeHelper->getTokenFilePath(), $oTaskData->token );

		$oTokenCheck = $this->task_parsetoken( $oTaskData );
		$manifestData = $upgradeHelper->getManifestData();
		$rVersionName = $oTokenCheck->payload[ 'response_data' ]->package_manifest->versionName;
		$rPackage = $oTokenCheck->payload[ 'response_data' ]->package_manifest->package;
		$rSystem = $oTokenCheck->payload[ 'response_data' ]->package_manifest->system;
		if ( $manifestData[ 'versionName' ] !== $rVersionName || $manifestData[ 'package' ] !== $rPackage || $manifestData[ 'system' ] !== $rSystem ) {
			//only trigger if version is different
			$upgradeTaskFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "upgrade.task";
			file_put_contents( $upgradeTaskFilePath, "" );
		} else {
			$upgradeTaskFilePath = getenv( 'BLUESPICE_CONFIG_PATH' ) . "/" . "upgrade_token_only.task";
			file_put_contents( $upgradeTaskFilePath, "" );
			unlink( $upgradeTaskFilePath );
		}

		$oReturn->success = true;
		return $oReturn;
	}

	public function task_parsetoken( $oTaskData ) {
		$oResponse = $this->makeStandardReturn();

		if ( !isset( $oTaskData->token ) ) {
			$oResponse->success = false;
			return $oResponse;
		}

		$oResponse->payload[ 'token_data' ] = $this->parseToken( $oTaskData->token );

		$req = \MWHttpRequest::factory( $this->getUrl() );
		$req->setHeader( 'Authorization', "Bearer " . $oTaskData->token );
		$status = $req->execute();

		if ( $status->isOK() ) {
			$oResponse->payload[ 'response_data' ] = \FormatJson::decode( $req->getContent() );
			$oResponse->payload_count++;
			$oResponse->success = true;
		} else {
			$oResponse->payload[ 'response_data' ] = \FormatJson::decode( $req->getContent() );
			$oResponse->success = false;
		}


		return $oResponse;
	}

	protected function getUrl() {
		$upgradeHelper = new \MediaWiki\Extension\BlueSpiceUpgradeHelper\Specials\UpgradeHelper();
		$manifestData = $upgradeHelper->getManifestData();
		return $this->url . $manifestData[ "system" ] . "/" . trim( $manifestData[ "branch" ], "_" . $manifestData[ "system" ] ) . "/" . "bluespice.zip";
	}

	protected function parseToken( $sToken ) {
		$token = (new Parser() )->parse( ( string ) $sToken ); // Parses from a string

		return $token->getClaims();
	}

}
