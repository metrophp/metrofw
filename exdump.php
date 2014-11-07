<?php
class Metrofw_Exdump {

	static public function onException($request, $response) {
		static $c=0;
		echo $c++;
		$exception = _get('last_exception');
		include(dirname(__FILE__).'/files/exception_template.html.php');
	}
}
