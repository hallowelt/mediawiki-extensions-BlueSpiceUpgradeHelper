<?php

namespace BlueSpice\UpgradeHelper\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( !$this->getConfig()->get( 'UpgradeHelperShowHint' ) ) {
			return true;
		}
		if ( !$this->out->getUser()->isAllowed( 'wikiadminh' ) ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$this->out->addModuleStyles( 'ext.blueSpiceUpgradeHelper.hint' );

		return true;
	}

}
