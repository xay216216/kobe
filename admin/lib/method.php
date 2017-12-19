<?php

/**
 * php 　后台通用方法文件
 */

class M {
	
	//外部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	请求合法性判断
	 * 
	 **/
	public static function check_postdata_cid() {

		//接收校验外部变量
		FUNC::re('20', $_POST, 'POST原始数据！');
		G::$POST = FUNC::check($_POST);
		FUNC::re('20', G::$POST, 'POST数据校验完成！');

		FUNC::re('20', $_GET, 'GET原始数据！');
		G::$GET = FUNC::check($_GET);
		FUNC::re('20', G::$GET, 'GET数据校验完成！');

		//统一登录校验
		if (G::$URL['m'] != 'user') {
			//A_LOGIN::login_check();
		}

		//统一权限校验
		if (G::$URL['m'] != 'user') {
			//A_LOGIN::check_permit();
		}

	}

	

}

?>