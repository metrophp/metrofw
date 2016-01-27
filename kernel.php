<?php


class Metrofw_Kernel {

	public static $singleton = NULL;

	public $container        = NULL;
	public $serviceList      = array();

	/**
	 * Cache reference
	 */
	public function __construct($container=NULL) {
		$this->container = $container;
		if ($this->container == NULL) {
			$this->container = Metrodi_Container::getContainer();
		}

		ini_set('display_errors', 'on');
		set_exception_handler( array(&$this, 'onException') );
		set_error_handler( array(&$this, 'onError') );
		register_shutdown_function( array(&$this, 'handleFatal') );

		Metrofw_Kernel::$singleton = $this;
	}

	public static function getKernel($container=NULL) {
		if (Metrofw_Kernel::$singleton === NULL) {
			new Metrofw_Kernel($container);
		}
		return Metrofw_Kernel::$singleton;
	}

	/**
	 * Run lifecycles:
	 *  analyze
	 *  resources
	 *  authenticate
	 *  process
	 *  output
	 *  hangup
	 */
	public function runMaster() {
		$this->_runLifecycle('analyze');
		$this->_runLifecycle('resources');
		$this->_runLifecycle('authenticate');
		$this->_runLifecycle('authorize');
		$this->_runLifecycle('process');
		$this->_runLifecycle('output');
		$this->_runLifecycle('hangup');
	}

	/**
	 * Handle signals and slots, which return TRUE to keep the 
	 * event propogating
	 */
	public static function emit($signalName, $source, &$args=array()) {
		$k = Metrofw_Kernel::getKernel();
		return $k->signal($signalName, $source, $args);
	}

	public function signal($signalName, $source, &$args=array()) {
		$container = $this->container;
		if (empty($args) && count($args) == 0) {
			$args['request']  = $container->make('request');
			$args['response'] = $container->make('response');
		}
		$signal = $container->make('signal');
		$signal->set('source', $source);
		$signal->set('name',   $signalName);
		$continue = true;
		while ($svc = $this->whoCanHandle($signalName.'_pre')) {
			if (is_callable($svc))
			$continue = $svc[0]->{$svc[1]}($signal, $args);
			if (!$continue) {break;}
		}

		while ($svc = $this->whoCanHandle($signalName)) {
			if (is_callable($svc))
			$continue = $svc[0]->{$svc[1]}($signal, $args);
			if (!$continue) {break;}
		}

		while ($svc = $this->whoCanHandle($signalName.'_post')) {
			if (is_callable($svc))
			$continue = $svc[0]->{$svc[1]}($signal, $args);
			if (!$continue) {break;}
		}

		return $continue;
	}

	public static function runLifeCycle($cycle) {
		$k = Metrofw_Kernel::getKernel();
		$k->_runLifecycle($cycle);
/*
		while ($svc = $k->whoCanHandle('master')) {
			$svc[0]->_runLifeCycle($cycle);
		}
*/
	}

	public function _runLifeCycle($cycle) {
		while ($svc = $this->whoCanHandle($cycle)) {

			if (is_object($svc[0]) && ($svc[0] instanceof Closure)) {
				$method = new ReflectionFunction($svc[0]);
			} else {
				if (!is_callable($svc)) {
					continue;
				}
				$method = new ReflectionMethod($svc[0], $svc[1]);
			}
			$params = $method->getParameters();
			$args   = array();

			foreach ($params as $k=>$v) {
				$value = NULL;
				$thing = $v->name; //assume untyped parameter
				if ($v->getClass()) {
					$thing = $v->getClass()->name;
				}
				//try simple get/set flags
				$value = $this->container->get($thing, NULL);
				//try making an object
				if ($value === NULL && $this->container->isThingDefined($thing)) {
					$value = $this->container->make($thing);
				}
				//just get default value defined in function sig
				if ($value === NULL && $v->isDefaultValueAvailable()) {
					$value = $v->getDefaultValue();
				}
				//lastly, just give a prototype object
				if ($value === NULL) {
					$value = $this->container->make($thing);
				}

				$args[] = $value;
			}

			if ($method->getName() == '{closure}') {
				$method->invokeArgs($args);
			} else {
				$method->invokeArgs($svc[0], $args);
			}
		}
	}
/*
	public function _runLifeCycle($cycle) {
		$request  = $this->container->getMeA('request');
		$response = $this->container->getMeA('response');
		while ($svc = $this->container->whoCanHandle($cycle)) {
			if (is_callable($svc))
			$svc[0]->{$svc[1]}($request, $response);
		}
		return $request;
	}
*/

	public function onException($ex) {
		if (!$this->hasHandlers('exception')) {
			$response = _make('response');
			$response->statusCode = 500;
			$this->_runLifeCycle('output');
			echo($ex);
		} else {
			_set('last_exception', $ex);
			$this->_runLifeCycle('exception');
			_set('last_exception', null);
		}
		return TRUE;
	}

	public function handleFatal() {
		$error = error_get_last();
		switch ($error['type']) {
			case E_ERROR:
			case E_PARSE:
			return $this->onError( $error["type"], $error["message"], $error["file"], $error["line"] );
		}
		return TRUE;
	}

