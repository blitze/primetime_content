<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\content\migrations\v20x;

/**
 * Initial schema changes needed for Extension installation
 */
class m2_initial_data extends \phpbb\db\migration\container_aware_migration
{
	/**
	 * @inheritdoc
	 */
	static public function depends_on()
	{
		return array(
			'\blitze\content\migrations\v20x\m1_initial_schema',
			'\blitze\content\migrations\converter\c1_update_config',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'create_forum'))),
			array('custom', array(array($this, 'create_bbcodes'))),
			array('config.add', array('blitze_content_forum_id', 0)),
		);
	}

	/**
	 *
	 */
	public function create_forum()
	{
		$forum = $this->container->get('blitze.sitemaker.forum.manager');

		$forum_data = array(
			'forum_type'	=> FORUM_CAT,
			'forum_name'	=> 'Sitemaker Content',
		);

		if (!empty($this->config['blitze_content_forum_id']))
		{
			$forum_data['forum_id'] = (int) $this->config['blitze_content_forum_id'];
		}

		$errors = $forum->add($forum_data);

		if (!sizeof($errors))
		{
			$forum_id = (int) $forum_data['forum_id'];
			$this->config->set('blitze_content_forum_id', $forum_id);
		}
	}

	/**
	 *
	 */
	public function create_bbcodes()
	{
		if (!class_exists('acp_bbcodes'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_bbcodes.' . $this->php_ext);
		}

		$bbcodes_ary = array(
			array(
				'match'		=> '[tag={IDENTIFIER}]{TEXT}[/tag]',
				'template'	=> "<!-- begin field -->\n" .
							"<h4>{IDENTIFIER}</h4><br />\n" .
							"<div data-field=\"{IDENTIFIER}\">{TEXT}</div><br />\n" .
							"<!-- end field -->\n",
			),
			array(
				'match'		=> '[page={SIMPLETEXT}]{TEXT}[/page]',
				'template'	=> "<!-- begin page -->\n" .
							"<i>{SIMPLETEXT}</i>\n" .
							"<div data-page=\"{SIMPLETEXT}\">{TEXT}</div><br />\n" .
							"<!-- end page -->\n",
			),
		);

		$sql = 'SELECT bbcode_id, bbcode_tag
			FROM ' . BBCODES_TABLE . '
			ORDER BY bbcode_id ASC';
		$result = $this->db->sql_query($sql);

		$max_bbcode_id = NUM_CORE_BBCODES;
		$current_bbcodes = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$max_bbcode_id = (int) $row['bbcode_id'];
			$current_bbcodes[$row['bbcode_tag']] = $row['bbcode_id'];
		}
		$this->db->sql_freeresult($result);

		// Make sure max_bbcode_id is not less than the core bbcode ids...
		if ($max_bbcode_id < NUM_CORE_BBCODES)
		{
			$max_bbcode_id = NUM_CORE_BBCODES;
		}

		$bbcode_manager = new \acp_bbcodes();

		foreach ($bbcodes_ary as $bbcode)
		{
			$data = $bbcode_manager->build_regexp($bbcode['match'], $bbcode['template']);

			$sql_ary = array(
				'bbcode_tag'				=> $data['bbcode_tag'],
				'bbcode_match'				=> $bbcode['match'],
				'bbcode_tpl'				=> $bbcode['template'],
				'display_on_posting'		=> false,
				'bbcode_helpline'			=> '',
				'first_pass_match'			=> $data['first_pass_match'],
				'first_pass_replace'		=> $data['first_pass_replace'],
				'second_pass_match'			=> $data['second_pass_match'],
				'second_pass_replace'		=> $data['second_pass_replace']
			);

			// Does this bbcode already exist?
			if (isset($current_bbcodes[$sql_ary['bbcode_tag']]))
			{
				$this->db->sql_query('UPDATE ' . BBCODES_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE bbcode_id = ' . (int) $current_bbcodes[$sql_ary['bbcode_tag']]);
			}
			else
			{
				$sql_ary['bbcode_id'] = ++$max_bbcode_id;

				$this->db->sql_query('INSERT INTO ' . BBCODES_TABLE . $this->db->sql_build_array('INSERT', $sql_ary));
			}
		}

		$this->container->get('cache.driver')->destroy('sql', BBCODES_TABLE);
	}
}
