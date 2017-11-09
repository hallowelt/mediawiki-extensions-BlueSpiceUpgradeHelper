<?php

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Api;

use Lcobucci\JWT\Parser;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Hooks;

class SubscriptionManager extends \BSApiTasksBase {

	protected $url = 'https://selfservice.bluespice.com/frontend/info/docker/REL1_27/bluespice.zip';
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
		]
	);

	protected function getRequiredTaskPermissions() {
		return array(
			'parsetoken' => array( Hooks\Main::$permissionViewSpecial )
		);
	}

	public function task_parsetoken( $oTaskData ) {
		$oResponse = $this->makeStandardReturn();

		if ( !isset( $oTaskData->token ) ) {
			$oResponse->success = false;
			return $oResponse;
		}

		$oResponse->payload[ 'token_data' ] = $this->parseToken( $oTaskData->token );

		$req = \MWHttpRequest::factory( $this->url );
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

	protected function parseToken( $sToken ) {
		$token = (new Parser() )->parse( ( string ) $sToken ); // Parses from a string

		return $token->getClaims();
	}

}
