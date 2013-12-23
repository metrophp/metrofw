<?php

class Metrofw_Output {

	/**
	 * Set the HTTP status first, in case output buffering is not on.
	 *
	 * save sparkmsg to session if redirecting
	 * load sparkmsg from session if not redirecting
	 */
	public function output($req, $res) {
		$this->statusHeader($res);
		$sess = _getMeA('session');
		if (isset($res->redir)) {
			$msg  = $res->get('sparkMsg');
			$sess->set('sparkMsg', $msg);

			$this->redir($res);
			return;
		}


		$msg  = $sess->get('sparkMsg');
		if (!empty($msg)) {
			foreach ($msg as $_m) {
				$res->addTo('sparkMsg', $_m);
			}
			$sess->clear('sparkMsg');
		}

		if ($req->isAjax) {
			header('Content-type: application/json');
			echo json_encode($res->sectionList);
		} else {
			associate_iCanHandle('output', 'metrofw/template.php');
		}
	}

	/**
	 * Redirect user
	 */
	public function redir($res) {
//		echo 'You will be redirected here: <a href="'.$request->redir.'">'.$request->redir.'</a>';
		header('Location: '.$res->redir);
	}

	/**
	 * Set the HTTP status header again if output buffering is on
	 */
	public function hangup($req) {
		$this->statusHeader($req);
	}

	public function statusHeader($res) {

		//if no statusCode, set to 200
		$code = $res->get('statusCode');
		if (empty($code)) {
			$res->set('statusCode', 200);
		}
		switch ($res->get('statusCode')) {

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

			default:
			header('HTTP/1.1 '.$res->get('statusCode'));
			break;
		}
	}
}
