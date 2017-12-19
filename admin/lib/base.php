<?php

/**
 * php 　后台底层文件
 */

//设置系统时区
date_default_timezone_set('Asia/Shanghai');

//强制浏览器不缓存
header ("Cache-Control: no-cache, must-revalidate");  
header ("Pragma: no-cache");
header ("Content-Type: application/json;charset=utf-8");

//载入底层支持包
include_once(BASEPATH . '/global/config/conf_server.php');
include_once(BASEPATH . '/global/lib/func.php');
include_once(BASEPATH . '/global/lib/mdb.php');
include_once(BASEPATH . '/global/lib/rdb.php');

include_once(BASEPATH . '/global/class/db_class.php');

include_once(APP_PATH . '/lib/method.php');

//初始化系统名称
G::$SYS = 'ADMIN';

//初始化数据库操作类
G::$MDB = new MDB();

//初始化Redis操作类
G::$RDB = new RDB();

//数据库通用参数容器
G::$SQL = array(
	'mysql_name'	=>	'c_kobe',
);

//初始化SESSION
session_start();

//请求合法性判断，并初始化运行环境变量
M::check_postdata_cid();

?>