	public function onError($errno, $errstr, $errfile, $errline, $errcontext=array()) {
		static $count=0;
		if (!($errno & error_reporting())) {
			return TRUE;
		}

		if (!$this->hasHandlers('exception')) {
			$response = _make('response');
			$response->statusCode = 500;
			$this->_runLifeCycle('output');
			echo ($errfile. ' ['.$errline.'] '.$errstr .' <br/> '.PHP_EOL);
			$count++;
			//killswitch
			if ($count > 100) {
				exit();
			}
		} else {
			_set('last_exception', new Exception($errfile. ' ['.$errline.'] '.$errstr , $errno));
			$this->_runLifeCycle('exception');
			_set('last_exception', null);
		}
		return TRUE;
	}



	/**
	 * Return true if there is any handler defined for a service
	 */
	public function hasHandlers($service) {
		$post = 'post_'.$service;
		return (
			(isset($this->serviceList[$service]) &&
			is_array($this->serviceList[$service]) &&
			count($this->serviceList[$service]) > 0)
			|| (isset($this->serviceList[$post]) &&
			is_array($this->serviceList[$post]) &&
			count($this->serviceList[$post]) > 0)
			);
	}


	/**
	 * Get an object or callback reference for who can handle this service.
	 * @return Mixed  Object or callback array suitable for dropping into call_user_func()
	 */
	public function whoCanHandle($service) {
		$endService = 'post_'.$service;
		$calledService = $service;
		//maybe we have only a post service (priority = 3)
		if (!isset($this->serviceList[$service])) {
			$service = $endService;
		}

		//maybe we have no services
		if (!isset($this->serviceList[$service])) {
			return FALSE;
		}
		$filesep = '/';
		$objList = array();

		$svc = each($this->serviceList[$service]);

		//done with service list
		if ($svc === FALSE && !isset($this->serviceList[$endService])) {
			reset($this->serviceList[$service]);
			return FALSE;
		}
		//not done with post_service list
		if ($svc == FALSE) {
			$svc = each($this->serviceList[$endService]);
			if ($svc === FALSE) {
				reset($this->serviceList[$service]);
				reset($this->serviceList[$endService]);
				return FALSE;
			}
		}

		//change service.sub.sub to serviceSubSub()
		if(strpos($calledService, '.') !== FALSE) {
			//clean cycle.sub.sub into cycleSubSub
			$funcParts = explode('.', $calledService);
			array_walk( $funcParts, function(&$value, $key) {
					$value = ucfirst($value);
					});
			$calledService = lcfirst( implode('', $funcParts ));
		}

		//you can tell the container iCanHandle('service', $obj)
		// as well as passing it a file.
		if (is_object($svc[1])) {
			return array($svc[1], $calledService);
		}

		//you can also pass an callback array iCanHandle('service', array($obj, 'func'))
		if (is_array($svc[1])) {
			return $svc[1];
		}

		//assume iCanHandle() was passed a file string
		$file  = $svc[1];

		if ($file === FALSE)
			return FALSE;

		//callback function defaults to name of service
		$func = $calledService;

		//check for function name embedded in filename
		if (strpos($file, '::')!==FALSE) {
			list($file, $func) = explode('::', $file);
		}

		unset($svc);

		// cachekey is just the file because services are
		// designed to be singleton
		if (!$this->container->loadAndCache($file, $file)) {
			//can't find a file, just keep going with recursion
			return $this->whoCanHandle($service);
		}
		//return $this->container->make($file);
		return array($this->container->objectCache[$file], $func);
	}

	public function iCanHandle($service, $file, $priority=2) {
		if ($priority == 3) {
			$service = 'post_'.$service;
		}
		if ($priority == 1) {
			if (!isset($this->serviceList[$service])) {
				$this->serviceList[$service] = array();
			}
			array_unshift($this->serviceList[$service], $file);
			reset($this->serviceList[$service]);
		} else {
			$this->serviceList[$service][] = $file;
		}
	}

	/**
	 * @DEPRECATED
	 */
	public function iCanOwn($service, $file) {
		//resets automatically
		$this->serviceList[$service] = array($file);
		$endService = 'post_'.$service;
		if (isset($this->serviceList[$endService])) {
			$this->serviceList[$endService] = array();
		}
	}

	public function clearHandlers($service) {
		unset($this->serviceList[$service]);
		unset($this->serviceList['post_'.$service]);
	}

}

function _iCanHandle($service, $file, $priority=2) {
	$a = Metrofw_Kernel::getKernel();
	$a->iCanHandle($service, $file, $priority);
}

function _connect($service, $locator, $priority=2) {
	$a = Metrofw_Kernel::getKernel();
	$a->iCanHandle($service, $locator, $priority);
}

function _connectSignal($service, $locator, $priority=2) {
	$a = Metrofw_Kernel::getKernel();
	$a->iCanHandle($service, $locator, $priority);
}

/**
 * @DEPRECATED
 */
function _iCanOwn($service, $file) {
	$a = Metrofw_Kernel::getKernel();
	$a->iCanOwn($service, $file);
}

function _clearHandlers($service) {
	$a = Metrofw_Kernel::getKernel();
	$a->clearHandlers($service);
}

function _hasHandlers($service) {
	$a = Metrofw_Kernel::getKernel();
	return $a->hasHandlers($service);
}

