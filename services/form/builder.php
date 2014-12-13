<?php
/**
 *
 * @package primetime
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace primetime\content\services\form;

class builder
{
	/** @var \phpbb\template\context */
	protected $template_context;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\user */
	protected $user;

	/** @var \primetime\primetime\core\block_template */
	protected $ptemplate;

	protected $fields = array();
	protected $data = array();
	protected $form = array();
	public $is_valid = false;

	/**
	 * Constructor
	 *
	 * @param \phpbb\template\context					$context				Template context object
	 * @param \phpbb\request\request_interface			$request				Request object
	 * @param \phpbb\di\service_collection				$type_collection
	 * @param \phpbb\user								$user					User object
	 * @param \primetime\primetime\core\template		$ptemplate				Primetime template object
	 */
	public function __construct(\phpbb\request\request_interface $request, \phpbb\di\service_collection $field_drivers, \phpbb\template\context $template_context, \phpbb\user $user, \primetime\primetime\core\template $ptemplate)
	{
		$this->request = $request;
		$this->template_context = $template_context;
		$this->user = $user;
		$this->ptemplate = $ptemplate;
		$this->register_fields($field_drivers);

		$this->user->add_lang_ext('primetime/content', 'form');
	}

	protected function register_fields($field_drivers)
	{
		if (!empty($field_drivers))
		{
			foreach ($field_drivers as $service => $driver)
			{
				$this->fields[$driver->get_name()] = $driver;
			}
		}
	}

	public function add($name, $type, $field_data = array(), $item_id = 0)
	{
		$field_data += array('field_id' => 'field-' . $name);
		$field_data += $this->get_default_field_data();

		if (isset($this->fields[$type]))
		{
			$obj = $this->fields[$type];

			$field_data['field_type'] = $type;
			$field_data['field_view'] = $obj->render_view($name, $field_data, $item_id);

			$this->data[$name] = $field_data;
		}

		return $this;
	}

	public function create($form_name, $action, $legend = '', $method = 'post')
	{
		add_form_key($form_name);

		$rootref = $this->template_context->get_root_ref();

		$this->form = array(
			'form_name'		=> $form_name,
			'form_action'	=> $action,
			'form_legend'	=> $legend,
			'form_method'	=> $method,
			'form_key'		=> $rootref['S_FORM_TOKEN'],
		);
		unset($rootref);

		return $this;
	}

	public function get_form()
	{
		foreach ($this->data as $field => $row)
		{
			switch ($row['field_type'])
			{
				case 'submit':
				case 'reset':
					$key = 'button';
				break;
				case 'hidden':
					$key = 'hidden';
				break;
				case 'file':
				default:
					$key = 'element';
				break;
			}

			$this->ptemplate->assign_block_vars($key, array_change_key_case($row, CASE_UPPER));
		}

		$this->ptemplate->assign_vars(array_change_key_case($this->form, CASE_UPPER));

		return $this->ptemplate->render_view('primetime/content', 'form.html', 'form');
	}

	public function handle_request($request)
	{
		$field_values = array();
		if ($request->server('REQUEST_METHOD') === 'POST')
		{
			$errors = array();
			foreach ($this->data as $field => $row)
			{
				if ($row['field_required'] && $row['field_value'] == '')
				{
					$errors[] = sprintf($this->user->lang['FIELD_REQUIRED'], $row['field_label']);
				}
				else if ($row['field_value'])
				{
					$obj = $this->fields[$row['field_type']];
					$errors[] = $obj->validate_field($row);
				}

				if (!$row['requires_item_id'])
				{
					$field_values[$field] = $row['field_value'];
				}
			}

			if (!check_form_key($this->form['form_name']))
			{
				$error[] = 'FORM_INVALID';
			}

			$errors = array_filter($errors);

			if (!sizeof($errors))
			{
				$this->is_valid = true;
			}
			else
			{
				$this->is_valid = false;
				$this->ptemplate->assign_var('ERRORS', join('<br />', $errors));
			}
		}

		return $field_values;
	}

	public function save_fields($item_id)
	{
		if (!$item_id)
		{
			return false;
		}

		foreach ($this->data as $field => $row)
		{
			$obj = $this->fields[$row['field_type']];
			$obj->save_field($field, $row['field_value'], $row, $item_id);
		}
	}

	public function get_default_field_data()
	{
		return array(
			'field_label'		=> '',
			'field_explain'		=> '',
			'field_value'		=> '',
			'field_required'	=> false,
		);
	}

	public function get_form_fields()
	{
		return $this->fields;
	}

	public function get_data()
	{
		return $this->data;
	}
}