<?php

use DiDom\Document;

$getDoms = function($urls, $context) {
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

		if(!mb_detect_encoding($str, 'UTF-8', true)) {
			$str = iconv('windows-1251', 'utf-8', $str);
		}

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