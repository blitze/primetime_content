<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\content\services\form\field;

use Urodoz\Truncate\TruncateService;

class textarea extends base
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\template\context */
	protected $template_context;

	/** @var \blitze\sitemaker\services\util */
	protected $util;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language					$language			Language object
	 * @param \phpbb\request\request_interface			$request			Request object
	 * @param \blitze\sitemaker\services\template		$ptemplate			Sitemaker template object
	 * @param \phpbb\auth\auth							$auth				Auth object
	 * @param \phpbb\config\config						$config				Config object
	 * @param \phpbb\pagination							$pagination			Pagination object
	 * @param \phpbb\template\template					$template			Template object
	 * @param \phpbb\template\context					$template_context	Template context object
	 * @param \blitze\sitemaker\services\util			$util				Sitemaker utility object
	 * @param string									$phpbb_root_path	Path to the phpbb includes directory.
	 * @param string									$php_ext			php file extension
	 */
	public function __construct(\phpbb\language\language $language, \phpbb\request\request_interface $request, \blitze\sitemaker\services\template $ptemplate, \phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\pagination $pagination, \phpbb\template\template $template, \phpbb\template\context $template_context, \blitze\sitemaker\services\util $util, $phpbb_root_path, $php_ext)
	{
		parent::__construct($language, $request, $ptemplate);

		$this->auth = $auth;
		$this->config = $config;
		$this->pagination = $pagination;
		$this->template = $template;
		$this->template_context = $template_context;
		$this->util = $util;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * @inheritdoc
	 */
	public function get_name()
	{
		return 'textarea';
	}

	/**
	 * @inheritdoc
	 */
	public function get_default_props()
	{
		return array(
			'size'		=> 'large',
			'maxlength'	=> '',
			'max_chars'	=> 200,
			'editor'	=> true,
		);
	}

	/**
	 * Display content field
	 *
	 * @param array $data
	 * @param string $view_mode
	 * @param array $topic_data
	 * @return mixed
	 */
	public function display_field(array $data = array(), $view_mode = 'summary', array $topic_data = array())
	{
		$toc_pattern = '(<h4>(.*?)</h4>)?';
		$pages_pattern = '<p><!-- pagebreak --></p>';
		$split_pattern = $pages_pattern . (($view_mode !== 'detail') ? $toc_pattern : '');

		$pages = array_filter(preg_split('#' . $split_pattern . '#s', $data['field_value']));

		if ($view_mode !== 'detail')
		{
			return $this->get_summary_value(trim($pages[0]), $data['field_props']['max_chars']);
		}

		// get page titles to generate TOC
		preg_match_all('#' . $pages_pattern . $toc_pattern . '#s', $data['field_value'], $matches);

		return $this->get_detail_value($pages, $matches[2], $data);
	}

	/**
	 * @inheritdoc
	 */
	public function show_form_field($name, array &$data, $forum_id = 0)
	{
		if ($data['field_props']['editor'])
		{
			$asset_path = $this->util->get_web_path();
			$this->util->add_assets(array(
				'js'   => array(
					$asset_path . 'assets/javascript/editor.js',
					'@blitze_content/assets/form/textarea.min.js'
				)
			));

			$data += $this->get_editor($forum_id);
		}

		return parent::show_form_field($name, $data, $forum_id);
	}

	/**
	 * @param int $forum_id
	 * @return array
	 */
	protected function get_editor($forum_id)
	{
		// Assigning custom bbcodes
		if (!function_exists('display_custom_bbcodes'))
		{
			include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		}

		display_custom_bbcodes();

		$bbcode_status	= ($this->config['allow_bbcode'] && $this->auth->acl_get('f_bbcode', $forum_id)) ? true : false;

		$dataref = $this->template_context->get_data_ref();
		$this->ptemplate->assign_block_vars_array('custom_tags', (isset($dataref['custom_tags'])) ? $dataref['custom_tags'] : array());

		// HTML, BBCode, Smilies, Images and Flash statusf
		return array(
			'S_BBCODE_IMG'			=> ($bbcode_status && $this->auth->acl_get('f_img', $forum_id)) ? true : false,
			'S_LINKS_ALLOWED'		=> ($this->config['allow_post_links']) ? true : false,
			'S_BBCODE_FLASH'		=> ($bbcode_status && $this->auth->acl_get('f_flash', $forum_id) && $this->config['allow_post_flash']) ? true : false,
			'S_BBCODE_QUOTE'		=> false,
			'S_SMILIES_ALLOWED'		=> false,
		);
	}

	/**
	 * @param string $html
	 * @param int $max_chars
	 * @return string
	 */
	protected function get_summary_value($html, $max_chars)
	{
		if ($max_chars)
		{
			$truncateService = new TruncateService();
			$html = $truncateService->truncate($html, $max_chars);
		}
		return $html;
	}

	/**
	 * @param array $pages
	 * @param array $titles
	 * @param array $data
	 * @return mixed
	 */
	protected function get_detail_value(array $pages, array $titles, array $data)
	{
		if ($this->request->is_set('preview') || $this->request->variable('view', '') === 'print')
		{
			return join('<p><hr class="dashed"></p>', $pages);
		}

		$page = $this->request->variable('page', 0);

		$start = isset($pages[$page]) ? $page : 0;
		$total_pages = sizeof($pages);
		$topic_url = build_url(array('page'));

		$this->generate_page_nav($topic_url, $total_pages, $start);
		$this->generate_toc($start, $topic_url, array_slice($titles, 0, $total_pages - 1));

		return $this->get_page_content($start, $data['field_name'], $pages);
	}

	/**
	 * Generate pagination for topic subpages
	 *
	 * @param string $topic_url
	 * @param int $total_pages
	 * @param int $start
	 * @return void
	 */
	protected function generate_page_nav($topic_url, $total_pages, &$start)
	{
		$start = $this->pagination->validate_start($start, 1, $total_pages);
		$this->pagination->generate_template_pagination($topic_url, 'page', 'page', $total_pages, 1, $start);
		$this->template->assign_var('S_NOT_LAST_PAGE', !($start === ($total_pages - 1)));
	}

	/**
	 * Generate Table of contents
	 *
	 * @param int $start
	 * @param string $topic_url
	 * @param array $page_titles
	 * @return void
	 */
	protected function generate_toc($start, $topic_url, array $page_titles)
	{
		if (sizeof(array_filter($page_titles)))
		{
			$page_titles = array_merge(array($this->language->lang('CONTENT_TOC_OVERVIEW')), $page_titles);

			foreach ($page_titles as $page => $title)
			{
				$this->template->assign_block_vars('toc', array(
					'TITLE'		=> ($title) ? $title : $this->language->lang('CONTENT_TOC_UNTITLED'),
					'S_PAGE'	=> ($page === $start),
					'U_VIEW'	=> append_sid($topic_url, ($page) ? 'page=' . $page : false),
				));
			}
		}
	}

	/**
	 * When Previewing topic, we show all pages
	 *
	 * @param array $pages
	 * @return mixed
	 */
	protected function get_page_content($start, $field_name, array $pages)
	{
		$value = trim($pages[$start]);

		// Hide all other fields if we're looking at page 2+
		if ($start)
		{
			$value = array(
				$field_name => $value,
			);
		}

		return $value;
	}
}
