<?php

use KubAT\PhpSimple\HtmlDomParser;

$getDoms = function($urls, $context) {
	$doms = array();

	foreach($urls as $url) {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-language: en'));

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:72.0) Gecko/20100101 Firefox/72.0');
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

		$str = curl_exec($ch);

		$doms[$url] = HtmlDomParser::str_get_html($str);
	}

	return $doms;
};