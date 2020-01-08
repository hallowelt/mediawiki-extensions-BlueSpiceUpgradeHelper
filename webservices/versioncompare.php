<?php

// process user lang param
$sLang = filter_input( INPUT_GET, "lang" );
if ( $sLang == "de" ) {
	echo file_get_contents( "https://de.bluespice.com/subscriptionmanagement/ucs/" );
} else {
	echo file_get_contents( "https://bluespice.com/subscriptionmanagement/ucs/" );
}
