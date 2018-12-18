<?php

/**
 * 检测是否白名单（目前仅仅保证需要内网IP ）
 */
function checkInternalNetwork() {
	$ip = getClientIp();
	if ( ! isInternalNetwork($ip) ) {
		return false;
	}

	return true;
}

/**
 * 判断是否是内网IP
 */
function isInternalNetwork($ip) {
	// 将IP转整数。
	$long_ip = ip2long($ip);

	// 环回
	if ($long_ip >= ip2long('127.0.0.0') && $long_ip <= ip2long('127.255.255.255')) {
		return true;
	}

	// A类
	if ($long_ip >= ip2long('10.0.0.0') && $long_ip <= ip2long('10.255.255.255')) {
		return true;
	}

	// B类
	if ($long_ip >= ip2long('172.16.0.0') && $long_ip <= ip2long('172.31.255.255')) {
		return true;
	}

	// C类
	if ($long_ip >= ip2long('192.168.0.0') && $long_ip <= ip2long('192.168.255.255')) {
		return true;
	}

	return false;
}

