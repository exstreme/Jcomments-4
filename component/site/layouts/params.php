<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 * This is simplified version of joomla.edit.params layout.
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

/** @var Joomla\Component\Jcomments\Site\View\Form\HtmlView $displayData */

$app       = Factory::getApplication();
$form      = $displayData->getForm();
$fieldSets = $form->getFieldsets();

if (empty($fieldSets))
{
	return;
}

$ignoreFieldsets      = $displayData->get('ignore_fieldsets') ?: array();
$outputFieldsets      = $displayData->get('output_fieldsets') ?: array();
$ignoreFieldsetFields = $displayData->get('ignore_fieldset_fields') ?: array();
$ignoreFields         = $displayData->get('ignore_fields') ?: array();
$extraFields          = $displayData->get('extra_fields') ?: array();

// These are required to preserve data on save when fields are not displayed.
$hiddenFieldsets = $displayData->get('hiddenFieldsets') ?: array();

// These are required to configure showing and hiding fields in the editor.
$configFieldsets = $displayData->get('configFieldsets') ?: array();

// Handle the hidden fieldsets when show_options is set false
if (!$displayData->get('show_options', 1))
{
	// The HTML buffer
	$html   = array();

	// Loop over the fieldsets
	foreach ($fieldSets as $name => $fieldSet)
	{
		// Check if the fieldset should be ignored
		if (in_array($name, $ignoreFieldsets, true))
		{
			continue;
		}

		// If it is a hidden fieldset, render the inputs
		if (in_array($name, $hiddenFieldsets))
		{
			// Loop over the fields
			foreach ($form->getFieldset($name) as $field)
			{
				// Add only the input on the buffer
				$html[] = $field->input;
			}

			// Make sure the fieldset is not rendered twice
			$ignoreFieldsets[] = $name;
		}

		// Check if it is the correct fieldset to ignore
		if (strpos($name, 'basic') === 0)
		{
			// Ignore only the fieldsets which are defined by the options not the custom fields ones
			$ignoreFieldsets[] = $name;
		}
	}

	// Echo the hidden fieldsets
	echo implode('', $html);
}

$opentab = false;
$xml = $form->getXml();

// Loop again over the fieldsets
foreach ($fieldSets as $name => $fieldSet)
{
	// Ensure any fieldsets we don't want to show are skipped (including repeating formfield fieldsets)
	if ((isset($fieldSet->repeat) && $fieldSet->repeat === true)
		|| in_array($name, $ignoreFieldsets)
		|| (!empty($configFieldsets) && in_array($name, $configFieldsets, true))
		|| (!empty($hiddenFieldsets) && in_array($name, $hiddenFieldsets, true))
	)
	{
		continue;
	}

	$hasChildren  = $xml->xpath('//fieldset[@name="' . $name . '"]//fieldset[not(ancestor::field/form/*)]');
	$hasParent    = $xml->xpath('//fieldset//fieldset[@name="' . $name . '"]');
	$isGrandchild = $xml->xpath('//fieldset//fieldset//fieldset[@name="' . $name . '"]');

	if (!$isGrandchild && $hasParent)
	{
		echo '<div class="form-grid">';
	}
	// Tabs
	elseif (!$hasParent)
	{
		if ($opentab)
		{
			if ($opentab > 1)
			{
				echo '</div>';
			}
		}

		$opentab = 1;

		// Directly add a fieldset if we have no children
		if (!$hasChildren)
		{
			echo '<div class="form-grid">';

			$opentab = 2;
		}
	}

	// We're on the deepest level => output fields
	if (!$hasChildren)
	{
		// The name of the fieldset to render
		$displayData->fieldset = $name;

		// Force to show the options
		$displayData->showOptions = true;

		// Render the fieldset
		echo LayoutHelper::render('joomla.edit.fieldset', $displayData);
	}

	// Close open fieldset
	if (!$isGrandchild && $hasParent)
	{
		echo '</div>';
	}
}

if ($opentab)
{
	if ($opentab > 1)
	{
		echo '</div>';
	}
}
