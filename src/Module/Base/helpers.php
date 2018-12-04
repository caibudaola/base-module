<?php
if (! function_exists('utf8_urldecode')) {

    /**
     * 支持解码 %uxxxx 格式的url编码。
     *
     * @param $str
     * @return string
     */
	function utf8_urldecode($str) {
		$ret = urldecode($str);
		if (mb_check_encoding($ret, 'UTF-8')) {
			$str = $ret;
		}

		$ret = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", $str);
		$ret = html_entity_decode($ret, null, 'UTF-8');
		if (mb_check_encoding($ret, 'UTF-8')) {
			$str = $ret;
		}

		return $str;
	}
}
