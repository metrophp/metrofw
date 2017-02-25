<?php

class Metrofw_Router {

	public $cycles = 0;

	public function analyze($request) {
		$url = $request->requestedUrl;

		_set('baseuri', $request->baseUri);

		//not using rewrite?
		if ($request->rewrite == FALSE) {
			_set('appuri', $request->baseUri. $request->script.'/');
		} else {
			_set('appuri', $request->baseUri );
		}


		return $this->routeSpecial($request);
	}

	public function routeSpecial($request) {

		$url = $request->requestedUrl;

		if (strpos($url, '/dologin') === 0) {
			$request->appUrl  = 'login';
			$request->appName = 'login';
			$request->isRouted = TRUE;
			_iCanHandle('process', 'metrou/authenticator.php::login');
			return;
		}

		if (strpos($url, '/dologout') === 0) {
			$request->appUrl  = 'logout';
			$request->appName = 'logout';
			$request->isRouted = TRUE;
			_iCanHandle('authenticate', 'metrou/logout.php');
			return;
		}

		//@DEPRECATED
		_iCanHandle('analyze',  'metrofw/router.php::autoRoute', 3);
	}

	/**
	 * Old method name, now runs default behavior if only request is not
	 * already routed 
	 */
	public function autoRoute($request, $kernel=NULL, $container=NULL) {
		if (!$request->isRouted) {
			$this->routeRules($request, $kernel, $container);
			$this->routeAppMain($request, $kernel, $container);
		}
	}


	public function routeRules($request, $kernel=NULL, $container=NULL) {
		if ($request->isRouted) {
			return;
		}
		if ($container === NULL) {
			$container = Metrodi_Container::getContainer();
		}

		if ($kernel === NULL) {
			$kernel = Metrofw_Kernel::getKernel($container);
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

		$rules = $container->get('route_rules');
		if (!$rules) {
			return;
		}

		$matched = $this->matchRequestRules($rules, $request, $listUrl);

		//submitted action always overrides any rules
		//because it is unlikely that anything in the provided rules
		//maps to /app/module/action
		if (array_key_exists('action', $request->vars)) {
			$request->actName = $request->vars['action'];
		}


		if ($matched) {
			$request->isRouted = TRUE;
			$kernel->iCanHandle('analyze',      $request->appName.'/'.$request->modName.'.php');
			$kernel->iCanHandle('resources',    $request->appName.'/'.$request->modName.'.php');
			$kernel->iCanHandle('authorize',    $request->appName.'/'.$request->modName.'.php');
			$kernel->iCanHandle('process',      $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
			$kernel->iCanHandle('output',       $request->appName.'/'.$request->modName.'.php', 1);
			$kernel->iCanHandle('hangup',       $request->appName.'/'.$request->modName.'.php');
		}
	}

	/**
	 * Assume the first part of the URL is the application name
	 * route module to main.php
	 * check for "action" as second URL param
	 * check for "action" in GET and POST vars, override param if found
	 * route action to mainAction
	 */
	public function routeAppMain($request, $kernel=NULL, $container=NULL) {
		if ($request->isRouted) {
			return;
		}

		if ($container === NULL) {
			$container = Metrodi_Container::getContainer();
		}

		if ($kernel === NULL) {
			$kernel = Metrofw_Kernel::getKernel($container);
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

		$request->appName = $container->get('main_module', 'main');
		$request->modName = 'main';
		$request->actName = 'main';

		if (isset($listUrl[0]) && $listUrl[0] !== '') {
			$request->appName = $listUrl[0];
		}

		if (isset($listUrl[1]) && $listUrl[1] !== '') {
			$request->actName = $listUrl[1];
		}

		//allow override action to be sent as part of forms
		if (array_key_exists('action', $request->vars)) {
			$request->actName = $request->vars['action'];
		}

		$kernel->iCanHandle('analyze',      $request->appName.'/'.$request->modName.'.php');
		$kernel->iCanHandle('resources',    $request->appName.'/'.$request->modName.'.php');
		$kernel->iCanHandle('authorize',    $request->appName.'/'.$request->modName.'.php');
		$kernel->iCanHandle('process',      $request->appName.'/'.$request->modName.'.php::'.$request->actName.'Action');
		$kernel->iCanHandle('output',       $request->appName.'/'.$request->modName.'.php', 1);
		$kernel->iCanHandle('hangup',       $request->appName.'/'.$request->modName.'.php');

	}

	public function matchRequestRules($rules, $request, $listUrl) {

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
			return TRUE;
		}
		return FALSE;
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
	static $baseuri, $request;
	if (!$baseuri) {
		$baseuri = _get('baseuri');
	}
	if (!$request) {
		$request = _make('request');
	}

	if ($request->ssl || $https>0) {
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
	static $request;
	if (!$baseUri) {
		$baseUri = _get('appuri');
	}
	if (!$request) {
		$request = _make('request');
	}

	$router = _make('router');
	$url  = $router->unrouteUrl($url);
	$url .= $router->formatArgs($args);
	$end  = $baseUri.$url;
	if (substr($end, -1) !== '/') {
		$end .= '/';
	}

	if ($https === 0) {
		return 'http://'.$end;
	} else if ($request->ssl || $https>0) {
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
	static $request;
	if (!$baseUri) {
		$baseUri = _get('appuri');
	}
	if (!$request) {
		$request = _make('request');
	}

	$router = _make('router');
	// *
	$url  = $router->unrouteUrl($url);
	$url .= $router->formatArgs($args);
	$end  = $baseUri.$url;
	// * /

	if ($https === 0) {
		return 'http://'.$end;
	} else if ($request->ssl || $https>0) {
		return 'https://'.$end;
	} else {
		return 'http://'.$end;
	}
}
