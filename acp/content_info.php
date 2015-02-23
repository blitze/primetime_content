<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\acp;

class content_info
{
	public function module()
	{
		return array(
			'filename'	=> '\primetime\content\acp\content_module',
			'title'		=> 'ACP_CONTENT',
			'parent'	=> 'ACP_MOD_MANAGEMENT',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'content'		=> array('title' => 'CONTENT_TYPES', 'auth' => 'ext_primetime/content', 'cat' => array('ACP_CONTENT')),
			),
		);
	}
}
