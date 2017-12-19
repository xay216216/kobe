<?php

/**
 * php houtai入口文件
 * User: xay
 * Date: 2017/12/19
 * Time: 下午14:30
 */

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/..');   // 项目根路径
define('PATH_404', '/404.html');	     				 //404页面
define('APP_PATH', $_SERVER['DOCUMENT_ROOT'] . '/../admin');//后台

try {
		
	require_once BASEPATH.'/global/lib/route.php';
	
	ROUTE::go();
	
} catch (Exception $e) {
	
	header("location: " . PATH_404);
	
}

?>