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

		$oResponse->payload = $this->parseToken($oTaskData->token);
		$oResponse->payload_count++;
		$oResponse->success = true;
		return $oResponse;
	}

	protected function parseToken($sToken) {
		$token = (new Parser() )->parse( ( string ) $sToken ); // Parses from a string

		return $token->getClaims();
	}

}
