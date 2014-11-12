<?php

class Metrofw_Analyze_sapi_cli {

	public function analyze($request) {
		global $argv;
		$request->sapiType = 'cli';

		$get = array();
		//cron.php or index.php from arg list
		@array_shift($argv);
		$request->requestedUrl = implode('/', $argv);
//		$request->mse = $argv[0];
//		@array_shift($argv);

		foreach($argv as $num=>$p) { 
			//only put argv in the get and request
			// if there's no equal sign
			// otherwise you get duplicate entries "[0]=>foo=bar"

			if (!strstr($p,'=')) {
				$argv[$num] = $p;
				$get[$num] = $p;
			} else {
				@list($k,$v) = explode("=",$p);
				if ($v!='') { 
					$argv[$k] = $v;
					$get[$k] = $v;
				}
			}
		}
		$request->getvars = $get;

		/**
		 * determine if ajax
		 */
		if (in_array( 'xhr', array_keys($request->getvars),TRUE)) {
			$request->isAjax = TRUE;
		} else {
			$request->isAjax = FALSE;
		}
	}
}
