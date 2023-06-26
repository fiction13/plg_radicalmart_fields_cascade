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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\RadicalMart\Site\Helper\MediaHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $id             DOM id of the field.
 * @var   string  $label          Label of the field.
 * @var   string  $name           Name of the input field.
 * @var   string  $value          Value attribute of the field.
 * @var   array   $checkedOptions Options that will be set as checked.
 * @var   boolean $hasValue       Has this field a value assigned?
 * @var   array   $options        Options available for this field.
 * @var   string  $onchange       Onchange attribute for the field.
 */

HTMLHelper::script('plg_radicalmart_fields_cascade/cascade.min.js', array('version' => 'auto', 'relative' => true));

Factory::getApplication()->getDocument()->addScriptOptions('plg_radicalmart_fields_cascade_' . $cascadeId, $items);

$lvlCheck = $curLvl = 0;
$allText  = ' - ' . Text::_('JALL') . ' - ';

$html = array();
for ($i = 0; $i <= $maxLevel; $i++)
{
	$listValue = $value[$i] ?? null;
	$listLabel = $names[$i] ?? ' ';
	$listName  = $name . "[$i]";
	$listAttrs = 'data-index="' . $i . '" class="form-select" data-name="' . $listLabel . '"';
	$options   = [];

    if ($onchange)
    {
        $listAttrs .= ' onchange="' . $onchange . '"';
    }

	$tmp        = new \stdClass();
	$tmp->value = '';
	$tmp->text  = '- ' . $listLabel . ' -';

	$options[] = $tmp;

	if ($lvlCheck == $curLvl)
	{
		$lvlCheck++;
		$keys = array_keys($items);

		if (!empty($keys))
		{
			array_walk($keys, function ($item) use (&$options) {
				$tmp        = new \stdClass();
				$tmp->value = $item;
				$tmp->text  = Text::_($item);

				$options[] = $tmp;
			});
		}
		else
		{
			$options = array();
		}
	}

	$html[] = '<div class="mb-2" data-cascade="parent">';
	$html[] = HTMLHelper::_(
		'select.genericList',
		$options,
		$listName,
		$listAttrs,
		'value',
		'text',
		$listValue,
	);
	$html[] = '</div>';

	if (isset($items[$listValue]))
	{
		$items = $items[$listValue];
		$curLvl++;
	}
}
?>

<div class="cascade" data-cascade="container" data-id="<?php echo $cascadeId; ?>">
	<?php echo implode(PHP_EOL, $html);?>
</div>