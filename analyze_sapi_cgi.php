<?php

class Metrofw_Analyze_sapi_cgi {


	public function analyze($request) {
		$params = $_REQUEST;
		$get = $_GET;
		$request->sapiType = 'cgi';

		if (array_key_exists('PATH_INFO', $_SERVER) && $_SERVER['PATH_INFO'] != '') {
			$pathinfo = $_SERVER['PATH_INFO'];
		} else {
			//fix some broken PATH_INFO implementations
			// these are usually broken if front controller script is hidden
			$pathinfo = $this->makePathInfo();
		}

		if (array_key_exists('REQUEST_URI', $_SERVER) && $_SERVER['REQUEST_URI']!='') {
			$request->requestedUrl = $_SERVER['REQUEST_URI'];
			$request->requestedUrl = $pathinfo;
		}

		$front_controller_name  = '';
		$base_path              = '/';
		//remove index.php or admin.php if they are present at the end of REQUEST_URI
		if (array_key_exists('SCRIPT_NAME', $_SERVER)) {
			$script_parts = explode("/",substr($_SERVER['SCRIPT_NAME'],1));
			$front_controller_name  = array_pop($script_parts);
			$request->script = $front_controller_name;
			if (count($script_parts)) {
				$base_path    .= implode('/', $script_parts).'/';
			}
		}

		$pathinfo_parts = explode("/", trim($pathinfo, '/'));

		foreach($pathinfo_parts as $num=>$p) { 
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

		if (array_key_exists('REQUEST_URI', $_SERVER) && $_SERVER['REQUEST_URI']!='') { 		
			if (strpos($_SERVER['REQUEST_URI'], $request->script) !== FALSE) {
			$request->rewrite = FALSE;
			}
		}

		$request->vars = $params;
		$request->getvars = $get;
		$request->postvars = $_POST;

		// get the base URI 
		// store in the template config area for template processing
		$request->baseUri = $_SERVER['HTTP_HOST'].$base_path;

		if ($request->script == 'admin.php') {
			$request->isAdmin = TRUE;
			$request->rewrite = FALSE;
			$request->script  = 'admin.php';
		}

		//
		// determine if ajax
		// if X-Requested-With == 'XMLHttpRequest'
		//
		if (in_array( 'xhr', array_keys($request->vars),TRUE)
			|| (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
			$request->isAjax = TRUE;
			//if there's no JSON in ://input, then there's no postvars by definition
			// just quiet this annoying 'not an array' message
			$jsonvars = @array_merge($request->postvars, json_decode(file_get_contents('php://input'), TRUE ));
			if (is_array($jsonvars))
			$request->postvars = @array_merge($request->postvars, $jsonvars);
		} else {
			$request->isAjax = FALSE;
		}
	}


	/**
	 * Determine PATH_INFO if not provided.
	 * @return String  PATH_INFO as determined by SCRIPT_NAME and REQUEST_URI
	 */
	public function makePathInfo() {
		$pathinfo   = '';
		$base_path  = '/';
		if (array_key_exists('SCRIPT_NAME', $_SERVER)) {
			$script_parts = explode("/",substr($_SERVER['SCRIPT_NAME'],1));
			$front_controller_name  = array_pop($script_parts);

			if (count($script_parts)) { 
				$base_path   .= implode('/', $script_parts).'/';
			}

			//determine PATH_INFO
			$pathinfo = str_replace($base_path, '', $_SERVER['REQUEST_URI']);
			$pathinfo = str_replace($front_controller_name, '', $pathinfo);
			//remove query string
			if (strpos($pathinfo, '?') !== FALSE) {
				$pathinfo = substr($pathinfo, 0, strpos($pathinfo, '?'));
			}
			$pathinfo = '/'.$pathinfo;
		}
		return $pathinfo;
	}
}
