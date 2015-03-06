<?php
class Metrofw_Exdump {

	public function onException($kernel, $response) {
		static $c=0;
		$response = _make('response');
		$response->statusCode = 500;
		$kernel->_runLifeCycle('output');

		$c++;
		$exception = _get('last_exception');
		include(dirname(__FILE__).'/files/exception_template.html.php');
	}
}
