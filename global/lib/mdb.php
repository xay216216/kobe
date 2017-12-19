<?php

/******************************

	mysql PDO操作类

	【使用方法】

		base_controller文件:	
			G::$MDB = new MDB();

		调用的文件中：
			G::$MDB->conn('');			//指定要使用的数据库连接参数
			G::$MDB->exec_sql($sql_array);

******************************/

class MDB{

	private $conn_pool 		= array();		//数据库连接池
	private $conn_name 		= '';			//当前连接的数据库名称
	private $conn_type		= '';			//当前请求的类型

	private $type_array 	= array(		//支持的操作请求类型：Y支持，T表操作方法支持，否则不支持
		'SELECT'	=>	'Y', 
		'INSERT'	=>	'Y', 
		'UPDATE'	=>	'Y', 
		'DELETE'	=>	'Y', 
		'CREATE'	=>	'T', 
		'RENAME'	=>	'T', 
		'DROP'		=>	'T', 
		'COPY'		=>	'T', 
	);

	//外部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	创建数据库连接
	 *	连接存在，则不创建
	 *	$transaction 事务开关
	 * 
	 **/
	public final function conn($db, $transaction = FALSE) {
		$this->conn_name = $db;		
		
		//不存在，则创建连接
		// $this->conn_pool[$this->conn_name]['conn']->getAttribute(PDO::ATTR_SERVER_INFO) == 'MySQL server has gone away'  连接中断
		// 如果改成这种情况关闭连接，执行重连，需要安排测试一下事务状态才行。
		if (empty($this->conn_pool[$this->conn_name]['conn'])) {

			//创建数据库连接
			try{
				FUNC::re('20', $this->conn_name, '执行数据库连接...');
				$conn_info = CONF::mysql_conn($this->conn_name);
				$this->conn_pool[$this->conn_name] = array();
				$this->conn_pool[$this->conn_name]['conn'] = new PDO(
					"mysql:host={$conn_info['host']};dbname={$conn_info['dbname']};port={$conn_info['port']}", 
					$conn_info['user'],
					$conn_info['password']
				);
				$php_ver = PHP_VERSION;
				if ((int)($php_ver{0} . $php_ver{2}) > 54) {
					$this->conn_pool[$this->conn_name]['conn']->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);			//启用或禁用预处理
				} else {
					$this->conn_pool[$this->conn_name]['conn']->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);			//启用或禁用预处理
				}
				$this->conn_pool[$this->conn_name]['conn']->setAttribute(PDO::ATTR_TIMEOUT, 5);								//指定超时的秒数。
				$this->conn_pool[$this->conn_name]['conn']->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);					//强制列名小写。
				$this->conn_pool[$this->conn_name]['conn']->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);	//FETCH模式，关联数组
				$this->conn_pool[$this->conn_name]['conn']->query('SET NAMES UTF8');
				
			} catch (PDOException $e) {
				FUNC::re('42', $e->getTraceAsString(), '数据库连接失败！');
			}
		}

		FUNC::re('20', $this->conn_name, '数据库连接成功！');

		//事务处理
		if ($transaction == TRUE) {
			self::begin_transaction($this->conn_name);
		}

		return TRUE;
	}

	/**
	 * 
	 *	关闭数据库连接
	 *	不指定，则全部关闭
	 *	$transaction 事务提交/撤销开关
	 * 
	 **/
	public final function close($db = '', $commit = TRUE) {
		if (empty($db) && empty($this->conn_pool)) {
			return TRUE;
		}

		if (!empty($db) && empty($this->conn_pool[$db])) {
			FUNC::re('42', $db, '关闭数据库失败！');
		}

		//事务处理
		if ($commit == TRUE) {
			self::commit($db);
		} else {
			self::roll_back($db);
		}
		
		//关闭数据库
		if (empty($db)) {
			$this->conn_pool = FALSE;
		    unset($this->conn_pool);
		} else {
			$this->conn_pool[$db] = FALSE;
			unset($this->conn_pool[$db]);
		}
		FUNC::re('20', $db, '关闭数据库成功！');

		return TRUE;
	}

	/**
	 * 
	 *	开启数据库事务
	 * 
	 **/
	public final function begin_transaction($db = '') {
		if (empty($db)) {
			$db = $this->conn_name;
		}
		if (empty($this->conn_pool[$db])) {
			FUNC::re('42', 'ALL', '连接不存在，事务开启失败！');
		}
		if (empty($this->conn_pool[$db]['transaction'])) {
			FUNC::re('20', $db, '开启数据库事务...');
			$this->conn_pool[$db]['transaction'] = TRUE;
			$this->conn_pool[$db]['conn']->beginTransaction();
		}
		FUNC::re('20', $db, '开启数据库事务成功！');

		return TRUE;
	}

	/**
	 * 
	 *	提交数据库事务
	 *	不指定，则全部提交
	 * 
	 **/
	public final function commit($db = '') {
		$db_pool = (empty($db)) ? array_keys($this->conn_pool) : array($db);

		foreach ($db_pool as $key) {
			if (!empty($this->conn_pool[$key]['transaction'])) {
				$this->conn_pool[$key]['transaction'] = FALSE;
				$this->conn_pool[$key]['conn']->commit();
				FUNC::re('20', $key, '提交数据库事务成功！');
			}
		}

		return TRUE;
	}

	/**
	 * 
	 *	回滚数据库事务
	 *	不指定，则全部回滚
	 * 
	 **/
	public final function roll_back($db = '') {
		$db_pool = (empty($db)) ? array_keys($this->conn_pool) : array($db);

		foreach ($db_pool as $key) {
			if (!empty($this->conn_pool[$key]['transaction'])) {
				$this->conn_pool[$key]['transaction'] = FALSE;
				$this->conn_pool[$key]['conn']->rollBack();
				FUNC::re('20', $key, '撤销数据库事务成功！');
			}
		}

		return TRUE;
	}

	/**
	 * 
	 *	执行数据库操作
	 *	$sql_array	= array(
	 *		'type'		=> 'SELECT/INSERT/UPDATE/DELETE',
	 *		'table'		=> 'str',
	 *		'field'		=> 'array/str',		//'字段名' => 'val'
	 *		'on'		=> 'str',
	 *		'where'		=> 'str',
	 *		'order'		=> 'str',
	 *		'limit'		=> 'str',
	 *		'lock'		=>	true			//for update
	 *	);
	 *	返回：结果集/影响的条数
	 * 
	 **/
	public final function exec_sql($sql_array){
		//构建SQL语句
		if (!($compose_sql_re = self::_compose_sql($sql_array))) {
			return FALSE;
		}

		//执行数据库操作
		return self::_exec_sql($compose_sql_re);
	}

	/**
	 * 
	 *	执行数据表操作
	 *	$sql_array	= array(
	 *		'type'		=> 'CREATE/RENAME/DROP/COPY',
	 *		'table'		=>'str',			//要操作的表名
	 *		'tab_source'=> 'str',			//数据源的表名。
	 *			COPY的时候，tab_source可以是数组格式：
	 *			array(
	 *				'tab_name'		=> tabname1 JOIN tabname2				//多个源表名JOIN/LEFT JOIN/RIGHT JOIN/FULL JOIN
	 *				'field_name'	=> field1,field2						//要写的字段名
	 *				'field_source'	=> tabname1.field1,tabname2.field1		//源表中要读的字段
	 *				'where'			=> tabname1.field1 = tabname2.field1	//条件
	 *			)
	 *	返回：影响的条数
	 * 
	 **/
	public final function exec_table($sql_array){
		//执行数据库操作
		return self::_exec_table($sql_array);	
	}
	
	//内部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	构建SQL语句
	 * 
	 **/
	private final function _compose_sql($sql_array){

		//type合法性校验
		if (empty($sql_array['type']) || empty($this->type_array[$sql_array['type']]) || (string)$this->type_array[$sql_array['type']] != 'Y') {
			FUNC::re('42', $sql_array, 'type合法性校验失败！');
		}
		
		//table合法性校验
		if (empty($sql_array['table'])) {
			FUNC::re('42', $sql_array, '表名校验失败！');
		}

		//where合法性校验
		if (empty($sql_array['where']) && ((string)$sql_array['type'] == 'UPDATE' || (string)$sql_array['type'] == 'DELETE')) {
			FUNC::re('42', $sql_array, '更新、删除操作必须带条件！');
		}
		
		//field合法性校验
		if (empty($sql_array['field']) || !is_array($sql_array['field'])){
			if ((string)$sql_array['type'] == 'SELECT') {
				$key_array[]	= (empty($sql_array['field'])) ? '*' : $sql_array['field'];
			} elseif ((string)$sql_array['type'] == 'INSERT' || (string)$sql_array['type'] == 'UPDATE') {
				FUNC::re('42', $sql_array, '新增、更新操作字段必须为数组！');
			} 
		} else {
			foreach ($sql_array['field'] as $key => $value){
				//数组结尾的空key兼容
				if (is_numeric($key) && empty($value)) {
					continue;
				}
				$key_array[]			= '`' . $key . '`';
				$value_array[]			= ':' . $key;
				$key_value[]			= '`' . $key . '` = :' . $key;
				$parameters[':' . $key]	= $value;
			}
		}

		//数据拼装
		$table	= $sql_array['table'];
		$on		= (empty($sql_array['on']))		? '' : ' ON ' . $sql_array['on'];
		$where	= (empty($sql_array['where']))	? '' : ' WHERE ' . $sql_array['where'];
		$where	.= (empty($sql_array['order']))	? '' : ' ORDER BY ' . $sql_array['order'];
		$where	.= (empty($sql_array['limit']))	? '' : ' LIMIT ' . $sql_array['limit'];
		$where	.= (empty($sql_array['lock']))	? '' : ' FOR UPDATE';

		$compose_sql = array(
			'type'			=> $sql_array['type'],
			'sql'			=> '',
			'parameters'	=> ((string)$sql_array['type'] == 'INSERT' || (string)$sql_array['type'] == 'UPDATE') ? $parameters : NULL,
		);

		//拼装SQL语句
		switch($sql_array['type']) {
			case 'SELECT':
				$field = implode(', ', $key_array);
				$compose_sql['sql'] = "SELECT {$field} FROM {$table} {$on} {$where}";		break;
			case 'INSERT':
				$field = implode(', ', $key_array);
				$value = implode(', ', $value_array);
				$compose_sql['sql'] = "INSERT INTO {$table} ({$field}) VALUES ({$value})";	break;
			case 'UPDATE':
				$setkv = implode(', ', $key_value);
				$compose_sql['sql'] = "UPDATE {$table} {$on} SET {$setkv} {$where}";		break;
			case 'DELETE':
				$compose_sql['sql'] = "DELETE FROM {$table} {$on} {$where}";				break;
			default:
				FUNC::re('42', $sql_array['type'], '未知的操作类型！');						break;
		}
		
		FUNC::re('20', $compose_sql, '构建SQL语句成功！');

		return $compose_sql;
	}

	/**
	 * 
	 *	执行数据库操作
	 * 
	 **/
	private final function _exec_sql($compose_sql) {

		try{
			//执行数据库操作
			$sth		= $this->conn_pool[$this->conn_name]['conn']->prepare(
							$compose_sql['sql'],
							array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
							OR FUNC::re('42', $this->conn_pool[$this->conn_name]['conn']->errorInfo(), 'SQL语句错误或没有权限！');
			$sql_exec	= $sth->execute($compose_sql['parameters']);
			$error		= $sth->errorInfo();
			if (empty($sql_exec) || isset($error[1]) || isset($error[2])){
				FUNC::re('42', $error, '数据库操作执行错误！', '2');
				return FALSE;
			}
		} catch (PDOException $e) {
			FUNC::re('42', $e->getTraceAsString(), '数据库执行操作错误！');
		}
		
		//根据执行行为返回不同的值
		switch($compose_sql['type']){
			case 'SELECT':
				$result = $sth->fetchall();												break;
			case 'INSERT':
				//$result = $sth->lastInsertId('uid'); break;
				$result = $this->conn_pool[$this->conn_name]['conn']->lastInsertId();	break;
			case 'UPDATE':
				$result = $sth->rowcount();												break;
			case 'DELETE':
				$result = $sth->rowcount();												break;
			default:
				FUNC::re('42', $compose_sql['type'], '未知的操作类型！');				break;
		}

		FUNC::re('20', $result, '数据库执行成功！');

		return $result;
    }

	/**
	 * 
	 *	执行数据表操作
	 * 
	 **/
	private final function _exec_table($sql_array){

		//type合法性校验
		if (empty($sql_array['type']) || empty($this->type_array[$sql_array['type']]) || (string)$this->type_array[$sql_array['type']] != 'T') {
			FUNC::re('42', $sql_array, 'type合法性校验失败！');
		}

		//table合法性校验
		if (empty($sql_array['table'])) {
			FUNC::re('42', $sql_array, '表名校验失败！');
		}

		//拼装SQL语句
		switch($sql_array['type']) {
			case 'CREATE':
				$compose_sql = self::_show_table($sql_array['table'], $sql_array['tab_source']);			break;
			case 'RENAME':
				$compose_sql = "ALTER TABLE {$sql_array['table']} RENAME TO {$sql_array['tab_source']}";	break;
			case 'DROP':
				$compose_sql = "DROP TABLE IF EXISTS {$sql_array['table']}";								break;
			case 'COPY':
				if (is_array($sql_array['tab_source'])) {
					if (empty($sql_array['tab_source']['tab_name']) ||			//tabname1 JOIN tabname2
						empty($sql_array['tab_source']['field_name']) ||		//field1,field2
						empty($sql_array['tab_source']['field_source']) ||		//tabname1.field1,tabname2.field1
						empty($sql_array['tab_source']['where'])) {				//tabname1.field1 = tabname2.field1
						FUNC::re('42', $sql_array, 'tab_source源表数据校验失败！');
					}
					$where = (stripos($sql_array['tab_source']['tab_name'], 'JOIN') === FALSE) ? ' WHERE ' : ' ON ';
					$compose_sql = "INSERT INTO {$sql_array['table']} ({$sql_array['tab_source']['field_name']}) 
									SELECT {$sql_array['tab_source']['field_source']} FROM {$sql_array['tab_source']['tab_name']} {$where} {$sql_array['tab_source']['where']}";
				}else{
					$compose_sql = "INSERT INTO {$sql_array['table']} SELECT * FROM {$sql_array['tab_source']}";
				}																							break;
			default:
				FUNC::re('42', $sql_array['type'], '未知的操作类型！');										break;
		}

		try{
			//执行数据库操作
			$sth = $this->conn_pool[$this->conn_name]['conn']->exec($compose_sql)
					OR FUNC::re('42', $this->conn_pool[$this->conn_name]['conn']->errorInfo(), 'SQL语句错误或没有权限！');
			if (!isset($sth) || $sth === FALSE){
				FUNC::re('42', $compose_sql, '数据库操作执行错误！');
			}
		} catch (PDOException $e) {
			FUNC::re('42', $e->getTraceAsString(), '数据库执行操作错误！');
		}
		
		FUNC::re('20', $sth, '数据库执行成功！');

		return $sth;
	}

	/**
	 * 
	 *	显示建表语句，用于复制表结构
	 *	传入$table, $tab_source
	 *	返回 建表语句;
	 * 
	 **/
	private final function _show_table($table, $tab_source){
		$compose_sql = array(
			'type'			=> 'SELECT',
			'sql'			=> 'SHOW CREATE TABLE ' . $tab_source,
			'parameters'	=> NULL
		);

		$result = self::_exec_sql($compose_sql);
		if (empty($result[0]['create table'])) {
			FUNC::re('42', $compose_sql, '获取源表数据库结构失败！');
		}

		$create_sql = str_replace(
			array("\\",	"CREATE TABLE `{$tab_source}`"),
			array("",	"CREATE TABLE IF NOT EXISTS `{$table}`"), 
			$result[0]['create table']);
		
		FUNC::re('20', $create_sql, '构建建表SQL语句成功！');

		return $create_sql;
	}
}
?>