<?php

namespace BlueSpice\UpgradeHelper;

use Message;
use SpecialPage;
use BlueSpice\IAdminTool;

class AdminTool implements IAdminTool {

	/**
	 *
	 * @return string String of the URL
	 */
	public function getURL() {
		$tool = SpecialPage::getTitleFor( 'SubscriptionManager' );
		return $tool->getLocalURL();
	}

	/**
	 *
	 * @return Message
	 */
	public function getDescription() {
		return Message::newFromKey( 'bs-upgrade-helper-title' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getName() {
		return Message::newFromKey( 'bs-upgrade-helper-title' );
	}

	/**
	 *
	 * @return array
	 */
	public function getClasses() {
		return [
			'bs-icon-shopping-cart'
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getDataAttributes() {
		return [];
	}

	/**
	 *
	 * @return array
	 */
	public function getPermissions() {
		return [
			'bluespice-upgradehelper-viewspecialpage'
		];
	}

}
