<?php
/*
 * @package     RadicalMart Fields Standard Plugin
 * @subpackage  plg_radicalmart_fields_standard
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartFields\Cascade\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;
use Joomla\Component\RadicalMart\Administrator\Helper\FieldsHelper;
use Joomla\Plugin\RadicalMartFields\Cascade\Helper\CascadeHelper;

class CascadeField extends TextField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $info = null;

	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'cascade';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'plugins.radicalmart_fields.cascade.administrator.field.cascade';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if ($return = parent::setup($element, $value, $group))
		{
			Text::script('JOPTION_DO_NOT_USE');

			$fields = FieldsHelper::getFields((int) $this->dataAttributes['data-id']);
			$field  = array_shift($fields);

			$this->helper = (new CascadeHelper($field->params));

			if (!self::$info)
			{
				self::$info = $this->helper->getCascadeInfo();
			}
		}

		return $return;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since 3.5
	 */
	protected function getLayoutData(): array
	{
		$data             = parent::getLayoutData();
		$data['items']    = self::$info['items'];
		$data['names']    = self::$info['names'];
		$data['maxLevel'] = self::$info['maxLevel'];
		$data['cascadeId']   = uniqid();

		return $data;
	}
}