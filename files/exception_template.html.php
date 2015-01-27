<div class="code" style="background-color:#f3f3ee;padding:1em 1.4em 1em 1.4em ;margin:1em;">
<?php

echo '<h3>'.$exception->getMessage().'</h3>';
echo '<pre>';
//printf (' %s: %s <br/>', $exception->getLine(),  $exception->getFile());
//echo '<br/>';
$lines = array_slice( file( $exception->getFile()), $exception->getLine()- 10, 20);

$start = $exception->getLine()-10;
foreach ($lines as $k=>$v) {
	$start++;
	if ($start == $exception->getLine()) {
		echo '<span style="background-color:#f39999;">';
	}
	echo $start.': '. htmlspecialchars($v);

	if ($start == $exception->getLine()) {
		echo '</span>';
	}
}
?>
</pre>
<h5>Trace</h5>
<pre>
<?php
foreach ($exception->getTrace() as $trace) {
//TODO: don't print traces that don't have line/file
printf (' %s %s %s '.PHP_EOL, $trace['class'], $trace['type'], $trace['function']);
printf (' %s: %s '.PHP_EOL, @$trace['line'], @$trace['file']);
echo PHP_EOL;
}
?>
</pre>
</div>
<?


