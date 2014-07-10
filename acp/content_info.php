<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\acp;

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package module_install
*/
class content_info
{
	function module()
	{
		return array(
			'filename'	=> '\primetime\content\acp\content_module',
			'title'		=> 'ACP_CONTENT',
			'parent'	=> 'ACP_MOD_MANAGEMENT',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'content'		=> array('title' => 'CONTENT', 'auth' => '', 'cat' => array('ACP_CONTENT')),
			),
		);
	}
}

