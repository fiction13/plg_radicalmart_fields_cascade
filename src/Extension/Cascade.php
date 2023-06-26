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

namespace Joomla\Plugin\RadicalMartFields\Cascade\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\QueryInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\RadicalMartFields\Cascade\Helper\CascadeHelper;
use Joomla\Registry\Registry;
use SimpleXMLElement;

class Cascade extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  \Joomla\Database\DatabaseDriver
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db = null;

	/**
	 * The cascadehelper
	 *
	 * @var    CascadeHelper
	 * @since  __DEPLOY_VERSION__
	 */
	protected $_name = null;

	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface  &$subject  The object to observe
	 * @param   array                 $config   An optional associative array of configuration settings.
	 *                                          Recognized key values include 'name', 'group', 'params', 'language'
	 *                                          (this list is not meant to be comprehensive).
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartGetFieldType'          => 'onRadicalMartGetFieldType',
			'onRadicalMartFilterFieldType'       => 'onRadicalMartFilterFieldType',
			'onRadicalMartGetFieldForm'          => 'onRadicalMartGetFieldForm',
			'onRadicalMartGetProductFieldXml'    => 'onRadicalMartGetProductFieldXml',
			'onRadicalMartAfterGetFieldForm'     => 'onRadicalMartAfterGetFieldForm',
			'onRadicalMartGetFilterFieldXml'     => 'onRadicalMartGetFilterFieldXml',
			'onRadicalMartGetProductsListQuery'  => 'onRadicalMartGetProductsListQuery',
			'onRadicalMartGetProductsFieldValue' => 'onRadicalMartGetProductsFieldValue',
			'onRadicalMartGetProductFieldValue'  => 'onRadicalMartGetProductFieldValue',
		];
	}

	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $item     List item object.
	 *
	 * @return string|false Field type constant on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetFieldType($context = null, $item = null)
	{
		return 'PLG_RADICALMART_FIELDS_CASCADE_FIELD_TYPE';
	}

	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string|null          $context  Context selector string.
	 * @param   string|null          $search   List item object.
	 * @param   QueryInterface|null  $query    A QueryInterface object to retrieve the data set.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartFilterFieldType(string $context = null, string $search = null, QueryInterface $query = null)
	{
		if ($context === 'com_radicalmart.fields')
		{
			$db = $this->db;
			$query->where('JSON_VALUE(f.params, ' . $db->quote('$."type"') . ') = ' . $db->quote($search));
		}
	}

	/**
	 * Method to field type form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetFieldForm(string $context = null, Form $form = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'cascade')
		{
			return;
		}

		$area    = $tmpData->get('area');
		$methods = [
			'products' => 'loadFieldProductsForm'
		];

		if (isset($methods[$area]))
		{
			$method = $methods[$area];
			if (method_exists($this, $method))
			{
				$this->$method($form, $tmpData);
			}
		}
	}

	/**
	 * Method to load products field type form.
	 *
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function loadFieldProductsForm(Form $form = null, Registry $tmpData = null)
	{
		// Load global
		Form::addFormPath(JPATH_PLUGINS . '/radicalmart_fields/cascade/forms');
		$form->loadFile('config');

		$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		$form->removeField('display_variability_as', 'params');
	}

	/**
	 * Method to change field form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartAfterGetFieldForm(string $context = null, Form $form = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'cascade')
		{
			return;
		}

		$area    = $tmpData->get('area');
		$methods = [
			'products' => 'changeFieldProductsForm'
		];

		if (isset($methods[$area]))
		{
			$method = $methods[$area];
			if (method_exists($this, $method))
			{
				$this->$method($form, $tmpData);
			}
		}
	}

	/**
	 * Method to chage  products field type form.
	 *
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function changeFieldProductsForm(Form &$form = null, Registry $tmpData = null)
	{
		$params = $tmpData->get('params', new \stdClass());

		$form->setValue('display_variability', 'params', '0');
	}

	/**
	 * Method to add field to product form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   object|null    $field    Field data object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @return false|\SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetProductFieldXml(string $context = null, object $field = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'cascade')
		{
			return false;
		}

		$fieldNode = new SimpleXMLElement('<field />');
		$fieldNode->addAttribute('name', $field->alias);
		$fieldNode->addAttribute('label', $field->title);
		$fieldNode->addAttribute('type', 'subform');
		$fieldNode->addAttribute('multiple', 'true');
		$fieldNode->addAttribute('layout', 'joomla.form.field.subform.repeatable');
		$fieldNode->addAttribute('addfieldprefix', 'Joomla\Plugin\RadicalMartFields\Cascade\Field');
		$fieldNode->addAttribute('parentclass', 'stack');
		$fieldNode->addAttribute('min', '1');

		// Build the form source
		$fieldsXml = new SimpleXMLElement('<form/>');
		$fields    = $fieldsXml->addChild('fields');

		$child = $fields->addChild('field');
		$child->addAttribute('name', 'value');
		$child->addAttribute('type', 'cascade');
		$child->addAttribute('label', $field->title);
		$child->addAttribute('data-id', $field->id);

		$fieldNode->addAttribute('formsource', $fieldsXml->asXML());

		return $fieldNode;
	}

	/**
	 * Method to add field to filter form.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 *
	 * @return false|\SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetFilterFieldXml(string $context = null, object $field = null)
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products'])
			|| $field->plugin !== 'cascade'
			|| $field->params->get('display_filter', 0) === 0)
		{
			return false;
		}

		$fieldXML = new SimpleXMLElement('<field />');
		$fieldXML->addAttribute('name', $field->alias);
		$fieldXML->addAttribute('label', $field->title);
		$fieldXML->addAttribute('type', 'cascade');
		$fieldXML->addAttribute('addfieldprefix', 'Joomla\Plugin\RadicalMartFields\Cascade\Field');
		$fieldXML->addAttribute('data-id', $field->id);
		$fieldXML->addAttribute('layout', 'plugins.radicalmart_fields.cascade.field.filter.cascade');

		return $fieldXML;
	}

	/**
	 * Method to modify query.
	 *
	 * @param   string|null          $context  Context selector string.
	 * @param   QueryInterface|null  $query    A QueryInterface object to retrieve the data set.
	 * @param   object|null          $field    Field data object.
	 * @param   mixed                $value    Value.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetProductsListQuery(string $context = null, QueryInterface $query = null,
	                                                  object $field = null, $value = null)
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products']) || $field->plugin !== 'cascade')
		{
			return;
		}

		if (!is_array($value))
		{
			$value = [$value];
		}

		$db    = $this->db;
		$value = array_filter($value);
		$value = '"' . implode('","', $value) . '"';
		$query->where('p.fields LIKE ' . $db->quote('%' . $value . '%'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string|null  $context  Context selector string.
	 * @param   object|null  $field    Field data object.
	 * @param   mixed        $value    Field value.
	 *
	 * @return  string|false  Field html value.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetProductsFieldValue(string $context = null, object $field = null, $value = null)
	{
		if (!in_array($context, ['com_radicalmart.category', 'com_radicalmart.products']) || $field->plugin !== 'cascade' || (int) $field->params->get('display_products', 1) === 0)
		{
			return false;
		}

		$display         = $field->params->get('display_products_display', 'all');
		$separatedValues = $field->params->get('display_products_separated_values', ' ');
		$layout          = $field->params->get('display_products_as', 'string');
		$template        = $field->params->get('display_products_template', 'default');

		$value = $this->getFieldValue($field, $value, $display, $separatedValues, $template, $layout);

		return $value;
	}


	/**
	 * Method to add field value to products list.
	 *
	 * @param   string        $context  Context selector string.
	 * @param   object        $field    Field data object.
	 * @param   array|string  $value    Field value.
	 *
	 * @return  string  Field html value.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetProductFieldValue($context = null, $field = null, $value = null)
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'cascade' || (int) $field->params->get('display_product', 1) === 0)
		{
			return false;
		}

		$display         = $field->params->get('display_product_display', 'all');
		$separatedValues = $field->params->get('display_product_separated_values', ' ');
		$layout          = $field->params->get('display_product_as', 'string');
		$template        = $field->params->get('display_product_template', 'default');

		$value = $this->getFieldValue($field, $value, $display, $separatedValues, $template, $layout);

		return $value;
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   object|null  $field   Field data object.
	 * @param   mixed        $value   Field value.
	 * @param   string       $layout  Layout name.
	 *
	 * @return  string|false  Field string values on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getFieldValue(object $field = null, $value = null, $display = 'all', $separatedValues = ' ', string $template = 'default', string $layout = 'string')
	{
		if (empty($field) || empty($value))
		{
			return false;
		}

		if (!is_array($value))
		{
			$value = [$value];
		}

		$helper = (new CascadeHelper($field->params));
		$names  = $helper->parseLines($field->params->get('names'));

		// Display options
		if ($display === 'first')
		{
			$value = [array_shift($value)];

		}
		else if ($display === 'all_without_first')
		{
			array_shift($value);
		}

		$values = [];

		foreach ($value as $val)
		{
			$val    = array_shift($val);
			$result = [];

			if ($template === 'last')
			{
				$result[] = end($val);
			}
			else if ($template === 'label')
			{
				foreach ($names as $key => $title)
				{
					if (!empty($title) && isset($val[$key]) && !empty($val[$key]))
					{
						$result[] =
							'<span class="cascade__label cascade__label_' . $key . '">' . $title . ':</span> '
							. '<span class="cascade__value cascade__value_' . $key . '">' . $val[$key] . '</span>';
					}
				}

			}
			else
			{
				$result = $val;
			}

			$result   = array_filter($result);
			$values[] = implode($separatedValues, $result);
		}

		$html = ($layout === 'string') ? implode(', ', $values)
			: LayoutHelper::render('plugins.radicalmart_fields.cascade.display.' . $layout,
				['field' => $field, 'values' => $values]);

		return $html;
	}
}