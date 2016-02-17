<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsAdminController
 * @author NURIGO (Contact@nurigo.net)
 * @brief  newpostsAdminController
 */
class newpostsAdminController extends newposts 
{
	/**
	 * @brief Constructor
	 */
	function init() 
	{
	}

	/**
	 * @brief saving config values.
	 *
	 **/
	function procNewpostsAdminInsert() 
	{
		$params = Context::gets('admin_phones','admin_emails', 'sender_phone', 'sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method', 'sms_method', 'time_switch', 'time_start', 'time_end', 'reserv_switch', 'selected_days');

		// 모듈 입력을 하지 않앗을 경우 에러메시지 & Redirect
		if(!$params->module_srls) return new Object(-1, 'notify_empty_module');

		//프로세스
		$output = $this->processNewpostsAdmin($params);
		if(!$output->toBool()) return $output;

		$this->setMessage('notify_add_newposts');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify','config_srl',$params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief modify configs
	 *
	 **/
	function procNewpostsAdminModify()
	{
		$params = Context::gets('admin_phones','admin_emails','sender_phone','sender_name','sender_email','content','mail_content','module_srls','msgtype','sending_method', 'sms_method', 'time_switch', 'time_start', 'time_end', 'reserv_switch', 'selected_days');
		$params->config_srl = Context::get('config_srl');
		// Insert 와 다른점은 이건 Modify 로 Redirect 하고 Insert 는 Insert 로 Redirect
		// 모듈 입력을 하지 않앗을 경우 에러메시지 & Redirect
		if(!$params->module_srls) return new Object(-1, 'notify_emtpy_module');

		//프로세스
		$output = $this->processNewpostsAdmin($params);
		if(!$output->toBool()) return $output;

		$this->setMessage('새글알림이 수정되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminModify', 'config_srl', $params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief process config settings whether create or modify to avoid massive duplicated codes
	 *
	 **/
	function processNewpostsAdmin(&$parm)
	{
		// 시작하는 시간은 있는데 끝나는 시간이 없는 경우
		if($parm->time_start && !$parm->time_end) return new Object(-1, Context::getLang('notify_empty_ending_time'));
		// 시작하는 시간이 없고 끝나는 시간만 있는 경우
		if(!$parm->time_start && $parm->time_end) return new Object(-1, Context::getLang('notify_empty_starting_time'));

		// 파라미터에 config_srl 있으면 지우고 다시만들고 없으면 새로 받아오고
		if($parm->config_srl) 
		{
			$args->config_srl = $parm->config_srl;

			$output = executeQuery('newposts.getConfig', $args);
			if(!$output->toBool()) return $output;
			$extra_vars = $output->data->extra_vars;
			
			// delete existences
			$output = executeQuery('newposts.deleteConfig', $args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('newposts.deleteModule', $args);
			if (!$output->toBool()) return $output;
		}
		else
		{
			// new sequence
			$parm->config_srl = getNextSequence();
		}
		// 현재 config_srl 에 있는 모듈 시리얼들을 가져오기
		$module_srls = explode(',', $parm->module_srls);

		// newposts.modules 에 insert 하기
		foreach ($module_srls as $srl) 
		{
			unset($args);
			$args->config_srl = $parm->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('newposts.insertModuleSrl', $args);
			if (!$output->toBool()) return $output;
		}
		// newposts.config 에 insert 하기 
		if($extra_vars) $parm->extra_vars = $extra_vars;

		$output = executeQuery('newposts.insertConfig', $parm);
		if (!$output->toBool())	return $output;

		return new Object();
	}

	/**
	 * @brief delete config
	 *
	 **/
	function procNewpostsAdminDelete() 
	{
		$config_srl = Context::get('config_srl');
		if (!$config_srl) return new Object(-1, 'msg_invalid_request');

		// delete existences
		$args->config_srl = $config_srl;
		$query_id = "newposts.deleteConfig";
		$output = executeQuery($query_id, $args);
		if(!$output->toBool()) return $output;
		$query_id = "newposts.deleteModule";
		$output = executeQuery($query_id, $args);
		if(!$output->toBool()) return $output;
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminList');
		$this->setMessage('성공적으로 삭제되었습니다');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief 분류별 담당자 지정
	 *
	 **/
	function procNewpostsAdminSetCategoryAdmins()
	{
		$category_srl = Context::get('category_srl');
		$cellphone = Context::get('cellphone');
		$email = Context::get('email');
		$config_srl = Context::get('config_srl');
		$error = array();
		if(!sizeof($cateogry_srl)) return new Object(-1, 'category_srl is empty');

		for($i=0; $i<sizeOf($category_srl); $i++)
		{
			$args->cellphone = $cellphone[$i];
			$args->email = $email[$i];
			$args->category_srl = $category_srl[$i];
			$output = executeQueryArray("newposts.updateAdminInfo", $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('등록되었습니다.');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNewpostsAdminSetCategoryAdmins', 'config_srl', $config_srl);
		$this->setRedirectUrl($redirectUrl);
	}
}
/* End of file newposts.admin.controller.php */
/* Location: ./modules/newposts/newposts.admin.controller.php */
