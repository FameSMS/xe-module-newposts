<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsModel
 * @author NURIGO (Contact@nurigo.net)
 * @brief  newpostsModel
 */

class newpostsModel extends newposts 
{

	/**
	 * @brief constructor
	 *
	 */
	function init() 
	{
	}

	/**
	 * @brief get newposts module config
	 *
	 **/
	function getModuleConfig() 
	{
		if (!$GLOBALS['__newposts_config__']) 
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('newposts');
			$GLOBALS['__newposts_config__'] = $config;
		}
		return $GLOBALS['__newposts_config__'];
	}

	/**
	 * @brief get config list by module srl
	 *
	 **/
	function getConfigListByModuleSrl($module_srl) 
	{
		if (!$module_srl) return false;
		$args->module_srl = $module_srl;
		$output = executeQueryArray("newposts.getConfigByModuleSrl", $args);
		if (!$output->toBool() || !$output->data) return false;
		$config_list = $output->data;

		foreach($config_list as $key=>$val)
		{
			$extra_vars = unserialize($val->extra_vars);
			if ($extra_vars) 
			{
				foreach ($extra_vars as $key2 => $val2) 
				{
					$config_list[$key]->{$key2} = $val2;
				}
			}
		}
		return $config_list;
	}

	/**
	 * @brief get registered sender ids
	 */
	function getRegisteredSenderIds()
	{
		$coolsms = $this->__getCoolsms();
		$coolsms->get_senderid_list();
		$result = $coolsms->getResult();
		return $result;
	}

	/**
	 * @brief get coolsms object
	 */
	function __getCoolsms()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'login required');
		$user_id = $logged_info->user_id;
		$password = $logged_info->password;

		require "coolsms.php";
		return new Coolsms($user_id, $password, true);
	}
}
/* End of file newposts.model.php */
/* Location: ./modules/newposts/newposts.model.php */
