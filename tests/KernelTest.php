<?php
include_once(dirname(__DIR__).'/kernel.php');

class Metrofw_Tests_Kernel extends PHPUnit_Framework_TestCase { 

	public $kernel = NULL;

	public function setUp() {
		$this->kernel = new Metrofw_Kernel(Metrodi_Container::getContainer());
	}

	public function test_event_params_are_mutable() {
		_iCanHandle('Fire', array($this, 'evtHandler'));
		_iCanHandle('Fire_post', array($this, 'evtPostHandler'));
		$x = 1;
		$y = 'a';
		$args = array($x, $y);
		$result = Metrofw_Kernel::event('Fire', $this, $args);
		$this->assertTrue($result);
		$this->assertEquals( $args[0], 2);
		$this->assertEquals( $args[1], 'z');
	}

	public function evtHandler($evt, &$args) {
		$args[0] = 2;
		return TRUE;
	}

	public function evtPostHandler($evt, &$args) {
		$args[1] = 'z';
		return TRUE;
	}

	public function test_skip_bad_handlers() {
		_iCanHandle('testphase1', 'non/existant.php');
		_iCanHandle('testphase1', 'non/existant2.php');
		$k = Metrofw_Kernel::getKernel();
		while ($svc = $k->whoCanHandle('testphase1')) {
			$this->assertFail(true);
		}
	}

	/**
	 * Ensure that iCanHandle produces the correct number of handler objects
	 */
	public function test_looping_over_multiple_handlers_works() {
		_iCanHandle('testphase2', 'tests/emptyhandler.txt');
		_iCanHandle('testphase2', 'tests/emptyhandler.txt');
		$k = Metrofw_Kernel::getKernel();
		$count = 0;
		$prevsvc = array();
		while ($svc = $k->whoCanHandle('testphase2')) {
			$prevsvc = $svc;
			++$count;
		}
		$this->assertTrue( is_object($prevsvc[0]) );
		$this->assertEquals( 2, $count );
	}

	/**
	 * Ensure you can pass an existing object reference as
	 * a lifecycle handler
	 */
	public function test_use_real_objects_as_handlers() {
		$k = $this->kernel;
		$k->container->didef('emptyhandler', 'nofw/tests/emptyhandler.txt');
		$obj = $k->container->make('emptyhandler');
		_iCanHandle('testphase3', $obj);
		$prevsvc = array();
		while ($svc = $k->whoCanHandle('testphase3')) {
			$prevsvc = $svc;
		}
		$this->assertTrue( is_object($prevsvc[0]) );
	}

	/**
	 * Ensure you can pass a user call back array as a lifecycle 
	 * handler
	 */
	public function test_use_array_callback_as_handlers() {
		$k = $this->kernel;
		$k->container->didef('emptyhandler', 'nofw/tests/emptyhandler.txt');
		$obj = $k->container->make('emptyhandler');
		_iCanHandle('testphase4', array(&$obj, 'dummyfunc'));
		$svc = $k->whoCanHandle('testphase4');
		$this->assertTrue( is_array($svc) );
		$this->assertTrue( is_object($svc[0]) );
		$this->assertSame( $obj, $svc[0] );
		$this->assertEquals( 'dummyfunc', ($svc[1]) );
	}

	/**
	 */
	public function test_lifecycles_get_called() {
		$k = $this->kernel;
		$stub = new Metrofw_Tests_Kernel_Handler();
		$k->iCanHandle('authorize', $stub);

		$k->runLifecycle('authorize');
		$this->assertEquals( 1, $stub->called );
	}

	public function test_lifecycles_inject_ctor_params() {
		$k = $this->kernel;
		$stub = new Metrofw_Tests_Kernel_Handler();
		$k->iCanHandle('authorize', $stub);

		$param1 = (object)array('name'=>'steve');
		$k->container->didef('param1', $param1);

		$k->runLifecycle('authorize');
		$this->assertEquals( 1, $stub->called );
		$this->assertSame( $param1, $stub->param );
	}

	public function test_has_handlers() {
		$k = $this->kernel;
		$stub = new Metrofw_Tests_Kernel_Handler();
		$k->iCanHandle('authorize', $stub);

		$param1 = (object)array('name'=>'steve');
		$k->iCanHandle('authenticate', $stub, 3);

		$this->assertTrue( $k->hasHandlers('authorize') );
		$this->assertTrue( $k->hasHandlers('authenticate') );
	}
}

class Metrofw_Tests_Kernel_Handler {

	public $called = 0;
	public $param  = NULL;

	public function authorize($param1=NULL) {
		$this->called++;
		$this->param = $param1;
	}
}
