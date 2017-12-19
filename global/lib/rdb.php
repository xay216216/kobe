<?php

/******************************

	redis 操作类
	
	【使用方法】

		base_controller文件:	
			G::$RDB = new RDB();

		调用的文件中：
			G::$RDB->conn('kb_system');			//指定要使用的数据库连接参数
			G::$RDB->exec_sql($sql_array);

******************************/

class RDB {

	private $conn_pool 		= array();		//数据库连接池
	private $conn_name 		= '';			//当前连接的数据库名称
	private $conn_type		= '';			//当前请求的类型
	private $cache_time     = 86400;        //默认缓存时间

	private $type_array 	= array(		//支持的操作请求类型：Y支持，否则不支持
		'SELECT'	=>	'Y', 
		'INSERT'	=>	'Y', 
		'ISKEY'		=>	'Y', 
		'DELETE'	=>	'Y', 
	);

	/**
	 * 
	 *	创建数据库连接
	 *	连接存在，则不创建
	 * 
	 **/
    public final function conn($db) {
		$this->conn_name = $db;	

		//不存在，则创建连接
		if (empty($this->conn_pool[$this->conn_name]['conn'])) {

			FUNC::re('20', $this->conn_name, '执行缓存数据库连接...');
			$conn_info = CONF::redis_conn($this->conn_name);
			$this->conn_pool[$this->conn_name]['conn'] = new Redis();
			$this->conn_pool[$this->conn_name]['conn']->connect($conn_info['host'], $conn_info['port']);
		}
		
		FUNC::re('20', $this->conn_name, '缓存数据库连接成功！');
    }
	
	/**
	 * 
	 *	执行数据库操作
     *   $sql_array = array(
	 *		'type'		=> 'SELECT/INSERT/ISKEY/DELETE',
     *      'key'		=> 'str',
	 *      'value'		=> 'str',
     *		'cache'		=> 86400
     *   );
	 * 
	 **/
    public final function exec_sql($sql_array){
	
		//type合法性校验
		if (empty($sql_array['type']) || empty($this->type_array[$sql_array['type']]) || $this->type_array[$sql_array['type']] != 'Y') {
			FUNC::re('42', $sql_array, 'type合法性校验失败！');
		}

		//key合法性校验
		if (empty($sql_array['key'])) { 
			FUNC::re('42', $sql_array, 'key合法性校验失败！');
		}

        //执行数据库操作
		$result = '';
        switch($sql_array['type']){
            case 'SELECT':
                $result = $this->conn_pool[$this->conn_name]['conn']->GET($sql_array['key']);													break;
            case 'INSERT':
				if (empty($sql_array['value'])) $sql_array['value'] = '';						//value合法性校验
				if (empty($sql_array['cache'])) $sql_array['cache'] = $this->cache_time;		//过期时间校验
                $result = $this->conn_pool[$this->conn_name]['conn']->SETEX($sql_array['key'], $sql_array['cache'], $sql_array['value']);		break;
            case 'ISKEY':
                $result = $this->conn_pool[$this->conn_name]['conn']->EXISTS($sql_array['key']);													break;
            case 'DELETE':
                $result = $this->conn_pool[$this->conn_name]['conn']->DEL($sql_array['key']);													break;
			default:
				FUNC::re('42', $sql_array['type'], '未知的操作类型！');																			break;
        }
		
		FUNC::re('20', array($sql_array['key'], $result), '缓存数据库' . $sql_array['type'] . '操作执行成功！');

		return $result;
    }

}

?>