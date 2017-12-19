<?php

/**
 * php  全局配置文件
 */

class G {

	public static $TIME;						//当前服务器时间
	public static $SYS;							//当前系统名称

	public static $MDB;							//MYSQL数据库对象
	public static $RDB;							//REDIS数据库对象
	
	public static $URL			= array();		//URL规则容器
	public static $POST			= array();		//外部变量容器
	public static $GET			= array();		//外部变量容器
	public static $SESSION		= array();		//用户SESSION
	public static $SQL			= array();		//数据库通用参数容器
	public static $PATH			= array();		//环境部署路径
	public static $INFO			= array();		//运行信息容器
	public static $ERROR		= array();		//错误捕获容器
	public static $CLEAR		= array();		//CACHE清除容器
	
	public static $CONFIG		= array();		//基础配置
	public static $MESSAGE		= array();		//输出信息
	public static $FORMAT		= array();		//外部变量校验规则

}

//当前系统时间
G::$TIME = time();

//请求来源解析
G::$URL	= array(
	'domain'				=>	'',
	'host'					=>	'',
	'm'						=>	'',
	'f'						=>	'',
	'n'						=>	'',
);

//环境部署变量
G::$PATH	= array(
	'log'					=>	BASEPATH . '/log/php_log',				//部署PHPLOG路径
);

//全局配置池
G::$CONFIG	= array(
	'log_type'				=>	'1',									//LOG记录方式：1对错都投放LOG 2错误才投放LOG
	'out_type'				=>	FALSE,									//OUT标识，避免出现循环调用
	'cookie_time'			=>	86400 * 365,							//登录COOKIE有效期
	'sync_cache_time'		=>	60,										//同步状态缓存有效期
	'pass_error'			=>	'10',									//登录密码每天错误次数上限	
);

//全局信息池
G::$MESSAGE	= array(
	//全局通用提示信息
	'0'						=>	'未知错误！',
	'20'					=>	'操作成功！',
	'40'					=>	'用户权限校验失败！',
	'41'					=>	'数据校验失败！',
	'42'					=>	'数据库操作失败！',
	'43'					=>	'逻辑校验失败！',
	'44'					=>	'请求的资源不存在！',
	
	//01：用户相关
	'400101'				=>	'您需要登录后才能访问！',
	'410101'				=>	'您输入的手机号校验失败！',
	'410102'				=>	'请输入6位数字验证码！',
);

//全局过滤规则池
G::$FORMAT	= array(
	'page'					=>	'/^[\d]{1,3}$/',
	'a_user_id'				=>	'/^au[0-9a-z]{10}$/',		
	'user_mobile'			=>	'/^[\d]{11}$/',
	'user_pass'				=>	'/^[0-9a-zA-Z]{6,16}$/',
	'sms_code'				=>	'/^[\d]{4}$/',
	'qihao'					=>	'/^[\d]{7}$/',
);

?>