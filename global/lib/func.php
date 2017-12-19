<?php

/******************************

	全局方法池
	

******************************/

class FUNC {

	//外部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	获取客户端IP地址，多个用,分割
	 * 
	 **/
	public static function user_ip(){

		$result = array_unique(array(
			getenv("HTTP_CLIENT_IP"),
			getenv("HTTP_X_FORWARDED_FOR"),
			getenv("REMOTE_ADDR"),
			((empty($_SERVER['REMOTE_ADDR'])) ? '' : $_SERVER['REMOTE_ADDR'])
		)); 
		
		foreach ($result as $key => $value) {
			if (empty($value) || !strcasecmp($value, "unknown")) {
				unset($result[$key]);
			}
		}

		return implode(',', $result); 
	}

	/**
	 * 
	 *	构建全局唯一性ID
	 * 
	 **/
	public static function gid($type){
		
		//如果该ID会被页面POST给程序，需要在gconf的G::$FORMAT中添加对应的规则。
		$type_data = array(
			'cid'			=>	'ci',
			'user_id'		=>	'uu',			//用户
			'a_user_id'		=>	'au',			//后台用户
		);

		if (empty($type_data[$type])) {
			self::re('41', '', '未知的ID构建类型！');
		}

		$time	= explode(' ', microtime());
		$code	= base_convert(mt_rand(10000, 99999) . $time[1] . substr($time[0], 2, 4), 10, 36);
		$code	= substr($code, 0, 10);
		$code	= str_pad($code, 10, '0', STR_PAD_RIGHT);
		return $type_data[$type] . $code;
	}

	/**
	 * 
	 *	数组合并，相同键名则相加
	 *	a b 为二维数组，field为要相加的元素，len为保留小数长度
	 * 
	 **/
	public static function array_add($a, $b, $field, $len = 2) {
		if (empty($a) || !is_array($a)) {
			return $b;
		}
		if (empty($b) || !is_array($b)) {
			return $a;
		}
		foreach ($b as $key => $value) {
			if (isset($a[$key][$field]) && !empty($value[$field])) {
				$a[$key][$field] = bcadd($a[$key][$field], $value[$field], $len);
			} else {
				$a[$key] = $value;
			}
		}	
		return $a;
	}


	/**
	 * 
	 *	统一校验外部变量
	 * 
	 **/
	public static function check($data, $pkey = '') {
		
		$result = array();

		if (!is_array($data)) {
			return $result;
		}

		foreach ($data as $key => $value) {

			//数字KEY，采用父级KEY校验
			$mykey = (is_numeric($key) && !empty($pkey)) ? $pkey : $key;

			//数组递归调用
			if (is_array($value)){
				$result[$key] = self::CHECK($value, $mykey);
				continue;
			}
			
			//去掉自动添加的反斜杠
			$value = (get_magic_quotes_gpc()) ? stripslashes($value) : $value;

			//JSON格式兼容：数组递归调用
			$json = json_decode($value, TRUE);  //if (json_last_error() === 'JSON_ERROR_NONE')
			if (is_array($json)){
				$result[$key] = self::CHECK($json, $mykey);
				continue;
			}

			//数据格式检查   这里还可以更灵活比如跳过检验
			if (empty($mykey) || 
				empty(G::$FORMAT[$mykey]) ||
				!preg_match(G::$FORMAT[$mykey], $value)) {
				continue;
			}
		    
			$result[$key] = $value;
		}

		return $result;

	}


	/**
	 * 
	 *	统一过滤客户输入的文本
	 * 
	 **/
	public static function check_text($str) {
		$replace = '\d\w\x{4e00}-\x{9fa5}\x{3000}-\x{301e}\x{fe10}-\x{fe19}\x{fe30}-\x{fe44}\x{fe50}-\x{fe6b}\x{ff01}-\x{ffee}';	//中文、数字、字母、全角符号
		$str = preg_replace('/[^\/\"\+\-\*\%\=()<>,.:;?!~@#&$\s' . $replace . ']/u', '', $str);  
		$str = preg_replace('/<script[\s\S]*?<\/script>/i', '', $str);

		self::re('20', $str, '输入文本过滤！');
		return $str;
	}

	/**
	 * 
	 *	密钥编码
	 * 
	 **/
	public static function en_code($str, $code) {

		return MD5($str . $code);
	
	}

	/**
	 * 
	 *	密钥比对
	 * 
	 **/
	public static function diff_code($diff, $str, $code) {

		$en_code = self::en_code($str, $code);

		return ((string)$diff == (string)$en_code) ? TRUE : FALSE;

	}


