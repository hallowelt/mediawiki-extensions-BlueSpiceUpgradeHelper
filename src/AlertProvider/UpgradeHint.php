<?php

namespace BlueSpice\UpgradeHelper\AlertProvider;

use BlueSpice\IAlertProvider;
use BlueSpice\AlertProviderBase;
use BlueSpice\UpgradeHelper\Special\UpgradeHelper;

class UpgradeHint extends AlertProviderBase {

	/**
	 * @return string
	 */
	public function getHTML() {
		if ( !$this->skin->getTitle()->isMainPage() ) {
			return '';
		}
		if ( !$this->config->get( 'UpgradeHelperShowHint' ) ) {
			return '';
		}
		if ( !$this->skin->getUser()->isAllowed( 'wikiadminh' ) ) {
			return '';
		}
		$var = filter_input( INPUT_COOKIE, 'bs-bluespiceupgradehelper-hide', FILTER_VALIDATE_BOOLEAN );
		$status = !empty( $var ) ? boolval( $var ) : false;
		if ( $status ) {
			return '';
		}
		$upgradeHelper = new UpgradeHelper();
		if ( $upgradeHelper->isPro() ) {
			return '';
		}
		$oView = new \BlueSpice\UpgradeHelper\Views\BlueSpiceUpgradeHelperPanel();
		return $oView->execute();
	}

	/**
	 * @return string
	 */
	public function getType() {
		return IAlertProvider::TYPE_SUCCESS;
	}

}
