<?php

define('ROOTDIR', dirname(dirname(__DIR__)));

require_once ROOTDIR . '/vendor/autoload.php';

use d7sd6u\VKPostsExtractorParser\Extractor as Parser;

use DiDom\Document;

if(!isset($argv[1])) {
	die("Please, provide source id\n");
}
$source = $argv[1];

$getDoms = function($urls, $context) use ($source) {
	$doms = array();

	$handles = array();

	$multiHandle = curl_multi_init();

	foreach($urls as $url) {
		$handle = curl_init($url);

		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($handle, CURLOPT_HTTPHEADER, array('Accept-language: en'));

		curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:72.0) Gecko/20100101 Firefox/72.0');
		curl_setopt($handle, CURLOPT_ENCODING, '');
		curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

		$handles[$url] = $handle;
		curl_multi_add_handle($multiHandle, $handle);
	}

	do {
		$status = curl_multi_exec($multiHandle, $active);
		if ($active) {
			curl_multi_select($multiHandle);
		}
	} while ($active && $status == CURLM_OK);

	foreach($handles as $url => $handle) {
		$str = curl_multi_getcontent($handle);

		$encodedUrl = urlencode($url);

		if(!file_exists(__DIR__ . "/pages/$source")) {
			mkdir(__DIR__ . "/pages/$source");
		}

		file_put_contents(__DIR__ . "/pages/$source/$encodedUrl", $str);

		echo "Saved $url\n";

		$str = iconv('windows-1251', 'utf-8//ignore', $str);
		try {
			$dom = new Document($str);
			$doms[$url] = $dom;
		} catch(\Exception $e) {
			$doms[$url] = null;
		}

		curl_multi_remove_handle($multiHandle, $handle);
	}

	curl_multi_close($multiHandle);

	return $doms;
};

$log = function($message) {
	echo "$message[text]\n";
};

date_default_timezone_set('UTC');

$parser = new Parser($getDoms, $log);

try {
	$posts = $parser->getPostsFromSource($source);
} catch(\Exception $e) {
	die('Parsing gone horribly wrong: ' . $e->getMessage() . "\n");
}

foreach($posts as $post) {
	$json = json_encode($post);
	if(!file_exists(__DIR__ . "/posts/$source")) {
		mkdir(__DIR__ . "/posts/$source");
	}
	file_put_contents(__DIR__ . "/posts/$source/$post[id]", $json);
}

echo "Generated posts from $source\n";