	/**
	 * 
	 *	发送POST请求
	 * 
	 **/
	public static function post($url, $send_data, $second = 30) {

		$post_string = http_build_query($send_data);				//根据数组产生一个urlencode之后的请求字符串

		//创建CURL请求
		$ch = curl_init() ;
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch) ;

		curl_close($ch) ;

		return $result;
	}

	/**
	 * 
	 *	运行状态处理
	 *	type：1如果有错就回滚退出 2如果有错也不退出(一般用于进程中，自己控制中断回滚)
	 * 
	 **/
	public static function re($code, $data = '', $message = '', $type = 1) {
		
		//记录运行信息
		G::$INFO[]	=	array(
			'code'		=>	$code,
			'message'	=>	self::_get_message($code, $message),		//记录，优先记录程序中指定的信息
			'data'		=>	$data,
			'time'		=>	date("Y-m-d H:i:s")
		); 

		//捕获错误信息
		if ($code{0} != '2') {
			
			//记录第一个错误，用于输出
			if (!G::$ERROR) {
				G::$ERROR = array(
					'code'		=>	$code,
					'message'	=>	self::_get_message($code, ''),			//输出，必须是配置中统一设定的信息
				);
			}

			//中断处理
			if ((string)$type == '1') {
				self::out($code, $data, $message, FALSE);
			}

		}

	}

	/**
	 * 
	 *	输出程序运行结果
	 * 
	 **/
	public static function out($code, $data = '', $message = '', $is_info = TRUE) {
		
		if (G::$CONFIG['out_type'] === TRUE) {
			return TRUE;
		} else {
			G::$CONFIG['out_type'] = TRUE;
		}
		
		//记录运行信息
		if ($is_info) {
			self::re($code, $data, $message, 2);
		}
		
		//有错误按错误输出
		if (G::$ERROR) {
			G::$MDB->close('', FALSE);									//关闭所有数据库连接并撤销事务
			$result = G::$ERROR; 
		} else {
			G::$MDB->close('', TRUE);									//关闭所有数据库连接并提交事务
			$result = array(
				'code'		=>	$code,
				'message'	=>	self::_get_message($code, ''),			//输出，必须是配置中统一设定的信息
				'data'		=>	$data
			);

			//清除缓存
			self::clear_cache();
		}

		//LOG投放处理
		if ((string)G::$CONFIG['log_type'] == '1' ||
			((string)G::$CONFIG['log_type'] == '2' && G::$ERROR)) {
			self::write_log(G::$INFO);
		}

		echo json_encode($result);

		self::write_log(array('===============' . time() . '=' . $message . ' =EXIT================================'));
		exit;

	}

	/**
	 * 
	 *	运行状态处理：用于后台进程
	 * 
	 **/
	public static function re_log($code, $data = '', $message = '') {
		self::re($code, $data, $message, '2');

		if (G::$ERROR) {
			G::$MDB->close('', FALSE);									//关闭所有数据库连接并撤销事务
		} else {
			//没错误不提交事务，这个由程序控制
			//清除缓存
			self::clear_cache();
		}

		//LOG投放处理
		if ((string)G::$CONFIG['log_type'] == '1' ||
			((string)G::$CONFIG['log_type'] == '2' && G::$ERROR)) {
			self::write_log(G::$INFO);
			G::$INFO = G::$ERROR = array();
		}
	}

	/**
	 * 
	 *	查询并构建缓存
	 * 
	 **/
	public static function select_cache($cache_data, $code = '', $message = '', $is_create = FALSE) {
		
		//基本配置校验
		if (empty($cache_data['cache_key'])) {					//构建缓存传入的参数
			self::re('42', $cache_data, '缓存调用失败！');
		}
		
		//获取缓存配置
		if (empty(G::$CACHE[$cache_data['cache_key']])) {
			self::re('42', $cache_data, '缓存调用失败！');
		}
		$cache_info = G::$CACHE[$cache_data['cache_key']];
		
		//获取缓存构建配置
		if (empty(G::$CACHE_CLEAR[$cache_info['class']][$cache_data['cache_key']])) {
			self::re('42', $cache_data, '缓存配置调用失败！');
		}

		//构建cache_key
		$cache_key = $cache_data['cache_key'];
		foreach (G::$CACHE_CLEAR[$cache_info['class']][$cache_data['cache_key']] as $key => $value) {
			if (empty($value)) {
				continue;
			}
			if (empty($cache_data[$key])) {
				self::re('42', $key, '缺少缓存构建元素！');
			}
			$cache_key .= '_' . $cache_data[$key];
		}

		//初始化redis连接
    	G::$RDB->conn($cache_info['conn']);

		//获取缓存
		$select_cache_re = G::$RDB->exec_sql(array(
			'type'		=>	'SELECT',
			'key'		=>	$cache_key,
		));
		if (!empty($select_cache_re)) {
			if (!($select_cache = unserialize($select_cache_re)) && !empty($code)) {
				self::re($code, $select_cache_re, $message);
			}
			self::re('20', $select_cache, '获取' . $cache_key . '缓存成功！');
			return $select_cache;
		}
		
		if ($is_create == TRUE) {
			self::re('42', $select_cache_re, '获取' . $cache_key . '缓存失败！');
		}

		//构建缓存
		if ($cache_info['is_load_class']) {
			include_once(BASEPATH . '/global/class/' . $cache_info['class'] . '.php');
		}
		//$select_data_re = $cache_info['class']::$cache_info['func']($cache_data['data']);
		//兼容代码加密
		$cache_class = new $cache_info['class'];
		$select_data_re = $cache_class->$cache_info['func']($cache_data['data']);

		//没有找到数据，则缓存空数组
		if (empty($select_data_re)) {
			$select_data_re = array();
		}

		//记录缓存
		$insert_cache_re = G::$RDB->exec_sql(array(
			'type'		=>	'INSERT',
			'key'		=>	$cache_key,
			'value'		=>	serialize($select_data_re),
			'cache'		=>	$cache_info['cache_time'],
		));
		if (empty($insert_cache_re)) {
			self::re('42', $cache_info, '记录' . $cache_key . '缓存失败！');
		}

		//回调输出缓存
		return self::select_cache($cache_data, $code, $message, TRUE);
	}

	/**
	 * 
	 *	清除缓存
	 * 
	 **/
	public static function clear_cache() {
		if (empty(G::$CLEAR)) {
			return TRUE;
		}

		foreach (G::$CLEAR as $key => $value) {

			//基本配置校验
			if (empty($value) || empty(G::$CACHE[$value])) {						//缓存KEY
				self::re('42', array($key, $value), '缓存清除失败！');
			}

			//初始化redis连接
    		G::$RDB->conn(G::$CACHE[$value]['conn']);
			
			//清除缓存
			G::$RDB->exec_sql(array(
				'type'		=>	'DELETE',
				'key'		=>	$key,
			));

		}

		G::$CLEAR = array();
	}

	/**
	 * 
	 *	递归的清空目录
	 * 
	 **/
	public static function del_dir($dir) {
		$file_list = scandir($dir);
		foreach ($file_list as $file) {
			if ($file == '.' || $file == '..' ) {
				continue;
			}
			if(is_dir($dir . '/' . $file)) {
				if (!self::del_dir($dir . '/' . $file)) {
					return FALSE;
				}
			} else {
				unlink($dir . '/' . $file);
			}
        }

		//删除当前文件夹：
		return rmdir($dir);
	}

	/**
	 * 
	 *	字符转义
	 * 
	 **/
	public static function chr_str($asc) {
		$str = '';
		foreach ($asc as $key => $value) {
			$str .= (is_array($value)) ? self::chr_str($value) : chr($value); 
		}
		return $str;
	}

	/**
	 * 
	 *	LOG投放处理
	 * 
	 **/
	public static function write_log($data, $m = '', $f = '') {

		$m = (empty($m)) ? G::$URL['m'] : $m;
		$f = (empty($f)) ? G::$URL['f'] : $f;

		$log_file =  G::$PATH['log'] . '/' . $m . '_' . date("Ymd") . '.txt';
		$data = "== " . $f . " ==========\n" . var_export(self::_unset_privacy($data), TRUE) . "\n\n";
		$handle = fopen($log_file, 'a+');
		if (fwrite($handle, $data) === FALSE) {
			return FALSE; 
		}
		fclose($handle);
		
		return TRUE; 
	}

	//内部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	获取提示信息
	 * 
	 **/
	private static function _get_message($code, $message) {

		if (!$message) {
			$message = (array_key_exists($code, G::$MESSAGE)) ? G::$MESSAGE[$code] : G::$MESSAGE[0];
		}
		
		return $message; 
	}

	/**
	 * 
	 *	去掉敏感数据
	 * 
	 **/
	private static function _unset_privacy($data) {

		//遍历敏感KEY
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				//数组就递归
				$data[$key] = self::_unset_privacy($value);
			} elseif ((string)$key == 'password' || 
				(string)$key == 'pwd_new' || 
				(string)$key == 'pwd_repeat' ||
				(string)$key == 'user_pass' ||
				(string)$key == 'a_user_pass'){
				unset($data[$key]);
			}
		}

		return $data; 
	}

}

?>