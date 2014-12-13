<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\services\views;

abstract class view implements views_interface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\cache\service */
	protected $cache;

	/** @var \phpbb\config\db */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/* @var \primetime\content\services\displayer */
	protected $displayer;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth							$auth				Auth object
	 * @param \phpbb\cache\service						$cache				Cache object
	 * @param \phpbb\config\db							$config				Config object
	 * @param \phpbb\db\driver\driver_interface			$db					Database object
	 * @param \phpbb\template\template					$template			Template object
	 * @param \phpbb\user								$user				User object
	 * @param \primetime\content\services\displayer		$displayer			Content displayer object
	 * @param string									$root_path			Path to the phpbb includes directory.
	 * @param string									$php_ext			php file extension
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\cache\service $cache, \phpbb\config\db $config, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \primetime\content\services\displayer $displayer, $root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->displayer = $displayer;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->icons = $this->cache->obtain_icons();
	}

	public function get_detail_template()
	{
		return 'content_show.html';
	}

	public function customize_view(&$sql_topics_count, &$sql_topics_data, &$type_data, &$limit)
	{
	}

	public function get_total_topics($forum_id, $sql_array)
	{
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		$total_topics = $this->db->sql_fetchfield('total_topics');
		$this->db->sql_freeresult($result);

		return $total_topics;
	}

	public function display_topics($type, $topics_data, $posts_data, $users_cache, $topic_tracking_info = array())
	{
		$topics_data = array_values($topics_data);
		for ($i = 0, $size = sizeof($topics_data); $i < $size; $i++)
		{
			$topic_data	= $topics_data[$i];
			$topic_id	= $topic_data['topic_id'];
			$poster_id	= $topic_data['topic_poster'];
			$post_data	= array_shift($posts_data[$topic_id]);
			$title		= censor_text($topic_data['topic_title']);

			$tpl_data = $this->get_common_template_data($topic_data, $post_data, $topic_tracking_info);
			$tpl_data += $this->displayer->show($type, $title, $topic_data, $post_data, $users_cache[$poster_id], $topic_tracking_info);

			$this->template->assign_block_vars('topic_row', $tpl_data);
			unset($topics_data[$i], $post_data[$topic_id]);
		}
	}

	public function show_topic($topic_title, $type, $topic_data, $post_data, $user_cache, $topic_tracking_info = array())
	{
		$max_post_time = 0;
		$update_count = array();
		$forum_id = $topic_data['forum_id'];
		$topic_id = $topic_data['topic_id'];
		$slug = $topic_data['topic_slug'];

		// Set max_post_time
		if ($post_data['post_time'] > $max_post_time)
		{
			$max_post_time = $post_data['post_time'];
		}

		$tpl_data = $this->get_common_template_data($topic_data, $post_data, $topic_tracking_info);
		$tpl_data += $this->get_detail_template_data($type, $topic_data, $post_data, $user_cache);
		$tpl_data += $this->displayer->show($type, $topic_title, $topic_data, $post_data, $user_cache, $topic_tracking_info);
		$this->template->assign_vars($tpl_data);

		// Update topic view and if necessary attachment view counters ... but only for humans and if this is the first 'page view'
		if (isset($this->user->data['session_page']) && !$this->user->data['is_bot'] && (strpos($this->user->data['session_page'], "content/$type/$topic_id/$slug") === false || isset($this->user->data['session_created'])))
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
				WHERE topic_id = $topic_id";
			$this->db->sql_query($sql);

			// Update the attachment download counts
			if (sizeof($update_count))
			{
				$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
					SET download_count = download_count + 1
					WHERE ' . $this->db->sql_in_set('attach_id', array_unique($update_count));
				$this->db->sql_query($sql);
			}
		}

		// Only mark topic if it's currently unread. Also make sure we do not set topic tracking back if earlier pages are viewed.
		if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id] && $max_post_time > $topic_tracking_info[$topic_id])
		{
			markread('topic', $forum_id, $topic_id, $max_post_time);

			// Update forum info
			update_forum_tracking_info($forum_id, $topic_data['forum_last_post_time'], (isset($topic_data['forum_mark_time'])) ? $topic_data['forum_mark_time'] : false, false);
		}

	}

	protected function get_common_template_data($topic_data, $post_data)
	{
		$row = &$post_data;
		$forum_id = $topic_data['forum_id'];
		$topic_id = $topic_data['topic_id'];
		$poster_id = $row['poster_id'];

		$display_notice = false;
		$post_edit_list = array();

		if (!$this->auth->acl_get('f_download', $forum_id))
		{
			$display_notice = true;
		}

		// this should be the url to content type
		$viewtopic_url = '';
		//
		return array(
			'POST_ICON_IMG'			=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $this->icons[$row['icon_id']]['img'] : '',
			'POST_ICON_IMG_WIDTH'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $this->icons[$row['icon_id']]['width'] : '',
			'POST_ICON_IMG_HEIGHT'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $this->icons[$row['icon_id']]['height'] : '',

			'POST_ID'				=> $row['post_id'],
			'POSTER_ID'				=> $poster_id,

			'S_HAS_ATTACHMENTS'			=> (!empty($attachments[$row['post_id']])) ? true : false,
			'S_MULTIPLE_ATTACHMENTS'	=> !empty($attachments[$row['post_id']]) && sizeof($attachments[$row['post_id']]) > 1,
			'S_DISPLAY_NOTICE'			=> $display_notice && $row['post_attachment'],
			'S_TOPIC_POSTER'			=> ($topic_data['topic_poster'] == $poster_id) ? true : false,
			'S_POST_UNAPPROVED'			=> ($row['post_visibility'] == ITEM_UNAPPROVED || $row['post_visibility'] == ITEM_REAPPROVE) ? true : false,
			'S_POST_REPORTED'			=> ($row['post_reported'] && $this->auth->acl_get('m_report', $forum_id)) ? true : false,
		);
	}

	protected function get_detail_template_data($type, $topic_data, $post_data, $user_cache)
	{
		$row = &$post_data;
		$forum_id = $row['forum_id'];
		$topic_id = $row['topic_id'];
		$poster_id = $row['poster_id'];

		if (($row['post_edit_count'] && $this->config['display_last_edited']) || $row['post_edit_reason'])
		{
			$this->show_edit_reason($row, $user_cache);
		}

		// Deleting information
		if ($row['post_visibility'] == ITEM_DELETED && $row['post_delete_user'])
		{
			$this->show_delete_reason($row, $user_cache);
		}

		$s_cannot_edit = !$this->auth->acl_get('f_edit', $forum_id) || $this->user->data['user_id'] != $poster_id;
		$s_cannot_edit_time = $this->config['edit_time'] && $row['post_time'] <= time() - ($this->config['edit_time'] * 60);
		$s_cannot_edit_locked = $topic_data['topic_status'] == ITEM_LOCKED || $row['post_edit_locked'];

		$s_cannot_delete = $this->user->data['user_id'] != $poster_id || (
			!$this->auth->acl_get('f_delete', $forum_id) &&
			(!$this->auth->acl_get('f_softdelete', $forum_id) || $row['post_visibility'] == ITEM_DELETED)
		);
		$s_cannot_delete_lastpost = $topic_data['topic_last_post_id'] != $row['post_id'];
		$s_cannot_delete_time = $this->config['delete_time'] && $row['post_time'] <= time() - ($this->config['delete_time'] * 60);
		// we do not want to allow removal of the last post if a moderator locked it!
		$s_cannot_delete_locked = $topic_data['topic_status'] == ITEM_LOCKED || $row['post_edit_locked'];

		$edit_url = '';
		if ($this->user->data['is_registered'] && ($this->auth->acl_get('m_edit', $forum_id) || (
			!$s_cannot_edit &&
			!$s_cannot_edit_time &&
			!$s_cannot_edit_locked)))
		{
			$class = ($this->user->data['user_id'] == $poster_id) ? 'ucp' : 'mcp';
			$edit_url = append_sid("{$this->root_path}{$class}.$this->php_ext", "i=-primetime-content-{$class}-content_module&mode=content&action=edit&type={$type}&amp;t={$topic_id}");
		}

		$delete_allowed = $this->user->data['is_registered'] && (
			($this->auth->acl_get('m_delete', $forum_id) || ($this->auth->acl_get('m_softdelete', $forum_id) && $row['post_visibility'] != ITEM_DELETED)) ||
			(!$s_cannot_delete && !$s_cannot_delete_lastpost && !$s_cannot_delete_time && !$s_cannot_delete_locked)
		);

		$viewtopic_url = '';
		//
		return array(
			'S_POST_DELETED'		=> ($row['post_visibility'] == ITEM_DELETED) ? true : false,
			//'S_FRIEND'				=> ($row['friend']) ? true : false,
			//'S_IGNORE_POST'			=> ($row['foe']) ? true : false,
			//'S_POST_HIDDEN'			=> $row['hide_post'],
			//'L_IGNORE_POST'			=> ($row['foe']) ? sprintf($this->user->lang['POST_BY_FOE'], get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username'])) : '',
			//'L_POST_DISPLAY'		=> ($row['hide_post']) ? $this->user->lang('POST_DISPLAY', '<a class="display_post" data-post-id="' . $row['post_id'] . '" href="' . $viewtopic_url . "&amp;p={$row['post_id']}&amp;view=show#p{$row['post_id']}" . '">', '</a>') : '',

			'U_EDIT'				=> $edit_url,
			'U_INFO'				=> ($this->auth->acl_get('m_info', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", "i=main&amp;mode=post_details&amp;f=$forum_id&amp;p=" . $row['post_id'], true, $this->user->session_id) : '',
			'U_DELETE'				=> ($delete_allowed) ? append_sid("{$this->root_path}posting.$this->php_ext", "mode=delete&amp;f=$forum_id&amp;p={$row['post_id']}") : '',

			'U_APPROVE_ACTION'		=> append_sid("{$this->root_path}mcp.$this->php_ext", "i=queue&amp;p={$row['post_id']}&amp;f=$forum_id&amp;redirect=" . urlencode(str_replace('&amp;', '&', $viewtopic_url . '&amp;p=' . $row['post_id'] . '#p' . $row['post_id']))),
			'U_REPORT'				=> ($this->auth->acl_get('f_report', $forum_id)) ? append_sid("{$this->root_path}report.$this->php_ext", 'f=' . $forum_id . '&amp;p=' . $row['post_id']) : '',
			'U_MCP_REPORT'			=> ($this->auth->acl_get('m_report', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=reports&amp;mode=report_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
			'U_MCP_APPROVE'			=> ($this->auth->acl_get('m_approve', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=queue&amp;mode=approve_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
			'U_MCP_RESTORE'			=> ($this->auth->acl_get('m_approve', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=queue&amp;mode=' . (($topic_data['topic_visibility'] != ITEM_DELETED) ? 'deleted_posts' : 'deleted_topics') . '&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
			'U_MINI_POST'			=> append_sid("{$this->root_path}viewtopic.$this->php_ext", 'p=' . $row['post_id']) . '#p' . $row['post_id'],
			'U_NOTES'				=> ($this->auth->acl_getf_global('m_')) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=notes&amp;mode=user_notes&amp;u=' . $poster_id, true, $this->user->session_id) : '',
			'U_WARN'				=> ($this->auth->acl_get('m_warn') && $poster_id != $this->user->data['user_id'] && $poster_id != ANONYMOUS) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=warn&amp;mode=warn_post&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
		);
	}

	protected function show_edit_reason($row, $user_cache)
	{
		$display_username = get_username_string('full', $row['post_edit_user'], $user_cache[$row['post_edit_user']]['username'], $user_cache[$row['post_edit_user']]['user_colour'], $user_cache[$row['post_edit_user']]['post_username']);
		$l_edited_by = $this->user->lang('EDITED_TIMES_TOTAL', (int) $row['post_edit_count'], $display_username, $this->user->format_date($row['post_edit_time'], false, true));

		$this->template->assign_vars(array(
			'EDITED_MESSAGE'	=> $l_edited_by,
			'EDIT_REASON'		=> $row['post_edit_reason']
		));
	}

	protected function show_delete_reason($row, $user_cache)
	{
		$display_username = get_username_string('full', $row['post_delete_user'], $user_cache[$row['post_delete_user']]['username'], $user_cache[$row['post_delete_user']]['user_colour']);

		if ($row['post_delete_reason'])
		{
			$l_deleted_message = $this->user->lang('POST_DELETED_BY_REASON', $display_postername, $display_username, $this->user->format_date($row['post_delete_time'], false, true), $row['post_delete_reason']);
		}
		else
		{
			$l_deleted_message = $this->user->lang('POST_DELETED_BY', $display_postername, $display_username, $this->user->format_date($row['post_delete_time'], false, true));
		}
		$l_deleted_by = $this->user->lang('DELETED_INFORMATION', $display_username, $this->user->format_date($row['post_delete_time'], false, true));

		$this->template->assign_vars(array(
			'DELETED_MESSAGE'			=> $l_deleted_by,
			'DELETE_REASON'				=> $row['post_delete_reason'],
			'L_POST_DELETED_MESSAGE'	=> $l_deleted_message
		));
	}
}