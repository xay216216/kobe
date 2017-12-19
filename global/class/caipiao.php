<?php

/******************************

	caipiao操作方法
	

******************************/

class caipiao extends db_class {
	
	const table_name = 'caipiao';	

	/**
	 * 
	 *	查询用户数据
	 * 
	 **/
	public static function select_caipiao($sql, $code = '', $message = '') {

		//数据整理
		$data = array(
			'where'	=>	"status = '" . $sql['status'] . "'
						 AND type = '" . $sql['type'] . "'
						 AND qihao = '" . $sql['qihao'] . "'",
		);

		//执行操作
		$exec_sql = parent::select($sql['mysql_name'], self::table_name, $sql, $data, $code, $message);
		
		//输出
		return self::_format_data($exec_sql, '', 'id');

	}

	
	

	//内部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	格式化数据
	 * 
	 **/
	private static function _format_data($exec_sql, $k = '', $n = 'id') {
		
		//初始化
		$format_data = (empty($k)) ? array() : array($k => array());

		//格式化
		foreach ($exec_sql as $key => $value) {
			$format_data[$value[$n]] = array(
				'id'				=>	$value['id'],
				'qihao'				=>	$value['qihao'],
				'b_one'				=>	$value['b_one'],
				'b_two'				=>	$value['b_two'],
				'b_three'			=>	$value['b_three'],
				'b_four'			=>	$value['b_four'],
				'r_one'				=>	$value['r_one'],
				'type'				=>	$value['type'],
				'status'		 	=>	$value['status'],               
			);
		}

		//输出
		return (empty($k)) ? $format_data : $format_data[$k];

	}

}

?>