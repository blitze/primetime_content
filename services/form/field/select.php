<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\services\form\field;

class select extends choice
{
	/** @var \phpbb\request\request_interface */
	protected $request;

	/* @var \phpbb\user */
	protected $user;

	/** @var \primetime\core\services\template */
	protected $ptemplate;

	/**
	 * Constructor
	 *
	 * @param \phpbb\request\request_interface		$request		Request object
	 * @param \phpbb\user							$user			User object
	 * @param \primetime\core\services\template		$ptemplate		Primetime template object
	 */
	public function __construct(\phpbb\request\request_interface $request, \phpbb\user $user, \primetime\core\services\template $ptemplate)
	{
		parent::__construct($user, $ptemplate);

		$this->request = $request;
	}

	/**
	 * @inheritdoc
	 */
	public function get_field_value($name, $value)
	{
		return $this->request->variable($name, $value, true);
	}

	/**
	 * @inheritdoc
	 */
	public function get_name()
	{
		return 'select';
	}
}
