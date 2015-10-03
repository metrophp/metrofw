<?php
include_once(dirname(__DIR__).'/kernel.php');

class Metrofw_Tests_Kernel extends PHPUnit_Framework_TestCase { 

	public $kernel = NULL;

	public function setUp() {
		$this->kernel = new Metrofw_Kernel(Metrodi_Container::getContainer());
	}

	public function test_empty_ctor_makes_di_container() {
		$k = new Metrofw_Kernel();
		$this->assertTrue( is_object($k->container) );
		$this->assertEquals('metrodi_container', strtolower(get_class($k->container)));
	}

	public function test_signal_params_are_mutable() {
		_iCanHandle('Fire', array($this, 'evtHandler'));
		_iCanHandle('Fire_post', array($this, 'evtPostHandler'));
		_iCanHandle('Fire_pre', array($this, 'evtPreHandler'));
		$x = 1;
		$y = 'a';
		$z = 'z';
		$args = array($x, $y);
		$result = Metrofw_Kernel::emit('Fire', $this, $args);
		$this->assertTrue($result);
		$this->assertEquals( $args[0], 2);
		$this->assertEquals( $args[1], 'z');
		$this->assertEquals( $args[2], 'q');
	}

	public function test_signal_default_params_are_request_response() {
		_iCanHandle('ArgumentTest', array($this, 'evtArgumentHandler'));
		$result = Metrofw_Kernel::emit('ArgumentTest', $this);
		$this->assertTrue($result);
	}

	public function evtHandler($evt, &$args) {
		$args[0] = 2;
		return TRUE;
	}

	public function evtPostHandler($evt, &$args) {
		$args[1] = 'z';
		return TRUE;
	}

	public function evtPreHandler($evt, &$args) {
		$args[2] = 'q';
		return TRUE;
	}

	public function evtArgumentHandler($evt, &$args) {
		//objects should be proto objs with $thing == 'request' and 'response'
		if (is_object($args['request'])) {
			if (is_object($args['response'])) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public function test_skip_bad_handlers() {
		_iCanHandle('testphase1', 'non/existant.php');
		_iCanHandle('testphase1', 'non/existant2.php', 3);
		$k = Metrofw_Kernel::getKernel();
		while ($svc = $k->whoCanHandle('testphase1')) {
			$this->assertFail(true);
		}
	}

	public function test_has_handlers_works() {
		$k = Metrofw_Kernel::getKernel();

		$this->assertFalse( $k->hasHandlers('testphase3') );
		$k->iCanHandle('testphase3', function() { return TRUE;}, 3);
		$this->assertTrue( $k->hasHandlers('testphase3') );

		$this->assertFalse( $k->hasHandlers('testphase4') );
		$k->iCanHandle('testphase4', function() { return TRUE;}, 1);
		$this->assertTrue( $k->hasHandlers('testphase4') );

//		$this->assertEquals( 1, $count );
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

	public function test_lifecycles_can_use_closures() {
		$output = '';
		$k = $this->kernel;

		$k->iCanHandle('closure_test', function() use(&$output) {
			$output .= 'inside closure';
		});

		$k->runLifecycle('closure_test');

		$this->assertEquals( 'inside closure', $output );
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

	public function test_capitalize_dotted_lifecycles() {
		$k = $this->kernel;
		$stub = new Metrofw_Tests_Kernel_Handler();
		$k->iCanHandle('camel.case.func', $stub);

		$k->runLifecycle('camel.case.func');
		$this->assertEquals( 1, $stub->called );
	}
}

class Metrofw_Tests_Kernel_Handler {

	public $called = 0;
	public $param  = NULL;

	public function authorize($param1=NULL) {
		$this->called++;
		$this->param = $param1;
	}

	public function camelCaseFunc() {
		$this->called++;
	}
}
