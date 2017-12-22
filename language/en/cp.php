<?php
/**
 *
 * @package phpBB Sitemaker [English]
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'CONTENT_NEW'						=> 'New Content',
	'CONTENT_PUBLISHED'					=> 'Published',
	'CONTENT_TITLE'						=> 'Title',
	'CONTENT_TYPE'						=> 'Content Type',
	'CONTENT_UPDATED'					=> 'The topic was successfully saved',

	'GOTO_TOPIC'						=> 'Go to topic',

	'INVALID_REQUEST'					=> 'Invalid request: %s',

	'MAKE_NORMAL'						=> 'Approve',
	'MANAGE_CONTENT'					=> 'Manage Content',
	'MCP_MAKE_ANNOUNCEMENT'				=> 'Modify to “Recommended”',
	'MCP_MAKE_ANNOUNCEMENTS'			=> 'Modify to “Recommended”',
	'MCP_MAKE_ANNOUNCEMENTS_CONFIRM'	=> 'Are you sure you want to change the selected topics to “Recommended”?',
	'MCP_MAKE_ANNOUNCEMENT_CONFIRM'		=> 'Are you sure you want to change this topic to “Recommended”?',
	'MCP_MAKE_GLOBAL'					=> 'Modify to “Must Read”',
	'MCP_MAKE_GLOBALS'					=> 'Modify to “Must Read”',
	'MCP_MAKE_GLOBALS_CONFIRM'			=> 'Are you sure you want to change the selected topics to a “Must Read”?',
	'MCP_MAKE_GLOBAL_CONFIRM'			=> 'Are you sure you want to change this topic to a “Must Read”?',
	'MCP_MAKE_NORMAL'					=> 'Modify to “Standard Topic”',
	'MCP_MAKE_NORMALS'					=> 'Modify to “Standard Topics”',
	'MCP_MAKE_NORMALS_CONFIRM'			=> 'Are you sure you want to change the selected topics to “Standard Topics”?',
	'MCP_MAKE_NORMAL_CONFIRM'			=> 'Are you sure you want to change this topic to a “Standard Topic”?',
	'MCP_MAKE_STICKIES'					=> 'Modify to “Featured”',
	'MCP_MAKE_STICKIES_CONFIRM'			=> 'Are you sure you want to change the selected topics to “Featured”?',
	'MCP_MAKE_STICKY'					=> 'Modify to “Featured”',
	'MCP_MAKE_STICKY_CONFIRM'			=> 'Are you sure you want to change this topic to “Featured”?',

	'NO_TOPICS_ALL'			=> 'There are no topics to display',
	'NO_TOPICS_DELETED'		=> 'There are no deleted topics to display',
	'NO_TOPICS_FEATURED'	=> 'There are no featured topics to display',
	'NO_TOPICS_MUST_READ'	=> 'Thera are no must-read topics to display',
	'NO_TOPICS_PUBLISHED'	=> 'There are no published topics to display',
	'NO_TOPICS_RECOMMENDED'	=> 'There are no recommended topics to display',
	'NO_TOPICS_SCHEDULED'	=> 'There are no scheduled topics to display',
	'NO_TOPICS_UNAPPROVED'	=> 'There are no unapproved topics to display',

	'TOPIC_ALL'				=> 'All',
	'TOPIC_DELETED'			=> 'Deleted',
	'TOPIC_FEATURED'		=> 'Featured',
	'TOPIC_MUST_READ'		=> 'Must Read',
	'TOPIC_PUBLISHED'		=> 'Published',
	'TOPIC_RECOMMENDED'		=> 'Recommended',
	'TOPIC_SCHEDULED'		=> 'Scheduled',
	'TOPIC_STATUS'			=> 'Topic Status',
	'TOPIC_TITLE'			=> 'Topic Title',
	'TOPIC_UNAPPROVED'		=> 'Unapproved',
));