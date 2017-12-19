<?php

/******************************

	服务器敏感配置库

******************************/

class CONF {
	
	/**
	 * 
	 *	mysql数据库连接参数
	 * 
	 **/
	public static function mysql_conn($key) {

		$data = array(

			//写主库
			'c_kobe'		=>	array(
					'host'		=>	'127.0.0.1',
					'port'		=>	'3306',
					'dbname'	=>	'shuangseqiu',
					'user'		=>	'root',
					'password'	=>	'123456',
			),
			//读辅库
			'c_kobe_read'	=>	array(
					'host'		=>	'127.0.0.1',
					'port'		=>	'3306',
					'dbname'	=>	'shuangseqiu',
					'user'		=>	'root_read',
					'password'	=>	'123456',
			),

			
		);

		return (array_key_exists($key, $data)) ? $data[$key] : array();
		
	}

	/**
	 * 
	 *	redis数据库连接参数
	 * 
	 **/
	public static function redis_conn($key) {

		$data = array(
			
			//默认返回值
			'default'		=>	array(
				'host'			=> '',
				'port'			=> 0
			),
			//卡包系统库-写主库
			'kb_system'		=>	array(
				'host'			=> 'localhost',
				'port'			=> 6379
			),
		);

		return (array_key_exists($key, $data)) ? $data[$key] : $data['default'];
		
	}

	/**
	 * 
	 *	SOCKET服务连接参数
	 * 
	 **/
	public static function socket_conn($key) {

		$data = array(
			
			//默认返回值
			'default'		=>	array(
				'host'			=>	'127.0.0.1',	//IP
				'port'			=>	'8011',			//端口
			),
			//kobe专属
			'kobe后台'		=>	array(
				'host'			=>	'127.0.0.2',	//IP
				'port'			=>	'8011',			//端口
			),
		);

		return (array_key_exists($key, $data)) ? $data[$key] : $data['default'];
		
	}

	

}

?>