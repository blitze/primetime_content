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

class url_test extends base_form_field
{
	public function test_name()
	{
		$field = $this->get_form_field('url');
		$this->assertEquals('url', $field->get_name());
	}

	public function test_langname()
	{
		$field = $this->get_form_field('url');
		$this->assertEquals('FORM_FIELD_URL', $field->get_langname());
	}

	public function test_default_props()
	{
		$field = $this->get_form_field('url');
		$this->assertEquals(array(
			'max'	=> 255,
		), $field->get_default_props());
	}

	/**
	 * @return array
	 */
	public function display_field_test_data()
	{
		return array(
			array('', ''),
			array('http://www.google.com', '<!-- l --><a class="postlink-local" href="http://www.google.com"><!-- w --><a class="postlink" href="http://www.google.com">www.google.com</a><!-- w --></a><!-- l -->'),
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
		$field = $this->get_form_field('url');
		$data = array('field_value' => $field_value);
		$this->assertEquals($expected, $field->display_field($data));
	}

	/**
	 * @return array
	 */
	public function show_url_field_test_data()
	{
		return array(
			array(
				'foo',
				array(
					'field_value'	=> '',
				),
				array(
					array('foo', '', true, request_interface::REQUEST, ''),
				),
				'<input type="url" class="inputbox autowidth" id="smc-foo" name="foo" maxlength="255" size="40" value="" />',
			),
			array(
				'foo',
				array(
					'field_value'	=> 'bar',
				),
				array(
					array('foo', 'bar', true, request_interface::REQUEST, 'bar'),
				),
				'<input type="url" class="inputbox autowidth" id="smc-foo" name="foo" maxlength="255" size="40" value="bar" />',
			),
			array(
				'foo2',
				array(
					'field_value'	=> 'bar',
				),
				array(
					array('foo2', 'bar', true, request_interface::REQUEST, 'foo_bar'),
				),
				'<input type="url" class="inputbox autowidth" id="smc-foo2" name="foo2" maxlength="255" size="40" value="foo_bar" />',
			),
		);
	}

	/**
	 * @dataProvider show_url_field_test_data
	 * @param string $name
	 * @param array $data
	 * @param array $variable_map
	 * @param string $expected
	 * @return void
	 */
	public function test_show_url_field($name, array $data, array $variable_map, $expected)
	{
		$field = $this->get_form_field('url', $variable_map);
		$data = $this->get_data('url', $name, $data, $field->get_default_props());

		$this->assertEquals($expected, $field->show_form_field($name, $data));
	}
}