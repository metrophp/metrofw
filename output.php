<?php

class Metrofw_Output {

	public function resources($request) {
		if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gz')!==FALSE) {
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}
	}

	/**
	 * Set the HTTP status first, in case output buffering is not on.
	 *
	 * save sparkmsg to session if redirecting
	 * load sparkmsg from session if not redirecting
	 */
	public function output($request, $response, $session) {

		$this->statusHeader($response);
		if (isset($response->redir)) {
			$msg  = $response->get('sparkMsg');
			$session->set('sparkMsg', $msg);

			$this->redir($response);
			return;
		}


		$msg  = $session->get('sparkMsg');
		if (!empty($msg)) {
			foreach ($msg as $_m) {
				$response->addTo('sparkMsg', $_m);
			}
			$session->clear('sparkMsg');
		}

		if ($request->isAjax) {
			_clearHandlers('output');
			//sometimes ajax wants HTML, sometimes it doesn't
			if (strpos($_SERVER['HTTP_ACCEPT'], 'html')===FALSE
			    || $request->cleanString('format') == 'json') {

				header('Content-type: application/json');
				echo json_encode($response->sectionList);
				//stop HTML output
			}
			//enable partial HTML rendering
			//TODO: remove hardcoded dependency?
			Metrofw_Template::parseSection('main');
		}
	}

	public function noop() {}

	/**
	 * Redirect user
	 */
	public function redir($response) {
//		echo 'You will be redirected here: <a href="'.$request->redir.'">'.$request->redir.'</a>';
		header('Location: '.$response->redir);
	}

	/**
	 * Set the HTTP status header again if output buffering is on
	 */
	public function hangup($request) {
		$this->statusHeader($request);
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
			//cannot send any http body with 301, even gz header
			ob_end_clean();
			header('HTTP/1.1 301 Not Modified');
			break;


			default:
			header('HTTP/1.1 '.$response->get('statusCode'));
			break;
		}
	}
}
