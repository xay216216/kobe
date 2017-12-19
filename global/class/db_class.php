<?php

/******************************

	db_class父类
	

******************************/

class db_class {

	/**
	 * 
	 *	查询方法
	 * 
	 **/
	protected final static function select($db_name, $table_name, $sql, $data, $code = '', $message = '') {

		$read_suffix = '';
		$is_lock	 = TRUE;
		if (empty($sql['lock'])) {
			$read_suffix = '_read';
			$is_lock	 = FALSE;
		}

		//连接到数据库
		G::$MDB->conn($db_name . $read_suffix);
		
		//执行操作
		$exec_sql = G::$MDB->exec_sql(array(
			'type'		=>	'SELECT',
			'table'		=>	$table_name,
			'field'		=>	(empty($data['field'])) ? '' : $data['field'],
			'where'		=>	$data['where'],
			'order'		=>	(empty($data['order'])) ? '' : $data['order'],
			'limit'		=>	(empty($data['limit'])) ? '' : $data['limit'],
			'lock'		=>	$is_lock,
		));
		
		//执行情况判断
		if (!empty($code) && (empty($exec_sql) || !is_array($exec_sql))) {
			FUNC::re($code, '', $message);
		}
		
		//输出
		return $exec_sql;

	}

	/**
	 * 
	 *	更新方法
	 * 
	 **/
	protected final static function update($db_name, $table_name, $sql, $data, $code = '', $message = '') {

		//连接到数据库
		G::$MDB->conn($db_name);
		
		//执行操作
		$exec_sql = G::$MDB->exec_sql(array(
			'type'		=>	'UPDATE',
			'table'		=>	$table_name,
			'field'		=>	$data['field'],
			'where'		=>	$data['where'],
		));

		//执行情况判断
		if (empty($exec_sql)) {
			if (!empty($code)) {
				FUNC::re($code, '', $message);
			}
		} else {
			//数据更新同步
			self::_sync($table_name, $sql, $data, $exec_sql, '2');
			//数据缓存更新
			self::_clear($table_name, $sql);
		}

		//输出
		return $exec_sql;

	}

	/**
	 * 
	 *	写入方法
	 * 
	 **/
	protected final static function insert($db_name, $table_name, $sql, $data, $code = '', $message = '') {

		//连接到数据库
		G::$MDB->conn($db_name);
		
		//执行操作
		$exec_sql = G::$MDB->exec_sql(array(
			'type'		=>	'INSERT',
			'table'		=>	$table_name,
			'field'		=>	$data['field'],
		));

		//执行情况判断
		if (empty($exec_sql)) {
			if (!empty($code)) {
				FUNC::re($code, '', $message);
			}
		} else {
			//数据写入同步
			//$data['field']['id'] = $exec_sql; 自增长ID可能冲突，不用同步，允许自增长ID不一致。
			self::_sync($table_name, $sql, $data, '1', '1');
			//数据缓存更新
			self::_clear($table_name, $sql);
		}

		//输出
		return $exec_sql;

	}

	//内部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	数据同步操作
	 * 
	 **/
	private final static function _sync($table_name, $sql, $data, $num, $type) {

		return true;
	}

	/**
	 * 
	 *	写入同步数据
	 * 
	 **/
	private final static function _insert_sync($sync_type, $table_name, $sql, $data, $num, $type) {

		return true;
	}

	/**
	 * 
	 *	写入异步同步数据
	 * 
	 **/
	private final static function _insert_sync_data($table_name, $sql, $data, $num, $type) {
		
		return true;
	}

	/**
	 * 
	 *	数据缓存更新
	 * 
	 **/
	private final static function _clear($table_name, $sql) {
		
		return true;
	}

}

?>