<?php

/**
 * php  路由文件
 */

class ROUTE {
	
	//外部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	入口解析
	 * 
	 **/
	public static function go(){

		//模块与方法校验
		if (empty($_GET['m']) || 
			empty($_GET['f']) ||
			!preg_match('/^[a-zA-Z_]+$/', $_GET['m']) ||
			!preg_match('/^[a-zA-Z_]+$/', $_GET['f'])) {
			header("location: " . PATH_404);
		}

		//模块名、方法名、域名规范化
		$_GET['m'] = strtolower($_GET['m']);
		$_GET['f'] = strtolower($_GET['f']);

		//模块判断
		$m_file = APP_PATH . '/function/' . $_GET['m'] . '.php';
		if (!file_exists($m_file)) {
			header("location: " . PATH_404);
		}
		include_once($m_file);
		
		//方法判断
		$m_name	= 'cls_' . $_GET['m'];
		$f_name	= 'func_' . $_GET['f'];
		if (!method_exists($m_name, $f_name)) { 
			header("location: " . PATH_404);
		}

		//载入全局配置容器
		include_once(BASEPATH . '/global/config/gconf.php');

		//模块名、方法名、域名规范化
		if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			G::$URL['host'] = strtolower($_SERVER['HTTP_X_FORWARDED_HOST']);
		} elseif (!empty($_SERVER['HTTP_HOST'])) {
			G::$URL['host'] = strtolower($_SERVER['HTTP_HOST']);
		}
		$domain = explode('.', G::$URL['host']);
		$domain_len = count($domain) - 2;                    //依据代理具体
		G::$URL['domain'] = (empty($domain[$domain_len])) ? G::$URL['host'] : $domain[$domain_len] . '.' . $domain[$domain_len + 1];
		G::$URL['m'] = $_GET['m'];
		G::$URL['f'] = $_GET['f'];

		//载入底层支持方法
		include_once(APP_PATH . '/lib/base.php');

		//销毁外部变量
		$_GET = $_POST = array();
		
		//执行方法
		$class = new $m_name;
		$class->$f_name();

	}

}

?>