<?php

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Api;

use Lcobucci\JWT\Parser;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Hooks;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Specials;

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
		'disableHint' => []
	);

	protected function getRequiredTaskPermissions() {
		return array(
			'parsetoken' => array( Hooks\Main::$permissionViewSpecial ),
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
		$upgradeHelper = new UpgradeHelper();
		$manifestData = $upgradeHelper->getManifestData();
		return $this->url . $manifestData[ "system" ] . "/" . trim( $manifestData[ "branch" ], "_" . $manifestData[ "system" ] ) . "/" . "bluespice.zip";
	}

	protected function parseToken( $sToken ) {
		$token = (new Parser() )->parse( ( string ) $sToken ); // Parses from a string

		return $token->getClaims();
	}

}
