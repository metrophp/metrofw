<?php

use PHPPM\Bridges;
use PHPPM\React\HttpResponse;
use React\Http\Request;

class Metrofw_Ppm implements PHPPM\Bridges\BridgeInterface
{
	public $kernel;
	public $container;

	/**
	 * Bootstrap an application implementing the HttpKernelInterface.
	 *
	 * @param string $appBootstrap The name of the class used to bootstrap the application
	 * @param string|null $appBootstrap The environment your application will use to bootstrap (if any)
	 * @param boolean $debug If debug is enabled
	 * @see http://stackphp.com
	 */
	public function bootstrap($appBootstrap, $appenv, $debug) {
		if (!include_once ('local/metrophp/metrodi/container.php')) {
			header('HTTP/1.1 500 Internal Server Error');
			echo 'System startup failure.  Incomplete dependencies.';
			exit();
		}
		if (!include_once ('local/metrofw/kernel.php')) {
			header('HTTP/1.1 500 Internal Server Error');
			echo 'System startup failure.  Incomplete dependencies.';
			exit();
		}

		include_once ('local/autoload.php');

		$this->container = Metrodi_Container::getContainer();
		$this->kernel    = new Metrofw_Kernel($this->container);
		_didef('kernel',    $this->kernel);
		_didef('container', $this->container);


		if(!include('etc/bootstrap.php')) {
			$this->container = NULL;
			$this->kernel    = NULL;
			return;
		}

	}

	/**
	 * Returns the repository which is used as root for the static file serving.
	 *
	 * @return string
	 */
	public function getStaticDirectory() {
		$templateName = _get('template_name', 'webapp01');
		_set('template_name', $templateName);
		$this->baseDir  = _get('template_basedir', 'local/templates/');

		return $this->baseDir . $templateName;
	}

	/**
	 * Handle a request using a HttpKernelInterface implementing application.
	 *
	 * @param \React\Http\Request $request
	 * @param \PHPPM\React\HttpResponse $response
	 */
	public function onRequest(\React\Http\Request $request, HttpResponse $response) {
		Metrodi_Container::$container = NULL;
		$this->container = Metrodi_Container::getContainer();
		$this->kernel->serviceList = array();
		$this->kernel->cycleStack  = array();
		$this->kernel->container   = $this->container;
		_didef('container', $this->container);

		if(!include('etc/bootstrap.php')) {
			$this->container = NULL;
			return;
		}

		try {
			$this->kernel->_runLifecycle('analyze');
			$this->kernel->_runLifecycle('resources');
			$this->kernel->_runLifecycle('authenticate');
			$this->kernel->_runLifecycle('authorize');
			$this->kernel->_runLifecycle('process');
			$this->kernel->_runLifecycle('output');
			$x = ob_get_contents();
			ob_end_clean();
			$this->kernel->_runLifecycle('hangup');
		} catch (Exception $e) {
			ob_end_clean();
		}

		$rsp = _make('response');
		$response->writeHead($rsp->get('statusCode'), ['Content-type'=>'text/html']);
		$response->write($x);
		$response->end();

		$this->container = NULL;

		_didef('container', $this->container);
	}
}
