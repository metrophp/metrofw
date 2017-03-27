<?php
class Metrofw_Template {

	public $scriptList    = array();
	public $styleList     = array();
	public $extraJs       = array();
	public $charset       = 'UTF-8';
	public $headTagList   = array();
	public $baseDir       = '';
	public $baseUri       = '';
	public $fileExt       = '.html.php';


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
	 * The output is echoed.
	 */
	public function output($request, $response, $user) {
		if (isset($response->redir)) {
			return;
		}
		$layout = _get('template_layout', 'index');

		$templateName = _get('template_name', 'webapp01');
		_set('template_name', $templateName);
		$this->baseDir  = _get('template_basedir', 'local/templates/');
		$this->baseUri  = _get('template_baseuri', 'local/templates/');
		$this->fileExt  = _get('template_fileext', $this->fileExt);

		_set('baseuri', $request->baseUri);

		$this->parseTemplate($request, $response, $templateName, $user, $layout);
	}

	/**
	 * Include $layout.".html.php" from $templateName folder.
	 * If $layout.".html.php" is not available, fallback to index.html.php
	 * If layout is not available throw an exception in development mode.
	 * Otherwise, try to include server_error.html.php.  If that fails, an
	 * empty 501 response will be returned, allowing for CGI intercept handling
	 * to occur.
	 */
	public function parseTemplate($request, $response, $templateName, $user=NULL, $layout = 'index') {

		$templateIncluded = FALSE;
		if ($layout == '') {
			$layout = 'index';
		}

		//try special style, if not fall back to index
		if (!@include( $this->baseDir. $templateName.'/'.$layout.$this->fileExt) ) {
			if(@include($this->baseDir. $templateName.'/index'.$this->fileExt)) {
				$templateIncluded = TRUE;
			}
		} else {
			$templateIncluded = TRUE;
		}

		if (!$templateIncluded) {
			$request->httpStatus = '501';
			if ($request->isDevelopment()) {
				throw new \Exception('Cannot include template '. $templateName.'/'.$layout.$this->fileExt);
			}
			$response->addTo('errors', 'Cannot include template '.$templateName.'.');
			@include($this->baseDir. $templateName.'/server_error'.$this->fileExt);
			return TRUE;
		}
	}

	/**
	 * If response->$section exists, print it
	 * else, see if there is a template file.
	 *
	 * This function looks for an array key in the $response object
	 * If an object is found this tries to call: toHtml, toString, __toString
	 * against the object.
	 *
	 * Otherwise, a file is included from one of the following locations:
	 *
	 * templates/webapp01/views/appName/modName_actName.html.php
	 * (template_basedir)/(template_name)/views/($appName)/($modName)_($actName).html.php
	 *
	 * src/($appName)/views/($modName)_($actName).html.php
	 * local/($appName)/views/($modName)_($actName).html.php
	 */
	public function template($request, $response, $template_section) {
		$sect     = str_replace('template.', '', $template_section);

		if ($response->has($sect)) {
			$content = $response->get($sect);
			if (is_array($content)) {
				$html = '';
				foreach ($content as $c) {
					$html .= $this->transformContent($c, $request);
				}
				echo $html;
				return;
			} else {
				echo $this->transformContent($content, $request);
				return;
			}
		}

		//we don't have a section in the response
		//let's try to include templates/{template_name}/section/name.html.php
		if ($sect != 'main') {
			$this->includeSectionTemplate($response, $sect);
			return;
		}

		//let's include the template.main.file if the section is "main".
		$fileChoices = $this->_guessFileChoices($request);

		extract($response->sectionList);

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
			$errors[] = 'Cannot include view file. ' . $_f;
			_set('output_errors', $errors);
		}
		echo ob_get_contents() . substr( ob_end_clean(), 0, 0);
		return $success;
	}

	public function _guessFileChoices($request) {
		$fileChoices = [];
		$filesep     = '/';
		$viewFile    = _get('template.main.file', '');
		//leading slash indicates template file
		if (substr($viewFile, 0, 1) ==  '/') {
			$fileChoices[] = $this->baseDir._get('template_name').$viewFile;
		} else {
			//no override file, try to make some automatic guesses
			if ($viewFile == '') {
				//try src/app/views/actName.html.php
				$viewFile = $request->modName.'_'.$request->actName.$this->fileExt;
			}

			$fileChoices[] = 'src'  .
				$filesep.$request->appName. $filesep . 'views'. $filesep . $viewFile;

			//try src/app/views/actName.html.php
			$fileChoices[] = 'local'.
				$filesep.$request->appName. $filesep . 'views'. $filesep . $viewFile;
		}
		return $fileChoices;
	}

	/**
	 * Try to include template_path/section/name.html.php
	 * where section/name comes from parseSection('template.section.name')
	 */
	public function includeSectionTemplate($response, $sect) {
		//try templates/section/name.html.php
		$fileChoices[] = $this->baseDir._get('template_name').str_replace('.', '/', $sect).$this->fileExt;
		ob_start();

		extract($response->sectionList);

		$success = FALSE;
		foreach ($fileChoices as $_f) {
			if (include($_f)) {
				$success = TRUE;
				break;
			}
		}

		if ($success) {
			echo ob_get_contents();
		}
		ob_end_clean();
		return $success;
	}

	public function transformContent($content, $request) {

		//struct
		if (is_array($content)) {
			return implode(' ', array_values($content));
		}

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
	}

	public function e($var) {
		return $this->escape($var);
	}

	public function escape($var) {
		if (is_array($var) || is_object($var)) {
			return;
		}
		return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
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
	static $request;

	if (!$baseUri) {
		$baseUri = _get('baseuri');
	}
	if (!$templatePath) {
		$templatePath = _get('template_baseuri');
	}
	if (!$templateName) {
		$templateName = _get('template_name');
	}
	if (!$request) {
		$request = _make('request');
	}
	$end = $baseUri.$templatePath.$templateName.'/';

	if ($https === 0) {
		return 'http://'.$end;
	} else if ($request->ssl || $https>0) {
		return 'https://'.$end;
	} else {
		return 'http://'.$end;
	}
}


