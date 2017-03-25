<?php
include_once(dirname(__DIR__).'/template.php');
include_once(dirname(__DIR__).'/request.php');

class Metrofw_Tests_Template extends PHPUnit_Framework_TestCase { 

	public $kernel = NULL;

	public function setUp() {
		$this->template = new Metrofw_Template();
	}

	public function test_make_correct_section_template_include() {
		_set('template_name', 'tests/templates/');

		$sect = 'test.name';
		$response = new StdClass();
		$response->sectionList = ['foo'=>'bar<script>'];
		$result = $this->template->includeSectionTemplate($response, $sect);
		$this->assertTrue($result);
	}

	public function test_make_correct_main_file_guesses() {

		_set('template_name', 'tests/templates/');

		$sect = 'main';
		$response = \_makeNew('response');
		$request  = new Metrofw_Request();

		$response->sectionList = ['foo'=>'bar'];
		$result = $this->template->_guessFileChoices($request);
		$this->assertEquals(
		    ['src/main/views/main_main.html.php',
		    'local/main/views/main_main.html.php'
		    ], $result
		);
	}


	public function test_handle_main_file_override() {

		_set('template_name', 'webapp01');
		_set('template.main.file', 'x');

		$sect = 'main';
		$response = \_makeNew('response');
		$request  = new Metrofw_Request();

		$response->sectionList = ['foo'=>'bar<script>'];
		$result = $this->template->_guessFileChoices($request);
		$this->assertEquals(
		    ['src/main/views/x',
		    'local/main/views/x'
		    ], $result
		);
	}

	public function test_handle_shared_file_override() {

		_set('template_name', 'webapp01');
		_set('template.main.file', '/shared/form.html.php');

		$sect = 'main';
		$response = \_makeNew('response');
		$request  = new Metrofw_Request();

		$response->sectionList = ['foo'=>'bar'];
		$result = $this->template->_guessFileChoices($request);
		$this->assertEquals(
		    ['webapp01/shared/form.html.php'
		    ], $result
		);
	}

}
