<?php

/**
 * php  彩票操作文件
 */

class cls_caip {
	
	//外部方法部分////////////////////////////////////////////////////

	/**
	 * 
	 *	获取
	 * 
	 **/
    public function func_index() {

    	include_once(BASEPATH . '/global/class/caipiao.php');
    	//查询某期彩票
		$select_caipiao_data = caipiao::select_caipiao(G::$SQL + array(    
			'qihao'		=>  G::$GET['qihao'],                              
			'type'		=>  1,
			'status'	=>  1,
			'lock'		=>  1,
		));

		//返回数据
		FUNC::out('20', $select_caipiao_data);   

	}


	/**
	 * 
	 *	用户登录
	 * 
	 **/
    public function func_login() {

    	//外部变量校验
    	if (empty(G::$POST['user_mobile'])) 
			FUNC::re('415101');	
 
		if (empty(G::$POST['user_pass']))
			FUNC::re('415103');	
		
		//执行用户登录
		A_LOGIN::a_user_login(G::$POST);

		//返回数据
		FUNC::out('205102');

	}

	/**
	 * 
	 *	退出登录
	 * 
	 **/
    public function func_logout() {

		//清除登录SESSION\COOKIE
		A_LOGIN::del_session_cookie();

		//返回数据
		FUNC::out('205104');
	}

	

}

?>