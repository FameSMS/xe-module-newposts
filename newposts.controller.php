<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsController
 * @author wiley@nurigo.net
 * @brief  newpostsController
 */
class newpostsController extends newposts 
{

	/**
	 * @brief 설정된 시간 외에 알림들을 리포트 형식으로 예약 발송
	 **/
	function sendReservedReport($content, $config, $sender)
	{
		$oTextmessageController = &getController('textmessage');
		$oNewpostsModel = &getModel('newposts');

		$args = new stdClass();
		$extra_vars = new stdClass();

		//serialize extra_vars(stdclass 를 db 넣으려면 이렇게해야함)
		if($config->extra_vars) $extra_vars = unserialize($config->extra_vars);

		$time = date('Ym'); 	// 현재 년도 + 월
		$today = date('d');		// 오늘 날짜
		$tomorrow = date('d', strtotime("+1 day")); 	// 내일 날짜
		$hour = date('H');		// 현재 시간
		$start = sprintf("%02d", $config->time_start);   	// config에 설정된 시작 시간 
		$end = sprintf("%02d", $config->time_end);			// config에 설정된 끝나는 시간

		if($hour < $start)
		{
			$args->datetime = sprintf("%s%s%s0000", $time, $today, $start);
		}
		elseif($hour >= $end)
		{
			$args->datetime = sprintf("%s%s%s0000", $time, $tomorrow, $start);
		}

		// extra_vars 에 regdate 가 있다면 이미 발송된 예약문자가 있다는 뜻이므로
		// 이미 발송된 예약문자를 extra_vars->group_id 로 취소를 한뒤
		// 다시 문자 발송

		//regdate yyyymmdd 가 datetime 의 yyyymmdd 가 같으면 같은날 발송된것
		if(substr($extra_vars->regdate, 0, 8) == substr($args->datetime, 0, 8))
		{
			$extra_vars->msg_count++;
			if($extra_vars->group_id)
			{
				$output = $oTextmessageController->cancelGroupMessages($extra_vars->group_id);
				if(!$output->toBool()) return $output;
			}
		}
		else
		{
			//다른 날이라면 msg_count 초기화
			$extra_vars->msg_count = 0;
		}

		// 문자 내용 처리
		$args->sender_no = $config->sender_phone;
		$args->recipient_no = explode(',',$config->admin_phones);
		$msg_format = "%s 포함한 총 %d 건의 게시글이 등록되었습니다.";
		$args->content = sprintf($msg_format, substr($content, 0, 50), $extra_vars->msg_count + 1);

		// 문자 전송
		if(count($args->recipient_no))
		{
			$result = $oTextmessageController->sendMessage($args);
			if (!$result->toBool()) return $return;
		}

		// newposts config 에 extra_vars 업데이트
		$extra_vars->regdate = $args->datetime;
		// group_id 설정
		if($result->variables['group_id']) $extra_vars->group_id = $result->variables['group_id'];

		$args->config_srl = $config->config_srl;
		$args->extra_vars = serialize($extra_vars);
		$output = executeQuery('newposts.updateExtraVars', $args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief 트리거를 통해 문자를 발송
	 **/
	function sendMessages($content, $mail_content, $obj, $sender, $config) 
	{
		$oTextmessageController = &getController('textmessage');
		$oNewpostsModel = &getModel('newposts');
		// get Phone# & email address accoring to category admin from newposts_admins table
		$args->category_srl = $obj->category_srl;
		$output = executeQuery("newposts.getAdminInfo", $args);

		if (in_array($config->sending_method,array('1','2')) && $oTextmessageController) 
		{
			$args->sender_no = $config->sender_phone;
			$args->type = "sms";
			if($config->sms_method == 2 && mb_strlen($args->content) > 89) $args->type = "lms";

			// 발송 시간 설정 리포트 예약 발송
			$hour = intval(date('H'));		// 현재 시간
			$start = intval($config->time_start);
			$end = intval($config->time_end);

			// 항상 받기 off  리포트예약발송 off  시간이 설정된 시간이 아니면 return 
			if($config->time_switch != 'on' && $config->reserv_switch != 'on' && ($hour < $start || $hour >= $end))
			{
				return;
			}

			// 리포트예약발송 on 이면서 시간이 알림 받을 시간이 아니면
			if($config->reserv_switch =='on' && ($hour < $time_start || $hour >= $end))
			{
				$this->sendReservedReport($content, $config, $sender);	
				return;
			}


			// 분류별 게시판 관리자에게 문자알림
			if($output->data->cellphone)
			{
				$args->recipient_no = explode(',',$output->data->cellphone);
				$args->content = $content;
				$result = $oTextmessageController->sendMessage($args);
				if (!$result->toBool()) return $output;
			}

			//전체관리자 문자알림 : 분류에 상관없이 보냄
			$args->recipient_no = explode(',',$config->admin_phones);
			$args->content = $content;

			if(count($args->recipient_no))
			{
				$result = $oTextmessageController->sendMessage($args);
				if (!$result->toBool()) return $output;
			}
		}

		// 
		if (in_array($config->sending_method,array('1','3'))) 
		{
			if ($config->sender_email)
			{
				$sender_email_address = $config->sender_email;
			}
			else
			{
				$sender_email_address = $sender->email_address;
			}
			if ($config->sender_name)
			{
				$sender_name = $config->sender_name;
			}
			else
			{
				$sender_name = $sender->nick_name;
			}
			$oMail = new Mail();
			$oMail->setTitle($obj->title);
			$oMail->setContent($mail_content);
			$oMail->setSender($sender_name, $sender_email_address);

			//분류별 관리자 E-mail 
			$target_email = explode(',',$output->data->email);
			foreach ($target_email as $email_address) 
			{
				$email_address = trim($email_address);
				if (!$email_address) continue;
				$oMail->setReceiptor($email_address, $email_address);
				$oMail->send();
			}
			
			//전체관리자 E-mail 
			$target_email = explode(',',$config->admin_emails);
			foreach ($target_email as $email_address) 
			{
				$email_address = trim($email_address);
				if (!$email_address) continue;
				$oMail->setReceiptor($email_address, $email_address);
				$oMail->send();
			}
		}
	}

	/**
	 * @brief 문자 발송전에 문자내용을 준비
	 **/
	function processNewposts(&$config,&$obj,&$sender,&$module_info) 
	{
		$oMemberModel = &getModel('member');
		// message content
		$sms_message = $this->mergeKeywords($config->content, $obj);
		$sms_message = $this->mergeKeywords($sms_message, $module_info);
		$sms_message = str_replace("&nbsp;", "", strip_tags($sms_message));
		// mail content
		$mail_content = $this->mergeKeywords($config->mail_content, $obj);
		$mail_content = $this->mergeKeywords($mail_content, $module_info);
/*
		// get document info.
		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);
		debugPrint('oDocument : ' . serialize($oDocument));
*/
		$tmp_obj->article_url = getFullUrl('','document_srl', $obj->document_srl);
		$tmp_content = $this->mergeKeywords($mail_content, $tmp_obj);
		$tmp_message = $this->mergeKeywords($sms_message, $tmp_obj);

		// 기존 $obj->title 을 $obj 으로 변경 
		$this->sendMessages($tmp_message, $tmp_content, $obj, $sender, $config);
	}

	/**
	 * @brief trigger for document insertion.
	 * @param $obj : document object.
	 **/
	function triggerInsertDocument(&$obj) 
	{
		$oMemberModel = &getModel('member');
		$oModel = &getModel('newposts');

		// if module_srl not set, just return with success;
		if (!$obj->module_srl) return;

		// if module_srl is wrong, just return with success
		$args->module_srl = $obj->module_srl;
		$output = executeQuery('module.getMidInfo', $args);
		if (!$output->toBool() || !$output->data) return;

		$module_info = $output->data;
		unset($args);
		if (!$module_info) return; 

		// check login.
		$sender = new StdClass();
		$sender->nick_name = $obj->nick_name;
		$sender->email_address = $obj->email_address;
		$logged_info = Context::get('logged_info');
		if ($logged_info) $sender = $logged_info; 

		// get configuration info. no configuration? just return.
		$config_list = $oModel->getConfigListByModuleSrl($obj->module_srl);
		if (!$config_list) return; 

		foreach ($config_list as $key=>$val) 
		{
			$this->processNewposts($val,$obj,$sender,$module_info);
		}
	}
}
?>
