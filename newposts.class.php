<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newposts
 * @author NURIGO (Contact@nurigo.net)
 * @brief  newposts
 */
class newposts extends ModuleObject 
{
	/**
	 * @brief Object를 텍스트의 %...% 와 치환.
	 **/
	function mergeKeywords($text, &$obj) 
	{
		if (!is_object($obj)) return $text;
		foreach ($obj as $key => $val)
		{
			if (is_array($val)) $val = join($val);

			if (is_string($key) && is_string($val)) 
			{
				if (substr($key,0,10)=='extra_vars') 
				{ 
					$val = str_replace('|@|', '-', $val);
				}
				elseif(!$val)
				{
					$val = "";
				}
				$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
			}
		}
		return $text;
	}

	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// Document Registration Trigger
		$oModuleController->insertTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after');
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate() 
	{
		error_log('checkUpdate');
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// Document Registration Trigger
		if (!$oModuleModel->getTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after'))
		{
			return true;
		}

		// 2012.03.06 add newposts_config.sender_name
		if(!$oDB->isColumnExists("newposts_config","sender_name")) return true;
		// 2012.03.06 add newposts_config.sender_email
		if(!$oDB->isColumnExists("newposts_config","sender_email")) return true;

		// 2014.06.16 add newposts_config.sender_phones
		if(!$oDB->isColumnExists("newposts_config","sender_phone")) return true;

		// 2015. 06. 09 add newposts_config.sms_method
		if(!$oDB->isColumnExists("newposts_config","sms_method")) return true;
		// 2015. 06. 09 add newposts_config.time_switch
		if(!$oDB->isColumnExists("newposts_config","time_switch")) return true;
		// 2015. 06. 09 add newposts_config.time_start
		if(!$oDB->isColumnExists("newposts_config","time_start")) return true;
		// 2015. 06. 09 add newposts_config.time_end
		if(!$oDB->isColumnExists("newposts_config","time_end")) return true;
		// 2015. 06. 09 add newposts_config.reserv_switch
		if(!$oDB->isColumnExists("newposts_config","reserv_switch")) return true;

		// 2015. 06. 12 add newposts_config.selected_days
		if(!$oDB->isColumnExists("newposts_config","selected_days")) return true;

		// 2016/03/17
		if(!$oDB->isColumnExists("newposts_config","sender_key")) return true;
		// 2016/03/17
		if(!$oDB->isColumnExists("newposts_config","template_code")) return true;

		if(!$oDB->isColumnExists("newposts_config","send_to_writer")) return true;
		if(!$oDB->isColumnExists("newposts_config","writer_content")) return true;
		if(!$oDB->isColumnExists("newposts_config","writer_extra_key")) return true;

		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// Document Registration Trigger
		if (!$oModuleModel->getTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after'))
		{
			$oModuleController->insertTrigger('document.insertDocument', 'newposts', 'controller', 'triggerInsertDocument', 'after');
		}

		if(!$oDB->isColumnExists("newposts_config","sender_name")) {
			$oDB->addColumn("newposts_config","sender_name", "varchar","80");
		}
		if(!$oDB->isColumnExists("newposts_config","sender_email")) {
			$oDB->addColumn("newposts_config","sender_email", "varchar","250");
		}
		if(!$oDB->isColumnExists("newposts_config","sender_phone")) {
			$oDB->addColumn("newposts_config", "sender_phone", "varchar", "250");
		}
		if(!$oDB->isColumnExists("newposts_config","sms_method")) {
			$oDB->addColumn("newposts_config", "sms_method", "char", "1");
		}
		if(!$oDB->isColumnExists("newposts_config","time_switch")) {
			$oDB->addColumn("newposts_config", "time_switch", "varchar", "10");
		}
		if(!$oDB->isColumnExists("newposts_config","time_start")) {
			$oDB->addColumn("newposts_config", "time_start", "number", "10");
		}
		if(!$oDB->isColumnExists("newposts_config","time_end")) {
			$oDB->addColumn("newposts_config", "time_end", "number", "10");
		}
		if(!$oDB->isColumnExists("newposts_config","reserv_switch")) {
			$oDB->addColumn("newposts_config", "reserv_switch", "varchar", "10");
		}
		if(!$oDB->isColumnExists("newposts_config","selected_days")) {
			$oDB->addColumn("newposts_config", "selected_days", "varchar", "30");
		}
		if(!$oDB->isColumnExists("newposts_config","sender_key")) {
			$oDB->addColumn("newposts_config", "sender_key", "varchar", "60");
		}
		if(!$oDB->isColumnExists("newposts_config","template_code")) {
			$oDB->addColumn("newposts_config", "template_code", "varchar", "30");
		}

		if(!$oDB->isColumnExists("newposts_config","send_to_writer")) {
			$oDB->addColumn("newposts_config", "send_to_writer", "char", "1");
		}
		if(!$oDB->isColumnExists("newposts_config","writer_content")) {
			$oDB->addColumn("newposts_config", "writer_content", "bigtext");
		}
		if(!$oDB->isColumnExists("newposts_config","writer_extra_key")) {
			$oDB->addColumn("newposts_config", "writer_extra_key", "varchar", "50");
		}
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache() 
	{
	}

	/**
	 * XE Object를 생성하여 반환한다.
	 *
	 * XE 1.8 이하, XE 1.9 이상, PHP 7.1 이하, PHP 7.2 이상 모두 호환된다.
	 * 기본적인 사용법은 return new Object(-1, 'error'); 라고 쓸 자리에
	 * return $this->createObject(-1, 'error'); 라고 쓰면 된다.
	 *
	 * 반환할 언어 내용 중 %s, %d 등 변수를 치환하는 부분이 있다면
	 * 치환할 내용을 추가 파라미터로 넘겨주면 sprintf()의 역할까지 해준다.
	 *
	 * @param string $message
	 * @param $arg1, $arg2 ...
	 * @return object
	 */
	public function createObject($status = 0, $message = 'success' /* $arg1, $arg2 ... */)
	{
		$args = func_get_args();
		if (count($args) > 2)
		{
			global $lang;
			$message = vsprintf($lang->$message, array_slice($args, 2));
		}
		return class_exists('BaseObject') ? new BaseObject($status, $message) : new Object($status, $message);
	}
}
/* End of file newposts.class.php */
/* Location: ./modules/newposts/newposts.class.php */
