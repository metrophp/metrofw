<?php

class Metrofw_Analyze_sapi_http {

	public function analyze(&$request) {
		$params = $_REQUEST;
		$get = $_GET;
		$request->sapiType = 'http';
		if (array_key_exists('PATH_INFO', $_SERVER) && $_SERVER['PATH_INFO']!='') { 		
			$request->requestedUrl = $_SERVER['PATH_INFO'];

			if (substr($_SERVER['PATH_INFO'],-1) == '/' ) {
				$parts = explode("/",substr($_SERVER['PATH_INFO'],1,-1));
			} else {
				$parts = explode("/",substr($_SERVER['PATH_INFO'],1));
			}
			//remove index.php or admin.php if they are present
			if (array_key_exists('SCRIPT_FILENAME', $_SERVER)) {
				$sfn = explode('/', $_SERVER['SCRIPT_FILENAME']);
				if ($parts[0] == end($sfn)) {
					array_shift($parts);
				}
			}
			foreach($parts as $num=>$p) { 
				//only put url parts in the get and request
				// if there's no equal sign
				// otherwise you get duplicate entries "[0]=>foo=bar"
				if (!strstr($p,'=')) {
					$p = rawurldecode($p);
					$params[$num] = $p;
					$get[$num] = $p;
				} else {
					@list($k,$v) = explode("=",$p);
					if ($v!='') { 
						$k = rawurldecode($k);
						$v = rawurldecode($v);
						$params[$k] = $v;
						$get[$k] = $v;
					}
				}
			}
		}
		if (array_key_exists('REQUEST_URI', $_SERVER) && $_SERVER['REQUEST_URI']!='') { 		
			if (strpos($_SERVER['REQUEST_URI'], 'index.php') !== FALSE) {
			$request->rewrite = FALSE;
			$request->script = 'index.php';
			}
		}

		$request->vars = $params;
		$request->getvars = $get;
		$request->postvars = $_POST;

		// get the base URI 
		// store in the template config area for template processing

		$path   = explode("/",$_SERVER['SCRIPT_NAME']);
		$script = array_pop($path);	
		$path   = implode("/",$path);
		$uri    = $_SERVER['HTTP_HOST'].$path.'/';
		$request->baseUri = $uri;
		if ($script == 'admin.php') {
			$request->isAdmin = TRUE;
			$request->rewrite = FALSE;
			$request->script  = 'admin.php';
		}

/*
		if (!in_array( 'foobar', array_keys($request->vars),TRUE)) {
			var_dump($_SERVER);exit();
		}
*/
			
		/**
		 * determine if ajax
		 */
		if (in_array( 'xhr', array_keys($request->vars),TRUE)
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
			$request->isAjax = TRUE;
//			$request->postvars = array_merge($request->postvars, json_decode(file_get_contents('php://input'), TRUE ));
		} else {
			$request->isAjax = FALSE;
		}

		//if X-Requested-With == 'XMLHttpRequest'
	}
}
