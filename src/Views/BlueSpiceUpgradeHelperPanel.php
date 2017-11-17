<?php
namespace MediaWiki\Extension\BlueSpiceUpgradeHelper\Views;

class BlueSpiceUpgradeHelperPanel extends \ViewStateBarTopElement {
	public function execute( $params = false ) {
		$sOut = '';
		$sOut .= \Xml::openElement( 'div' , array(
			'id' => 'bs-bluespiceupgradehelper'
		));
		$sOut .= \Xml::openElement(
			'div' ,
			array( 'id' => 'bs-bluespiceupgradehelper-text' )
		);
		$sOut .= wfMessage( 'bs-bluespiceupgradehelper-hint' )->parse();
		$sOut .= \Xml::closeElement( 'div' );
		$oCloseMsg = wfMessage('bs-bluespiceupgradehelper-closebutton');
		$oConfirmMsg = wfMessage('bs-bluespiceupgradehelper-confirm');
		$sOut .= \Xml::openElement( 'div', array(
			'id' => 'bs-bluespiceupgradehelper-closebutton',
			'title' => $oCloseMsg->plain(),
			'data-confirm-msg' => $oConfirmMsg->plain(),
		));
		$sOut .= \Xml::closeElement( 'div' );
		$sOut .= \Xml::closeElement( 'div' );
		return $sOut;
	}
}