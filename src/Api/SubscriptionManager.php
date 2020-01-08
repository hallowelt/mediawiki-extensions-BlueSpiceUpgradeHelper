<?php

namespace BlueSpice\UpgradeHelper\Api;

use Lcobucci\JWT\Parser;
use BlueSpice\UpgradeHelper\Hooks;
use BlueSpice\UpgradeHelper\Specials\UpgradeHelper;

class SubscriptionManager extends \BlueSpice\Api\Task {

	protected $url = 'https://selfservice.bluespice.com/frontend/info/';
	protected $aTasks = [
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
	];

	protected function getRequiredTaskPermissions() {
		return [
			'parsetoken' => [ 'bluespice-upgradehelper-viewspecialpage' ],
			'triggerUpgrade' => [ 'bluespice-upgradehelper-viewspecialpage' ],
			'triggerDowngrade' => [ 'bluespice-upgradehelper-viewspecialpage' ],
			'disableHint' => [ 'wikiadmin' ]
		];
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

		// $oTaskData->token
		$upgradeHelper = new \BlueSpice\UpgradeHelper\Specials\UpgradeHelper();
		file_put_contents( $upgradeHelper->getTokenFilePath(), $oTaskData->token );

		$oTokenCheck = $this->task_parsetoken( $oTaskData );
		$upgradeHelper = new UpgradeHelper();
		$manifestData = $upgradeHelper->getManifestData();
		$rVersionName = $oResponse->payload[ 'response_data' ][ 'package_manifest' ][ 'versionName' ];
		$rPackage = $oResponse->payload[ 'response_data' ][ 'package_manifest' ][ 'package' ];
		$rSystem = $oResponse->payload[ 'response_data' ][ 'package_manifest' ][ 'system' ];
		if ( $manifestData[ 'versionName' ] !== $rVersionName
			|| $manifestData[ 'package' ] !== $rPackage
			|| $manifestData[ 'system' ] !== $rSystem ) {
			// only trigger if version is different
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
		$upgradeHelper = new \BlueSpice\UpgradeHelper\Specials\UpgradeHelper();
		$manifestData = $upgradeHelper->getManifestData();
		return $this->url . $manifestData[ "system" ] . "/"
			. trim( $manifestData[ "branch" ], "_" . $manifestData[ "system" ] )
			. "/" . "bluespice.zip";
	}

	protected function parseToken( $sToken ) {
		$token = ( new Parser() )->parse( (string)$sToken ); // Parses from a string

		return $token->getClaims();
	}

}
