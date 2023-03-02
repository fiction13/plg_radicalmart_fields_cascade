<?php
/*
 * @package   mod_radicalmart_categories
 * @version   __DEPLOY_VERSION__
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

namespace Joomla\Plugin\RadicalMartFields\Cascade\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\String\StringHelper;

/**
 * @package     Helper class
 *
 * @since       __DEPLOY_VERSION__
 */
class CascadeHelper
{
	/**
	 * @var Registry
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $params;

	/**
	 * @var Input
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $input;


	/**
	 * @param   Registry  $params
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function __construct(Registry $params)
	{
		$this->params = $params;
	}

	/**
	 * Get item list
	 *
	 * @param   string  $names
	 * @param   string  $items
	 *
	 * @return array
	 */
	public function getCascadeInfo()
	{
		$configNames = $this->parseLines($this->params->get('names'), true);
		$configItems = $this->parseLines($this->params->get('items'));

		$maxLevel    = 0;
		$resultItems = array();

		if (!empty($configItems))
		{
			$prevLevel     = 0;
			$prevLevelName = '';
			$nestedKeys    = array();

			foreach ($configItems as $configItem)
			{

				if (preg_match("#^([- ]*|[-]*)(.*)#ius", $configItem, $matches))
				{

					$level = substr_count(trim($matches[1]), '-');

					if ($prevLevel < $level)
					{
						$nestedKeys[] = $prevLevelName;

					}
					elseif ($prevLevel > $level)
					{

						for ($i = 1; $i <= $prevLevel - $level; $i++)
						{
							array_pop($nestedKeys);
						}
					}

					if (count($nestedKeys) > $maxLevel)
					{
						$maxLevel = count($nestedKeys);
					}

					$listTitle = ' ';
					if (isset($configNames[$level]))
					{
						$listTitle = $configNames[$level];
					}

					$resultItems = $this->_addToNestedList($matches[2], $resultItems, $nestedKeys, $listTitle);

					$prevLevelName = $matches[2];

					$prevLevel = $level;
				}
			}
		}

		$result = array(
			'items'    => $resultItems,
			'names'    => $configNames,
			'maxLevel' => $maxLevel
		);

		return $result;
	}

	/**
	 * Parse text by lines
	 *
	 * @param   string  $text
	 *
	 * @return array
	 */
	public function parseLines($text, $translate = false)
	{
		$text = StringHelper::trim($text ?? '');
		$text = htmlspecialchars_decode($text, ENT_COMPAT);
		$text = strip_tags($text);

		$lines  = explode("\n", $text);
		$result = array();

		if (!empty($lines))
		{
			foreach ($lines as $line)
			{
				$line     = StringHelper::trim($line ?? '');

				if ($translate)
				{
					$line = Text::_($line);
				}

				$result[] = strtr($line, "\"", "'");
			}
		}

		return $result;
	}

	/**
	 * Add item to nested list
	 *
	 * @param   string  $item
	 * @param   array   $resultArr
	 * @param   array   $nestedKeys
	 * @param   string  $listTitle
	 *
	 * @return array
	 */
	protected function _addToNestedList($item, array $resultArr, array $nestedKeys, $listTitle)
	{
		$tmpArr = &$resultArr;

		if (!empty($nestedKeys))
		{
			foreach ($nestedKeys as $nestedKey)
			{
				$tmpArr = &$tmpArr[$nestedKey];
			}
		}

		$tmpArr[$item] = array();

		return $resultArr;
	}
}