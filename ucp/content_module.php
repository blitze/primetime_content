<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\ucp;

class content_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $phpbb_container, $request, $phpbb_root_path, $phpEx, $template, $user;

		// Set up the page
		$this->tpl_name = 'cp_content';
		$this->page_title = 'UCP_CONTENT';
	}
}
