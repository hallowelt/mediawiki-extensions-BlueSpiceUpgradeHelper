<?php

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Api;

use Lcobucci\JWT\Parser;
use MediaWiki\Extension\BlueSpiceUpgradeHelper\Hooks;

class SubscriptionManager extends \BSApiTasksBase {

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

		$client = new \GuzzleHttp\Client();
		$res = $client->request( 'GET', 'https://selfservice.bluespice.com/frontend/info/docker/REL1_27/bluespice.zip', [
			'headers' => [
				'User-Agent' => 'bluespice-upgrade-helper/1.0',
				'Accept' => 'application/json',
				'Authorization' => "Bearer " . $oTaskData->token
			]
		  ] );

		if ( $res->getStatusCode() == 200 ) {
			$oResponse->payload[ 'response_data' ] = \GuzzleHttp\json_decode( $res->getBody() );
		}

		$oResponse->payload_count++;
		$oResponse->success = true;
		return $oResponse;
	}

	protected function parseToken( $sToken ) {
		$token = (new Parser() )->parse( ( string ) $sToken ); // Parses from a string

		return $token->getClaims();
	}

}
