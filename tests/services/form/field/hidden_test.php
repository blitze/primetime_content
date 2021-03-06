<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2017 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\content\tests\services\form\field;

use phpbb\request\request_interface;

class hidden_test extends base_form_field
{
	public function test_name()
	{
		$field = $this->get_form_field('hidden');
		$this->assertEquals('hidden', $field->get_name());
	}

	public function test_langname()
	{
		$field = $this->get_form_field('hidden');
		$this->assertEquals('FORM_FIELD_HIDDEN', $field->get_langname());
	}

	public function test_default_props()
	{
		$field = $this->get_form_field('hidden');
		$this->assertEquals(array(), $field->get_default_props());
	}

	/**
	 * @return array
	 */
	public function display_field_test_data()
	{
		return array(
			array('', ''),
			array('foo', 'foo'),
		);
	}

	/**
	 * @dataProvider display_field_test_data
	 * @param string $field_value
	 * @param string $expected
	 * @return void
	 */
	public function test_display_field($field_value, $expected)
	{
		$field = $this->get_form_field('hidden');

		$data = array('field_value' => $field_value);
		$data['field_value'] = $field->get_field_value($data);

		$this->assertEquals($expected, $field->display_field($data, array(), 'summary'));
	}

	/**
	 * @return array
	 */
	public function show_hidden_field_test_data()
	{
		return array(
			array(
				array(
					'field_name'	=> 'foo1',
					'field_value'	=> '',
				),
				array(
					array('foo1', '', true, request_interface::REQUEST, ''),
				),
				'<input type="hidden" id="smc-foo1" name="foo1" value="" />',
			),
			array(
				array(
					'field_name'	=> 'foo2',
					'field_value'	=> 'bar',
				),
				array(
					array('foo2', 'bar', true, request_interface::REQUEST, 'bar'),
				),
				'<input type="hidden" id="smc-foo2" name="foo2" value="bar" />',
			),
			array(
				array(
					'field_name'	=> 'foo3',
					'field_value'	=> 'bar',
				),
				array(
					array('foo3', 'bar', true, request_interface::REQUEST, 'foo_bar'),
				),
				'<input type="hidden" id="smc-foo3" name="foo3" value="foo_bar" />',
			),
		);
	}

	/**
	 * @dataProvider show_hidden_field_test_data
	 * @param array $data
	 * @param array $variable_map
	 * @param string $expected
	 * @return void
	 */
	public function test_show_hidden_field(array $data, array $variable_map, $expected)
	{
		$field = $this->get_form_field('hidden', $variable_map);

		$data = $this->get_data('hidden', $data, $field->get_default_props());
		$data['field_value'] = $field->get_submitted_value($data);

		$this->assertEquals($expected, $field->show_form_field($data));
	}
}
