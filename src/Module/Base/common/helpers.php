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


if ( ! function_exists( 'get_client_ip' ) ) {
    /**
     * 获取ip地址
     */
    function get_client_ip(){
        if(isset($_SERVER['HTTP_REMOTEIP']) && !empty($_SERVER['HTTP_REMOTEIP'])){
            return $_SERVER['HTTP_REMOTEIP'];
        }else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if(count($ips) > 1){
                return $ips[1];
            }
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif ( isset($_SERVER['SERVER_ADDR']) ) {

            return $_SERVER['SERVER_ADDR'];
        } else {

            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

if ( ! function_exists( 'getClientIp' ) ) {
    /**
     * 获取ip地址
     */
    function getClientIp(){
        if(isset($_SERVER['HTTP_REMOTEIP']) && !empty($_SERVER['HTTP_REMOTEIP'])){
            return $_SERVER['HTTP_REMOTEIP'];
        }else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if(count($ips) > 1){
                return $ips[1];
            }
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif ( isset($_SERVER['REMOTE_ADDR']) ) {

            return $_SERVER['REMOTE_ADDR'];
        } else {

            return $_SERVER['SERVER_ADDR'];
        }
    }
}

// php 判断某个值在二维数组里
if ( ! function_exists( 'deep_in_array' ) ) {
    function deep_in_array($value, $array) {
        foreach($array as $item) {
            if(!is_array($item)) {
                if ($item == $value) {
                    return true;
                } else {
                    continue;
                }
            }

            if(in_array($value, $item)) {
                return true;
            } else if(deep_in_array($value, $item)) {
                return true;
            }
        }
        return false;
    }
}
