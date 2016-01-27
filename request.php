<?php

/**
 * The Request object is responsible for modeling
 * most of the _SERVER vars and other things about
 * the execution environment.
 */
class Metrofw_Request {

	public $vars           = array();
	public $getvars        = array();
	public $postvars       = array();
	public $cookies        = array();
	public $isAdmin        = FALSE;
	public $isRouted       = FALSE;
	public $rewrite        = TRUE;
	public $unauthorized   = FALSE;
	public $method         = '';
	public $requestedUrl   = '';
	public $baseUri        = '';
	public $moduleName     = '';
	public $serviceName    = '';
	public $eventName      = '';
	public $sapiType       = '';
	public $isAjax         = FALSE;
	public $prodEnv        = 'prod';
	public $statusCode     = '200';
	public $remoteAddr     = '';
	public $proxyAddr      = '';
	public $ssl            = '';

	public $appUrl         = '';
	public $appName        = 'main';
	public $modName        = 'main';
	public $actName        = 'main';

	public function set($k, $v) {
		$this->{$k} = $v;
	}

	/**
	 * @return boolean True if this production environment is 'demo'
	 */
	public function isDemo() {
		return $this->isEnv('demo');
	}

	/**
	 * @return boolean True if this production environment is 'test'
	 */
	public function isTest() {
		return $this->isEnv('test');
	}

	/**
	 * @return boolean True if this production environment is 'prod'
	 */
	public function isProduction() {
		return $this->isEnv('prod');
	}

	/**
	 * @return boolean True if this production environment is 'dev'
	 */
	public function isDevelopment() {
		return $this->isEnv('dev');
	}

	/**
	 * @return boolean True if this production environment is $state
	 */
	public function isEnv($state) {
		return $this->prodEnv == $state;
	}

	/**
	 * Return the default session object.
	 *
	 * @return Object   the default session object.
	 */
	public function getSession() {
		return _make('session');
	}

	public function getMethod() {
		return $this->method;
	}

	public function header($h) {
		if (array_key_exists($h, $_SERVER)) {
			return $_SERVER[$h];
		}
		return NULL;
	}

	/**
	 * removes effects of Magic Quotes GPC
	 */
	public function stripMagic() {
		//runtime magic quotes are quite rare, and even
		// turning them off results in an error above PHP 5.3.0
		if (version_compare(phpversion(), '5.3.0', '<')) {
			@set_magic_quotes_runtime(0);
		}
		// if magic_quotes_gpc strip slashes from GET POST COOKIE
		if (get_magic_quotes_gpc()){
		function stripslashes_array($array) {
		 return is_array($array) ? array_map('stripslashes_array',$array) : stripslashes($array);
		}
		$_GET= stripslashes_array($_GET);
		$_POST= stripslashes_array($_POST);
		$_REQUEST= stripslashes_array($_REQUEST);
		$_COOKIE= stripslashes_array($_COOKIE);
		}
	}

