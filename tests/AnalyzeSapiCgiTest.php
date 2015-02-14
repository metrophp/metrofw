<?php
include_once(dirname(__DIR__).'/kernel.php');
include_once(dirname(__DIR__).'/analyze_sapi_cgi.php');

class Metrofw_Tests_AnalyzeSapiCgi extends PHPUnit_Framework_TestCase { 

	public $kernel = NULL;

	public function setUp() {
		$this->analyzer   = new Metrofw_Analyze_sapi_cgi();
	}

	public function test_make_path_info_with_script() {
		$expected = '/dologin';
		$pathInfo = $this->analyzer->makePathInfo('/index.php', '/index.php/dologin');

		$this->assertEquals($expected, $pathInfo);
	}

	public function test_make_path_info_with_rewrite() {
		$expected = '/dologin';
		$pathInfo = $this->analyzer->makePathInfo('/index.php', '/dologin');

		$this->assertEquals($expected, $pathInfo);
	}

	public function test_make_path_info_with_basepath() {
		$expected = '/dologin';
		$pathInfo = $this->analyzer->makePathInfo('/folder/index.php', '/folder/dologin');

		$this->assertEquals($expected, $pathInfo);
	}

	public function test_make_path_info_with_basepath_and_script() {
		$expected = '/dologin';
		$pathInfo = $this->analyzer->makePathInfo('/folder/index.php', '/folder/index.php/dologin');

		$this->assertEquals($expected, $pathInfo);
	}
}
