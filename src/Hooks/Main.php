<?php

namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Hooks;

/**
 * Hooks for BoilerPlate extension
 *
 * @file
 * @ingroup Extensions
 */
class Main {

	public static $configNameShowLink = 'MW::BlueSpiceUpgradeHelper::ShowMenuLinks';
	public static $configNameHint = 'MW::BlueSpiceUpgradeHelper::ShowHint';
	public static $permissionViewSpecial = 'bluespice-upgradehelper-viewspecialpage';

	public static function onRegistration() {
		\BsConfig::registerVar( self::$configNameShowLink, true, \BsConfig::LEVEL_PUBLIC | \BsConfig::TYPE_BOOL, 'bs-bluespiceupgradehelper-show-menu-links', 'toggle' );
		\BsConfig::registerVar(
		  self::$configNameHint, true, \BsConfig::LEVEL_PUBLIC | \BsConfig::TYPE_BOOL, 'bs-bluespiceupgradehelper-hint-active', 'toggle'
		);
	}

	public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
		$out->addModules( "ext.blueSpiceUpgradeHelper.base" );

		$bActive = \BsConfig::get(
			self::$configNameHint
		);

		if ( $skin->getUser()->isAllowed( 'wikiadmin' ) && $bActive ) {
			$out->addModules( "ext.blueSpiceUpgradeHelper.hint" );
		}

		return true;
	}

	/**
	 * @param string $sData
	 * @param Skin $oSkin
	 * @return boolean
	 */
	public static function onSkinAfterContent( &$sData, \Skin $oSkin ) {
		$bActive = \BsConfig::get(
			self::$configNameHint
		);
		$cVar = filter_input(INPUT_COOKIE, 'bs-bluespiceupgradehelper-hide', FILTER_VALIDATE_BOOLEAN);
		$status = (!empty( $cVar )) ? boolval( $cVar ) : false;
		if ( $oSkin->getUser()->isAllowed( 'wikiadmin' ) && $bActive && !$status && $oSkin->getTitle()->isMainPage()) {
			$oView = new \MediaWiki\Extension\BlueSpiceUpgradeHelper\Views\BlueSpiceUpgradeHelperPanel();
			$sData .= $oView->execute();
		}

		return true;
	}

	/**
	 * Returns a list item with a link to the "About BlueSpice" special page
	 * @param array $aOutSortable Indexed list of menu items. Add item in HTML form.
	 * @param \User The user in which context the menu is rendered
	 * @return string Link to the "About BlueSpice" special page
	 */
	public static function onBSWikiAdminMenuItems( &$aOutSortable, $oUser ) {
		if ( !\BsConfig::get( self::$configNameShowLink ) ) {
			return true;
		}
		if ( !$oUser->isAllowed( self::$permissionViewSpecial ) ) {
			return true;
		}
		$oSpecialPage = \SpecialPage::getTitleFor( 'BlueSpiceUpgradeHelper' );
		$sLink = \Html::element(
			'a', array(
			  'id' => 'bs-admin-aboutbluespice',
			  'href' => $oSpecialPage->getLocalURL(),
			  'title' => wfMessage( 'bs-upgrade-helper-title' )->plain(),
			  'class' => 'bs-admin-link'
			), wfMessage( 'bs-upgrade-helper-title' )->plain()
		);
		$aOutSortable[ wfMessage( 'bs-upgrade-helper-title' )->escaped() ] = '<li>' . $sLink . '</li>';
		return true;
	}

}
