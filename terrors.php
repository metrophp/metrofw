<?php

class Metrofw_Terrors {

	public function output($request) {

		$request->set('statusCode', '500');
		$errors = _get('output_errors');
		if (!is_array($errors)) {
			return;
		}
		echo "<ul>\n";
		foreach ($errors as $_er) {
			echo "<li>\n";
			echo $_er;
			echo "</li>\n";
		}
		echo "</ul>\n";
	}

	public function template($request, $template_section) {

		$request->statusCode = '500';
		$errors = _get('output_errors');
		if (!is_array($errors)) {
			return;
		}
		echo "<ul>\n";
		foreach ($errors as $_er) {
			echo "<li>\n";
			echo $_er;
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
}
