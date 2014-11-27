<?php

class Metrofw_Router {

	public $cycles = 0;

	public function analyze($request) {

/*
		if ($request->requestedUrl == '' && $this->cycles == 0) {
			$this->cycles++;
			//let's stack ourselves at the end
			_iCanHandle('analyze',  'metrofw/router.php');
			return;
		}
*/
		$url = $request->requestedUrl;

		_set('baseuri', $request->baseUri);

		//not using rewrite?
		if ($request->rewrite == FALSE) {
			_set('appuri', $request->baseUri. $request->script.'/');
		} else {
			_set('appuri', $request->baseUri );
		}


		if (strpos($url, '/dologin') === 0) {
			$request->appUrl  = 'login';
			$request->appName = 'login';
			_iCanHandle('process', 'metrou/authenticator.php::login');
			return;
		}

		if (strpos($url, '/dologout') === 0) {
			$request->appUrl  = 'logout';
			$request->appName = 'logout';
			_iCanHandle('authenticate', 'metrou/logout.php');
			return;
		}

		if (strpos($url, '/hello') === 0) {
			$request->appUrl  = 'hello';
			$request->appName = 'hello';
			_iCanOwn('output', 'example/helloworld.php');
			return;
		}

		$parts = explode('/', $url);
		if (!isset($parts[1]) || $parts[1] == '') {
			$parts[1] = _get('main_module', 'main');
		}

		$default = 'main';
		if ($request->isAdmin) {
			$default = 'admin';
		}

		_iCanHandle('analyze',       $parts[1].'/'.$default.'.php');
		_iCanHandle('resources',     $parts[1].'/'.$default.'.php');
		_iCanHandle('authenticate',  $parts[1].'/'.$default.'.php');
		_iCanHandle('authorize',     $parts[1].'/'.$default.'.php');
		_iCanHandle('process',       $parts[1].'/'.$default.'.php');
		_iCanHandle('output',        $parts[1].'/'.$default.'.php');
		_iCanHandle('hangup',        $parts[1].'/'.$default.'.php');

		_iCanHandle('analyze',  'metrofw/router.php::autoRoute', 3);
	}

	/**
	 * If nothing has routed the request try route_rules pattern matching or our best guess
	 */
	public function autoRoute($request, $response) {
		if ($request->isRouted) {
			return;
		}

		$url = $request->requestedUrl;
		//remove initial /
		$listUrl = explode('/', $url);
		array_shift($listUrl);
		//remove trailing slash
		if (empty($listUrl [ (count($listUrl)-1) ])) {
			array_pop($listUrl);
		}
		//remove /id=X/ from  URL
		foreach ($listUrl as $_key => $_url) {
			if (strpos($_url, '=') !== FALSE ) {
				unset($listUrl[$_key]);
			}
		}

		$rules = _get('route_rules');
		if ($rules) {
			foreach ($rules as $pattern => $params) {
				$listPat = explode('/', $pattern);
				//remove initial /
				array_shift($listPat);

				//if pattern is longer, forget it
				if (count($listPat) > count($listUrl)) continue;

				foreach ($listPat as $_kPat => $_vPat) {
					if ( substr($_vPat, 0, 1) === ':' ) {
						$params[ substr($_vPat, 1) ] = $listUrl[$_kPat];
					} else {
						//ensure the pattern matches the url exactly
						if ($listPat[ $_kPat ] != $listUrl[ $_kPat ]) continue 2;
					}
				}
				foreach ($params as $_i => $_j) {
					$request->set($_i, $_j);
				}

				$request->isRouted = TRUE;
				_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
				return;
			}
/*
			foreach ($rules as $pattern => $route ) {
				$matches = array();
				preg_match($pattern, $url, $matches);
				if (count($matches)) {
					for ($regexx=1; $regexx<=(count($matches)); $regexx++) {
						foreach ($route as $_j => $_k) {
							$request->set($_j, str_replace('$'.$regexx, $matches[($regexx-1)], $_k));
						}
					}
					_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
					return;
				}
			}
*/
		}

		$parts = explode('/', $url);
		if (!isset($parts[1]) || $parts[1] == '') {
			$parts[1] = _get('main_module', 'main');
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

		_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::process');
		_iCanHandle('process',  $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
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
		$baseuri = _get('baseuri');
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
		$baseUri = _get('appuri');
	}

	$router = _getMeA('router');
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
		$baseUri = _get('appuri');
	}

	$router = _getMeA('router');
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
