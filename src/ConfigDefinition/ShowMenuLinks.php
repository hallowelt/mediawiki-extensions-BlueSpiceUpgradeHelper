<?php

namespace BlueSpice\UpgradeHelper\ConfigDefinition;

class ShowMenuLinks extends \BlueSpice\ConfigDefinition\IntSetting {

	/**
	 *
	 * @return string[]
	 */
	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_PERSONALISATION . '/BlueSpiceUpgradeHelper',
			static::MAIN_PATH_EXTENSION . '/BlueSpiceUpgradeHelper/' . static::FEATURE_PERSONALISATION,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_CUSTOMIZING . '/BlueSpiceUpgradeHelper',
		];
	}

	/**
	 *
	 * @return string
	 */
	public function getLabelMessageKey() {
		return 'bs-bluespiceupgradehelper-show-menu-links';
	}

}
