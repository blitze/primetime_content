<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\content\services\views;

class tiles extends base_view
{
	/** @var \phpbb\request\request_interface */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language					$language			Language Object
	 * @param \phpbb\pagination							$pagination			Pagination object
	 * @param \phpbb\request\request_interface			$request			Request object
	 * @param \phpbb\template\template					$template			Template object
	 * @param \blitze\content\services\fields			$fields				Content fields object
	 * @param \blitze\sitemaker\services\forum\data		$forum				Forum Data object
	 * @param \blitze\content\services\helper			$helper				Content helper object
	 * @param string									$phpbb_root_path	Path to the phpbb includes directory.
	 * @param string									$php_ext			php file extension
	*/
	public function __construct(\phpbb\language\language $language, \phpbb\pagination $pagination, \phpbb\request\request_interface $request, \phpbb\template\template $template, \blitze\content\services\fields $fields, \blitze\sitemaker\services\forum\data $forum, \blitze\content\services\helper $helper, $phpbb_root_path, $php_ext)
	{
		parent::__construct($language, $pagination, $template, $fields, $forum, $helper, $phpbb_root_path, $php_ext);

		$this->request = $request;
	}

	/**
	 * @inheritdoc
	 */
	public function get_name()
	{
		return 'tiles';
	}

	/**
	 * @inheritdoc
	 */
	public function get_langname()
	{
		return 'CONTENT_DISPLAY_TILES';
	}

	/**
	 * @inheritdoc
	 */
	public function get_index_template()
	{
		return 'views/content_tiles.html';
	}

	/**
	 * @inheritdoc
	 */
	public function render_index(\blitze\content\model\entity\type $entity, $page, $filter_type, $filter_value)
	{
		parent::render_index($entity, $page, $filter_type, $filter_value);

		if ($this->request->is_ajax())
		{
			$this->template->assign_var('S_HIDE_HEADERS', true);
		}
	}
}
