<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\services;

use Symfony\Component\DependencyInjection\Container;

class displayer extends types
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\db */
	protected $config;

	/** @var \phpbb\content_visibility */
	protected $content_visibility;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var Container */
	protected $phpbb_container;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \Twig_Environment */
	protected $twig;

	/** @var \phpbb\user */
	protected $user;

	/** @var \primetime\content\services\comments */
	protected $comments;

	/** @var \primetime\content\services\form */
	protected $form;

	/** @var array */
	protected $form_fields;

	/** @var string */
	protected $root_path;

	/** @var array */
	protected $tags;

	/** @var array */
	protected $type_data;

	/** @var array */
	protected $type_fields;

	/** @var bool */
	protected $allow_comments = false;

	/** @var string */
	protected $tpl_name = '';

	/** @var string */
	protected $view = '';

	/**
	 * Construct
	 *
	 * @param \phpbb\auth\auth								$auth					Auth object
	 * @param \phpbb\cache\service							$cache					Cache object
	 * @param \phpbb\config\db								$config					Config object
	 * @param \phpbb\content_visibility						$content_visibility		Content visibility object
	 * @param \phpbb\db\driver\driver_interface				$db						Database connection
	 * @param \phpbb\controller\helper						$helper					Helper object
	 * @param Container										$phpbb_container		Service container
	 * @param \phpbb\template\template						$template				Template object
	 * @param \phpbb\user									$user					User object
	 * @param \primetime\content\services\comments			$comments				Comments object
	 * @param \primetime\content\services\form				$form					Form object
	 * @param string										$root_path				phpBB root path
	 * @param string										$fields_table			Name of content fields database table
	 * @param string										$types_table			Name of content types database table
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\cache\service $cache, \phpbb\config\db $config, \phpbb\content_visibility $content_visibility, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, Container $phpbb_container, \phpbb\template\template $template, \phpbb\user $user, \primetime\content\services\comments $comments, \primetime\content\services\form $form, $root_path, $fields_table, $types_table)
	{
		parent::__construct($cache, $config, $db, $fields_table, $types_table);

		$this->auth					= $auth;
		$this->config				= $config;
		$this->content_visibility	= $content_visibility;
		$this->helper				= $helper;
		$this->phpbb_container		= $phpbb_container;
		$this->template				= $template;
		$this->user					= $user;
		$this->comments				= $comments;
		$this->form					= $form;
		$this->root_path			= $root_path;
	}

	/**
	 * Set type data needed to display posts
	 */
	public function prepare_to_show($type, $view, $fields, $custom_tpl = '', $tpl_name = '', $force_max_chars = '')
	{
		$this->tags = array();
		$this->type_fields = array();
		$this->form_fields = array();
		$this->tpl_name = '';
		$this->type_data = $this->get_type($type);

		$fields_data = $this->type_data['content_fields'];

		if ($this->type_data['allow_comments'])
		{
			$this->template->assign_var('S_COMMENTS', true);
			$this->allow_comments = true;
		}

		if (!empty($custom_tpl))
		{
			$this->twig		= $this->get_twig();
			$this->tpl_name	= ($tpl_name) ? $tpl_name : $type . '_' . $view;
		}

		$this->view = ($view == 'summary' || $view == 'detail') ? $view : 'summary';
		$this->form_fields = array_intersect_key($this->form->get_form_fields(), array_flip($fields));

		foreach ($fields as $name => $type)
		{
			if (isset($this->form_fields[$type]))
			{
				if ($force_max_chars && $type == 'textarea')
				{
					$fields_data[$name]['max_chars'] = $force_max_chars;
				}

				$this->tags[] = $name;
				$this->type_fields[$name] = $fields_data[$name];
			}
		}
		unset($fields, $fields_data);
	}

	/**
	 * Get post
	 */
	public function show($type, $topic_title, $topic_data, $post_data, $user_cache, $attachments, &$update_count, $topic_tracking_info = array(), $page = 1)
	{
		$row = $topic_data;
		$forum_id = $row['forum_id'];
		$topic_id = $row['topic_id'];
		$post_id = $post_data['post_id'];

		if (!empty($attachments[$post_id]))
		{
			parse_attachments($forum_id, $post_data['post_text'], $attachments[$post_id], $update_count);
		}

		$label_class = array('label-hidden', 'label-inline', 'label-newline');
		$post_field_data = $this->get_fields_data_from_post($post_data['post_text'], $this->tags);
		$post_unread = (isset($topic_tracking_info[$topic_id]) && $row['topic_last_post_time'] > $topic_tracking_info[$topic_id]) ? true : false;

		$topic_url = $this->helper->route('primetime_content_show', array(
			'type'		=> $type,
			'topic_id'	=> $topic_id,
			'slug'		=> $topic_data['topic_slug']
		));

		$topic_row = array(
			'MINI_POST_IMG'			=> ($post_unread) ? $this->user->img('icon_post_target_unread', 'UNREAD_POST') : $this->user->img('icon_post_target', 'POST'),
			'TOPIC_AUTHOR'			=> $user_cache['username'],
			'TOPIC_AUTHOR_COLOUR'	=> $user_cache['user_colour'],
			'TOPIC_AUTHOR_FULL'		=> $user_cache['username_full'],
			'TOPIC_AUTHOR_URL'		=> $user_cache['user_profile'],
			'TOPIC_AUTHOR_AVATAR'	=> $user_cache['avatar'],

			'S_UNREAD_POST'			=> $post_unread,

			'TOPIC_TITLE'			=> $topic_title,
			'TOPIC_COMMENTS'		=> ($this->allow_comments) ? $this->comments->count($topic_data) : '',
			'TOPIC_DATE'			=> $this->user->format_date($row['topic_time']),
			'TOPIC_URL'				=> $topic_url,
		);

		$fields_data = array();
		foreach ($this->type_fields as $field_name => $row)
		{
			$field_type		= $row['field_type'];
			$field_value	= &$post_field_data[$field_name];

			$field_contents	= $this->form_fields[$field_type]->display_field($field_value, $row, $this->view, $topic_id);

			if (!$field_contents)
			{
				continue;
			}
			else if ($field_type == 'textarea' && $row['size'] == 'large' && strpos($field_contents, '<!-- PAGE') !== false)
			{
				if (preg_match_all("#<!-- PAGE(.*?)? -->(.*?)<!-- ENDPAGE -->#s", $field_contents, $matches))
				{
					if (sizeof($matches[2]))
					{
						$total_pages = sizeof($matches[2]);
						$start = (($page > $total_pages) ? $total_pages - 1 : (($page < 1) ? 1 : $page)) - 1;
						$field_contents = $matches[2][$start];

						// Generate pagination
						if ($this->view == 'detail')
						{
							$this->phpbb_container->get('pagination')->generate_template_pagination(
								array(
									'routes' => array(
										'primetime_content_show',
										'primetime_content_show_page',
									),
									'params' => array(
										'type'		=> $type,
										'topic_id'	=> $topic_id,
										'slug'		=> $topic_data['topic_slug']
									),
								),
								'page', 'page', $total_pages, 1, $start);

							// Generate TOC
							if (sizeof(array_filter($matches[1])))
							{
								foreach ($matches[1] as $pg => $title)
								{
									$title = ($title) ? $title : $this->user->lang['CONTENT_TOC_UNTITLED'];
									$this->template->assign_block_vars('toc', array(
										'TITLE'		=> $title,
										'S_PAGE'	=> ($pg == $start) ? true : false,
										'U_VIEW'	=> $topic_url . (($pg > 0) ? '/page/' . ($pg + 1) : ''),
									));
								}
							}

							// When Previewing
							if (!empty($post_data['preview']))
							{
								for ($i = 1, $size = sizeof($matches[2]); $i < $size; $i++)
								{
									$this->template->assign_block_vars('pages', array(
										'CONTENT'	=> $matches[2][$i],
										'PAGE'		=> $i + 1,
									));
								}
							}

							// Hide all other fields if we're looking at page 2+
							if ($page > 1)
							{
								$fields_data = array(
									$field_name	=> $field_contents
								);
								break;
							}
						}
					}
				}
			}

			$fields_data[$field_name] = '<div class="field-label ' . $label_class[$row['field_' . $this->view . '_ldisp']] . '">' . $row['field_label'] . ': </div>' . $field_contents;
		}

		$topic_row = array_change_key_case(array_merge($topic_row, $fields_data), CASE_UPPER);

		if ($this->tpl_name)
		{
			$topic_row['CUSTOM_DISPLAY'] = $this->twig->render($this->tpl_name, $topic_row);
		}
		else
		{
			$topic_row['SEQ_DISPLAY'] = join('', $fields_data);
		}
		unset($fields_data, $post_field_data, $topic_data, $post_data, $row);

		return $topic_row;
	}

	public function show_edit_reason($row, $users_cache)
	{
		$l_edited_by = $edit_reason = '';
		if (($row['post_edit_count'] && $this->config['display_last_edited']) || $row['post_edit_reason'])
		{
			$display_username	= $users_cache[$row['poster_id']]['username_full'];
			$l_edited_by = $this->user->lang('EDITED_TIMES_TOTAL', (int) $row['post_edit_count'], $display_username, $this->user->format_date($row['post_edit_time'], false, true));
			$edit_reason = $row['post_edit_reason'];
		}

		return array(
			'EDITED_MESSAGE'	=> $l_edited_by,
			'EDIT_REASON'		=> $edit_reason,
		);
	}

	public function show_delete_reason($row, $users_cache)
	{
		$l_deleted_by = $delete_reason = $l_deleted_message = '';
		if ($row['post_visibility'] == ITEM_DELETED && $row['post_delete_user'])
		{
			$display_postername	= $users_cache[$row['poster_id']]['username_full'];
			$display_username	= $users_cache[$row['post_delete_user']]['username_full'];

			if ($row['post_delete_reason'])
			{
				$l_deleted_message = $this->user->lang('POST_DELETED_BY_REASON', $display_postername, $display_username, $this->user->format_date($row['post_delete_time'], false, true), $row['post_delete_reason']);
			}
			else
			{
				$l_deleted_message = $this->user->lang('POST_DELETED_BY', $display_postername, $display_username, $this->user->format_date($row['post_delete_time'], false, true));
			}

			$l_deleted_by = $this->user->lang('DELETED_INFORMATION', $display_username, $this->user->format_date($row['post_delete_time'], false, true));
			$delete_reason = $row['post_delete_reason'];
		}

		return array(
			'DELETED_MESSAGE'			=> $l_deleted_by,
			'DELETE_REASON'				=> $delete_reason,
			'L_POST_DELETED_MESSAGE'	=> $l_deleted_message
		);
	}

	public function get_twig()
	{
		$twig = new \Twig_Environment(
			$this->phpbb_container->get('primetime.content.loader'),
			array(
				'cache'			=> $this->root_path . 'cache/twig/',
				'debug'			=> defined('DEBUG'),
				'auto_reload'	=> (bool) $this->config['load_tplcompile'],
				'autoescape'	=> false,
			)
		);

		$twig->addExtension(
			new \phpbb\template\twig\extension(
				$this->phpbb_container->get('template_context'),
				$this->user
			)
		);

		$lexer = new \phpbb\template\twig\lexer($twig);
		$twig->setLexer($lexer);

		return $twig;
	}
}
