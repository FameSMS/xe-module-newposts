<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  newpostsView
 * @author NURIGO (Contact@nurigo.net)
 * @brief  newpostsView
 */
class newpostsView extends newposts 
{
	/**
	 * @brief constructor 
	 *
	 **/
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}
}
/* End of file newposts.view.php */
/* Location: ./modules/newposts/newposts.view.php */
