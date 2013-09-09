<?php

class Metrofw_Router {

	public $cycles = 0;

	public function analyze(&$request) {

		if ($request->requestedUrl == '' && $this->cycles == 0) {
			$this->cycles++;
			//let's stack ourselves at the end
			associate_iCanHandle('analyze',  'metrofw/router.php');
			return;
		}
		$url = $request->requestedUrl;

		associate_set('baseuri', $request->baseUri);

		//not using rewrite?
		if ($request->rewrite == FALSE) {
			associate_set('appuri', $request->baseUri. $request->script.'/');
		} else {
			associate_set('appuri', $request->baseUri );
		}


		if (strpos($url, '/dologin') === 0) {
			$request->appUrl  = 'login';
			$request->appName = 'login';
			associate_iCanHandle('process', 'metrou/authenticator.php::login');
			return;
		}

		if (strpos($url, '/dologout') === 0) {
			$request->appUrl  = 'logout';
			$request->appName = 'logout';
			associate_iCanHandle('authenticate', 'metrou/logout.php');
			return;
		}

		if (strpos($url, '/hello') === 0) {
			$request->appUrl  = 'hello';
			$request->appName = 'hello';
			associate_iCanOwn('output', 'example/helloworld.php');
			return;
		}

		$parts = explode('/', $url);
		if (!isset($parts[1]) || $parts[1] == '') {
			$parts[1] = associate_get('main_module', 'main');
		}

		$default = 'main';
		if ($request->isAdmin) {
			$default = 'admin';
		}

		associate_iCanHandle('analyze',  $parts[1].'/'.$default.'.php');
		associate_iCanHandle('resources',  $parts[1].'/'.$default.'.php');
		associate_iCanHandle('authenticate',  $parts[1].'/'.$default.'.php');

		associate_iCanHandle('analyze',  'metrofw/router.php::autoRoute', 3);
	}

	/**
	 * If nothing has routed the request try route_rules pattern matching or our best guess
	 */
	public function autoRoute($request, $response) {
		if ($request->isRouted) {
			return;
		}

		$url = $request->requestedUrl;

		$rules = associate_get('route_rules');
		if ($rules) {
			foreach ($rules as $pattern => $route ) {
				$matches = array();
				preg_match($pattern, $url, $matches);
				if (count($matches)) {
					for ($regexx=1; $regexx<=(count($matches)); $regexx++) {
						foreach ($route as $_j => $_k) {
							$request->set($_j, str_replace('$'.$regexx, $matches[($regexx-1)], $_k));
						}
					}
					associate_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
					return;
				}
			}
		}

		$parts = explode('/', $url);
		if (!isset($parts[1]) || $parts[1] == '') {
			$parts[1] = associate_get('main_module', 'main');
		}

		$default = 'main';
		if ($request->isAdmin) {
			$default = 'admin';
		}

		if (!isset($parts[2]) || $parts[2] == '') {
			$parts[2] = 'main';
		}

		$request->appName = $parts[1];
		$request->appUrl  = $parts[1];
		$request->modName = $default;
		$request->actName = $parts[2];

		associate_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::process');
		associate_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
	}

	public function unrouteUrl($app) {
		return $app;
	}

	public function formatArgs($args) {
		if ($args === NULL) {
			return '';
		}
		if (!is_array($args)) {
			return '';
		}

		$v = '';
		foreach ($args as $_k => $_v) {
			$v .= '/'.urlencode($_k).'='.urlencode($_v);
		}
		return $v;
	}
}

/**
 * Build a URL the same way this router analyzes one.
 */
function m_url($https=0) {
	static $baseuri;
	if (!$baseuri) {
		$baseuri = associate_get('baseuri');
	}

	if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']== 'on') || $https>0) {
		return 'https://'.$baseuri;
	} else {
		return 'http://'.$baseuri;
	}
}

/**
 */
function m_appurl($url='', $args=null, $https=-1) {
	static $baseUri;
	static $templateName;
	static $templatePath;
	if (!$baseUri) {
		$baseUri = associate_get('appuri');
	}

	$router = associate_getMeA('router');
	$url  = $router->unrouteUrl($url);
	$url .= $router->formatArgs($args);
	$end  = $baseUri.$url;
	if (substr($end, -1) !== '/') {
		$end .= '/';
	}

	if ($https === 0) {
		return 'http://'.$end;
	} else if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $https>0) {
		return 'https://'.$end;
	} else {
		return 'http://'.$end;
	}
}

/**
 */
function m_pageurl($url, $args=null, $https=-1) {
	static $baseUri;
	static $templateName;
	static $templatePath;
	if (!$baseUri) {
		$baseUri = associate_get('appuri');
	}

	$router = associate_getMeA('router');
	// *
	$url  = $router->unrouteUrl($url);
	$url .= $router->formatArgs($args);
	$end  = $baseUri.$url;
	// * /

	if ($https === 0) {
		return 'http://'.$end;
	} else if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $https>0) {
		return 'https://'.$end;
	} else {
		return 'http://'.$end;
	}
}
