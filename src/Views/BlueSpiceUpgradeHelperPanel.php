<?php

namespace BlueSpice\UpgradeHelper\Views;

class BlueSpiceUpgradeHelperPanel extends \ViewBaseElement {

	public function execute( $params = false ) {
		$sOut = '';
		$sOut .= \Xml::openElement( 'div', [
			  'id' => 'bs-bluespiceupgradehelper'
		  ] );
		$sOut .= \Xml::openElement(
			'div', [ 'id' => 'bs-bluespiceupgradehelper-text' ]
		);
		$sOut .= \Html::element( "h3", [], wfMessage( 'upgrade-invite-head' )->text() );
		$sOut .= \Html::element( "p", [], wfMessage( 'upgrade-invite' )->text() );
		$sOut .= \Html::element( "p", [], wfMessage( 'upgrade-invite-second' )->text() );

		$specialLink = \SpecialPage::getTitleFor( 'Subscription manager' )->getFullURL();
		$sOut .= \Html::element(
			"a",
			[ 'class' => 'upgrade-hint-button', 'href' => $specialLink ],
			wfMessage( 'upgrade-invite-final' )->text()
		);

		$sOut .= \Xml::closeElement( 'div' );
		$oCloseMsg = wfMessage( 'bs-bluespiceupgradehelper-closebutton' );
		$oConfirmMsg = wfMessage( 'bs-bluespiceupgradehelper-confirm' );
		$sOut .= \Xml::openElement( 'div', [
			  'id' => 'bs-bluespiceupgradehelper-closebutton',
			  'title' => $oCloseMsg->plain(),
			  'data-confirm-msg' => $oConfirmMsg->plain(),
		  ] );
		$sOut .= \Xml::closeElement( 'div' );
		$sOut .= \Xml::closeElement( 'div' );
		return $sOut;
	}

}
