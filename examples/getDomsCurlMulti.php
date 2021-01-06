<?php

use KubAT\PhpSimple\HtmlDomParser;

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


		$dom = HtmlDomParser::str_get_html($str);
		if($dom instanceof simple_html_dom\simple_html_dom) {
			$doms[$url] = $dom;
		} else {
			$doms[$url] = null;
		}


		curl_multi_remove_handle($multiHandle, $handle);
	}

	curl_multi_close($multiHandle);

	return $doms;
};