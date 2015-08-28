<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsAdminModel
 * @author NURIGO (Contact@nurigo.net)
 * @brief  newpostsAdminModel
 */
class newpostsAdminModel extends newposts 
{
	/**
	 * @brief get templates for deleting config
	 *
	 **/
	function getNewpostsAdminDelete() 
	{
		// get configs.
		$args->config_srl = Context::get('config_srl');
		$output = executeQuery("newposts.getConfig", $args);
		if(!$output->toBool()) return $output;
		$config = $output->data;
		$output = executeQueryArray("newposts.getModuleInfoByConfigSrl", $args);
		if(!$output->toBool()) return $output;
		$mid_list = array();
		if ($output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$mid_list[] = $val->mid;
			}
		}
		$config->mid_list = join(',', $mid_list);
		Context::set('config', $config);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'delete');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}
/* End of file newposts.admin.model.php */
/* Location: ./modules/newposts/newposts.admin.model.php */
