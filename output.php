<?php

class Metrofw_Output {

	public function resources($request) {
		if (strpos($request->header('HTTP_ACCEPT_ENCODING'), 'gz')!==FALSE) {
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}
	}

	/**
	 * If $response->redir is set, then reidrect to set location and return.
	 * Next set the status header.
	 * if the request is Ajax, don't call Template, just json_encode $response->sectionList
	 *
	 * save sparkmsg to session if redirecting
	 * load sparkmsg from session if not redirecting
	 */
	public function output($request, $response, $session) {

		if (isset($response->redir)) {
			$this->redir($response);
			return;
		}
		$this->statusHeader($response);

		//only clear session messages on a full page display
		if (!$request->isAjax) {
			$msg  = $session->get('sparkmsg');

			if (!empty($msg)) {
				foreach ($msg as $_m) {
					$response->addTo('sparkmsg', $_m);
				}
				$session->clear('sparkmsg');
			}
		}

		if ($request->isAjax) {
			_clearHandlers('output');
			//sometimes ajax wants HTML, sometimes it doesn't
			if (strpos($_SERVER['HTTP_ACCEPT'], 'html')===FALSE
			    || $request->cleanString('format') == 'json') {

				header('Content-type: application/json');
				echo json_encode($response->sectionList);
				//stop HTML output
			} else {
				//enable partial HTML rendering
				//TODO: remove hardcoded dependency?
				Metrofw_Template::parseSection('main');
			}
		}
	}

	public function noop() {}

	/**
	 * Redirect user
	 */
	public function redir($response) {
//		echo 'You will be redirected here: <a href="'.$request->redir.'">'.$request->redir.'</a>';
		header('Location: '.$response->redir);
		$response->statusCode  = 302;
		$this->statusHeader($response);
	}

	/**
	 * Set the HTTP status header again if output buffering is on
	 * Save any spark messages not unset by plugins
	 */
	public function hangup($response, $session) {
		$this->statusHeader($response);

		$msg  = $response->get('sparkmsg');
		if (is_array($msg) && count($msg)) {
			$sessionMsg  = (array)$session->get('sparkmsg');
			$msg = array_merge($msg, $sessionMsg);
			$session->set('sparkmsg', $msg);
		}
	}

	public function statusHeader($response) {

		//if no statusCode, set to 200
		$code = $response->statusCode;

		if (empty($code)) {
			$response->statusCode = 200;
		}
		switch ($response->statusCode) {

			case 400:
			header('HTTP/1.1 400 Bad Request');
			break;

			case 401:
			header('HTTP/1.1 401 Unauthorized');
			break;

			case 403:
			header('HTTP/1.1 403 Forbidden');
			break;

			case 404:
			header('HTTP/1.1 404 File Not Found');
			break;

			case 500:
			case 501:
			header('HTTP/1.1 501 Server Error');
			break;

			case 200:
			header('HTTP/1.1 200 OK');
			break;

			case 301:
			header('HTTP/1.1 301 Moved Permanently');
			break;

			case 302:
			header('HTTP/1.1 302 Moved Temporarily');
			break;

			case 304:
			//cannot send any http body with 301, even gz header
			ob_end_clean();
			header('HTTP/1.1 304 Not Modified');
			break;


			default:
			header('HTTP/1.1 '.$response->get('statusCode'));
			break;
		}
	}
}
