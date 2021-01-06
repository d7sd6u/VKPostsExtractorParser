<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once 'getDomsCurlMulti.php';

use d7sd6u\VKPostsExtractorParser\Extractor as Parser;

$logs = array();

$log = function($message) use (&$logs)  {
	$logs[] = $message;
};

$parser = new Parser($getDoms, $log);

$posts = $parser->getPostsFromSource('habr');

foreach($logs as $message) {
	echo $message;
	echo '<br/>';
}
echo '<hr/>';

echo '<pre>';
print_r($posts);
echo '</pre>';