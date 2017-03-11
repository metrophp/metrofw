<?php
include_once(dirname(__DIR__).'/kernel.php');
include_once(dirname(__DIR__).'/router.php');
include_once(dirname(__DIR__).'/request.php');

class Metrofw_Tests_Router extends PHPUnit_Framework_TestCase { 

	public $kernel = NULL;

	public function setUp() {
		$this->router    = new Metrofw_Router();
		$this->request   = new Metrofw_Request();
	}

	public function test_default_routing_uses_main_as_modName() {
		$this->request->requestedUrl = '/app/func/';
		$this->router->autoRoute($this->request);
		$this->assertEquals(
			'app',
			$this->request->appName 
		);
		$this->assertEquals(
			'main',
			$this->request->modName 
		);
		$this->assertEquals(
			'func',
			$this->request->actName 
		);
	}

	public function test_deafult_routing_uses_action_override() {
		$this->request->requestedUrl = '/app/func/';
		$this->request->vars['action'] = 'override';
		$this->router->autoRoute($this->request);
		$this->assertEquals(
			'app',
			$this->request->appName 
		);
		$this->assertEquals(
			'main',
			$this->request->modName 
		);
		$this->assertEquals(
			'override',
			$this->request->actName 
		);
	}

	public function test_route_rules() {

		$this->container = new Metrodi_Container();
		$this->kernel    = new Metrofw_Kernel($this->container);


		$this->container->set('route_rules', array());

		/*
		$this->container->set('route_rules', 
			array_merge(array('/:appName'=>array( 'modName'=>'main', 'actName'=>'main' )),
			_get('route_rules')));

		$this->container->set('route_rules', 
			array_merge(array('/:appName/:modName'=>array( 'actName'=>'main' )),
			_get('route_rules')));

		$this->container->set('route_rules', 
			array_merge(array('/:appName/:modName/:actName'=>array(  )),
			_get('route_rules')));
		 */

		$this->container->set('route_rules', 
			array_merge(array('/:appName/:modName/:actName/:arg'=>array( 'appName'=>'main', 'modName'=>'main', 'actName'=>'main' )),
			_get('route_rules')));


		$this->request->requestedUrl = '/app/mod/func/';
		$this->router->autoRoute($this->request, $this->kernel, $this->container);

		$this->assertEquals(
			'app',
			$this->request->appName 
		);
		$this->assertEquals(
			'mod',
			$this->request->modName 
		);
		$this->assertEquals(
			'func',
			$this->request->actName 
		);

		$this->assertTrue(
			isset($this->kernel->serviceList['process']) 
		);
	}

	public function test_route_rules_allows_action_override() {

		$this->container = new Metrodi_Container();
		$this->kernel    = new Metrofw_Kernel($this->container);


		$this->container->set('route_rules', array());

		/*
		$this->container->set('route_rules', 
			array_merge(array('/:appName'=>array( 'modName'=>'main', 'actName'=>'main' )),
			_get('route_rules')));

		$this->container->set('route_rules', 
			array_merge(array('/:appName/:modName'=>array( 'actName'=>'main' )),
			_get('route_rules')));

		$this->container->set('route_rules', 
			array_merge(array('/:appName/:modName/:actName'=>array(  )),
			_get('route_rules')));

		 */
		$this->container->set('route_rules', 
			array_merge(array('/:appName/:modName/:actName/:arg'=>array( 'appName'=>'main', 'modName'=>'main', 'actName'=>'main' )),
			_get('route_rules')));


		$this->request->requestedUrl = '/app/mod/func/';
		$this->request->vars['action'] = 'override';
		$this->router->autoRoute($this->request, $this->kernel, $this->container);

		$this->assertEquals(
			'app',
			$this->request->appName 
		);
		$this->assertEquals(
			'mod',
			$this->request->modName 
		);
		$this->assertEquals(
			'override',
			$this->request->actName 
		);

		$this->assertTrue(
			isset($this->kernel->serviceList['process']) 
		);
	}

}