	/**
	 * This method finds a parameter from the GET or POST. 
	 * Order of preference is GET then POST
	 *
	 * @return bool  true if the key exists in get or post
	 */
	public function hasParam($name) {
		if (isset($this->getvars[$name])) {
			return TRUE;
		}
		if (isset($this->postvars[$name])) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * This method cleans a string from the GET or POST. 
	 * It does *not* escape data safely for SQL.
	 * Order of preference is GET then POST
	 *
	 * @return string
	 */
	public function cleanString($name) {
		if (isset($this->getvars[$name])){
			$val = $this->getvars[$name];
		} else {
			$val = @$this->postvars[$name];
		}
		if ($val == '') {
			return '';
		}
		if (is_array($val)) {
			array_walk_recursive($val, array($this, 'removeCtrlChar'));
		} else {
		   	$this->removeCtrlChar($val);
			$val = (string)$val;
		}
		return $val;

	}

	/**
	 * This method calls HTMLspecialchars on the result of $this->cleanString();
	 */
	public function escapeString($name) {
		return htmlspecialchars( $this->cleanString($name) );
	}


	/**
	 * This method cleans a multi-line string from the GET or POST. 
	 * It does *not* escape data safely for SQL.
	 * Order of preference is GET then POST
	 *
	 * This method allows new line, line feed and tab characters
	 * @return string
	 */
	public function cleanMultiLine($name) {
		if (isset($this->getvars[$name])){
			$val = $this->getvars[$name];
		} else {
			$val = @$this->postvars[$name];
		}
		if ($val == '') {
			return '';
		}
		$allow = array();
		$allow[] = ord("\t");
		$allow[] = ord("\n");
		$allow[] = ord("\r");

		if (is_array($val)) {
			array_walk_recursive($val, array($this, 'removeCtrlChar'), $allow);
		} else {
		   	$this->removeCtrlChar($val, NULL, $allow);
			$val = (string)$val;
		}
		return $val;

	}

	/**
	 * This method cleans an integer from the GET or POST. 
	 * It always returns the result of intval()
	 * Order of preference is GET then POST
	 *
	 * @return int
	 */
	public function cleanInt($name) {
		if (isset($this->getvars[$name])){
			if (is_array($this->getvars[$name])){
				return Metrofw_Request::cleanIntArray($this->getvars[$name]);
			}
			return intval($this->getvars[$name]);
		} else {
			if (@is_array($this->postvars[$name])){
				return Metrofw_Request::cleanIntArray($this->postvars[$name]);
			}
			return intval(@$this->postvars[$name]);
		}
	}

	/**
	 * Clean a multi-dimensional array of ints
	 */
	static public function cleanIntArray($input, $loop=0) {
		if ($loop > 100) return (int)$input;
		if (!is_array($input)) return (int)$input;
		$output = array();
		foreach ($input as $k=>$v) {
			$output[$k] = self::cleanIntArray($input, $loop++);
		}
		return $output;
	}

	/**
	 * This method cleans a float from the GET or POST. 
	 * It always returns the result of floatval()
	 * Order of preference is GET then POST
	 *
	 * @return float
	 */
	public function cleanFloat($name) {
		if (isset($this->getvars[$name])){
			if (is_array($this->getvars[$name])){
				return Metrofw_Request::cleanFloatArray($this->getvars[$name]);
			}
			return floatval($this->getvars[$name]);
		} else {
			if (@is_array($this->postvars[$name])){
				return Metrofw_Request::cleanFloatArray($this->postvars[$name]);
			}
			return floatval(@$this->postvars[$name]);
		}
	}

	/**
	 * Clean a multi-dimensional array of floats
	 */
	static public function cleanFloatArray($input, $loop=0) {
		if ($loop > 100) return floatval($input);
		if (!is_array($input)) return floatval($input);
		$output = array();
		foreach ($input as $k=>$v) {
			$output[$k] = self::cleanFloatArray($input, $loop++);
		}
		return $output;
	}

	/**
	 * This method cleans a boolean from the GET or POST. 
	 * It always returns the result of (bool)
	 * Order of preference is GET then POST
	 *
	 * @return float
	 */
	public function cleanBool($name) {
		if (isset($this->getvars[$name])){
/*
			if (is_array($this->getvars[$name])){
				return Metrofw_Request::cleanBoolArray($this->getvars[$name]);
			}
*/
			return (bool)$this->getvars[$name];
		} else {
/*
			if (@is_array($this->postvars[$name])){
				return Metrofw_Request::cleanBoolArray($this->postvars[$name]);
			}
*/
			return (bool)@$this->postvars[$name];
		}
	}


	/**
	 * This method cleans a string from the GET or POST, removing any HTML tags. 
	 * It does *not* escape data safely for SQL.
	 * Order of preference is GET then POST
	 *
	 * @return string
	 */
	public function cleanHtml($name) {
		if (isset($this->getvars[$name])){
			return (string)strip_tags(urldecode($this->getvars[$name]));
		} else {
			return (string)@strip_tags(urldecode($this->postvars[$name]));
		}
	}

	/**
	 * Replaces any non-printable control characters with underscores (_).
	 * Can be called with array_walk or array_walk_recursive
	 */
	public function removeCtrlChar(&$input, $key = NULL, $allow = array()) {
		//preg throws an error if the pattern cannot compile
		$len = strlen($input);
		$extra = count($allow);
		for($i = 0; $i < $len; $i++) {
			$hex =ord($input{$i});
			if ($extra && in_array($hex, $allow)) {
				continue;
			}
			if ( ($hex < 32) ) {
				$input{$i} = '_';
			}
			if ($hex == 127 ) {
				$input{$i} = '_';
			}
		}
	}

	public function getUser() {
		return _make('user');
	}
}

