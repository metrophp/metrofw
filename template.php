<?php
class Metrofw_Template {

	public $scriptList    = array();
	public $styleList     = array();
	public $extraJs       = array();
	public $charset       = 'UTF-8';
	public $headTagList   = array();
	public $baseDir       = '';
	public $baseUri       = '';


	/**
	 * This function handles redirects if 
	 * $request->redir is set
	 *
	 * $this function also sets DI flags:
	 *
	 * 'template_name' to 'webapp01' if it is not set,
	 *
	 * 'template_basedir' to 'local/templates/' if it is not set,
	 *
	 * 'template_baseuri' to 'local/templates/' if it is not set,
	 *
	 * This function handles template section "template.main"
	 * if no other handler is installed for that section.
	 * If there is no $request->output, it includes 
	 * the DI flag "template.main.file"
	 * if there is no file, it defaults to 
	 * templates/$appName/template.mainmain.html.php 
	 *
	 * If $request->output is set, and 
	 * if it is a string it is returned,
	 * if it is an object toHtml is called, if available,
	 * else, toString is called, if available, 
	 * else, __toString is called, if available.
	 *  
	 * The output is returned.
	 */
	public function output($request, $response, $user) {
		$layout = _get('template_layout', 'index');

		$templateName = _get('template_name', 'webapp01');
		_set('template_name', $templateName);
		$this->baseDir  = _get('template_basedir', 'local/templates/');
		$this->baseUrl  = _get('template_baseuri', 'local/templates/');


		_set('baseuri', $request->baseUri);

		$templateName = _get('template_name');
		$this->parseTemplate($request, $response, $templateName, $user, $layout);
	}

	public function parseTemplate($request, $response, $templateName, $user=NULL, $layout = 'index') {

		$templateIncluded = FALSE;
		if ($layout == '') {
			$layout = 'index';
		}

		//try special style, if not fall back to index
		if (!include( $this->baseDir. $templateName.'/'.$layout.'.html.php') ) {
			if(include($this->baseDir. $templateName.'/index.html.php')) {
				$templateIncluded = TRUE;
			}
		} else {
			$templateIncluded = TRUE;
		}

		if (!$templateIncluded) {
			$errors = array();
			$errors[] = 'Cannot include template '.$templateName.'.';
			$request->httpStatus = '501';
			_set('output_errors', $errors);
			_iCanHandle('output', 'metrofw/terrors.php');
			return true;
		}
	}

	/**
	 * If response->$section exists, print it
	 * else, see if there is a template file.
	 */
	//public function template($signal, &$args) {
	public function template($request, $response, $template_section) {
/*
		$sect     = str_replace('template.', '', $args['template_section']);
		$response = $args['response'];
		$request  = $args['request'];
*/
		$sect     = str_replace('template.', '', $template_section);

		if ($response->has($sect)) {
			$content = $response->get($sect);
			if (is_array($content)) {
				$html = '';
				foreach ($content as $c) {
					$html .= $this->transformContent($c);
				}
				echo $html;
				return;
			} else {
				echo $this->transformContent($content);
				return;
			}
		}
		//we don't have a section in the response
		//let's include the template.main.file if the section is "main".
		if ($sect != 'main') return;
		$filesep = '/';
		//$subsection = substr($args['template_section'], strpos($args['template_section'], '.')+1);
		$subsection = substr($template_section, strpos($template_section, '.')+1);
		$viewFile = _get('template.main.file', $request->modName.'_'.$request->actName).'.html.php';
		$fileChoices = array();
		$fileChoices[] = $this->baseDir.'view'.$filesep.$viewFile;
		$fileChoices[] = 'local'.
				$filesep.$request->appName. $filesep . 'views'. $filesep . $viewFile;
		$fileChoices[] = 'src'  .
				$filesep.$request->appName. $filesep . 'views'. $filesep . $viewFile;

		ob_start();
		$success = FALSE;
		foreach ($fileChoices as $_f) {
			if (@include($_f)) {
				$success = TRUE;
				break;
			}
		}

		if (!$success) {
			$errors = array();
			$errors = _get('output_errors', $errors);
			$errors[] = 'Cannot include view file. ' . $viewFile;
			_set('output_errors', $errors);
			_iCanHandle('template.main', 'metrofw/terrors.php');
		}
		echo ob_get_contents() . substr( ob_end_clean(), 0, 0);
		//$args['output'] .= ob_get_contents() . substr( ob_end_clean(), 0, 0);

		/*
		//we have some special output,
		// could be text, could be object
		if (!is_object($request->output))
			return $request->output;

		//it's an object
		if (method_exists( $request->output, 'toHtml' )) {
			return call_user_func_array(array($request->output, 'toHtml'), array($request));
		}

		if (method_exists( $request->output, 'toString' )) {
			return call_user_func_array(array($request->output, 'toString'), array($request));
		}

		if (method_exists( $request->output, '__toString' )) {
			return call_user_func_array(array($request->output, '__toString'), array($request));
		}
		 */
	}

	public function transformContent($content) {
		//we have some special output,
		// could be text, could be object
		if (!is_object($content))
			return $content;

		//it's an object
		if (method_exists( $content, 'toHtml' )) {
			return call_user_func_array(array($content, 'toHtml'), array($request));
		}

		if (method_exists( $content, 'toString' )) {
			return call_user_func_array(array($content, 'toString'), array($request));
		}

		if (method_exists( $content, '__toString' )) {
			return call_user_func_array(array($content, '__toString'), array($request));
		}
	}

	/**
	 * Ask for who can handle the given section
	 */
	static public function parseSection($template_section) {
		$kernel                   = Metrofw_Kernel::getKernel();
		$container                = $kernel->container;
		$args['request']          = $container->make('request');
		$args['response']         = $container->make('response');
		$args['output']           = '';
		$args['template_section'] = $template_section;

		//if nobody has a handle on this section, we'll handle it
		if (! $kernel->hasHandlers('template.'.$template_section)) {
			_connect('template.'.$template_section, 'metrofw/template.php::template');
		}
		_set('template_section', $template_section);
		$kernel->runLifecycle('template.'.$template_section);
		return $args['output'];
	}
}

function sitename() {
	return _get('sitename', 'Metro');
}

/**
 */
function m_turl($https=-1) {
	static $baseUri;
	static $templateName;
	static $templatePath;

	if (!$baseUri) {
		$baseUri = _get('baseuri');
	}
	if (!$templatePath) {
		$templatePath = _get('template_baseuri');
	}
	if (!$templateName) {
		$templateName = _get('template_name');
	}
	$end = $baseUri.$templatePath.$templateName.'/';

	if ($https === 0) {
		return 'http://'.$end;
	} else if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $https>0) {
		return 'https://'.$end;
	} else {
		return 'http://'.$end;
	}
}


