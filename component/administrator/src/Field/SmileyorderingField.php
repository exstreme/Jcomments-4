<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

class SmileyorderingField extends FormField
{
	protected $type = 'SmileyOrdering';

	protected function getInput()
	{
		$html = array();

		$attr = $this->element['class'] ? ' class="' . $this->element['class'] . '"' : '';
		$attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$attr .= $this->element['onchange'] ? ' onchange="' . $this->element['onchange'] . '"' : '';

		$smileyId = (int) $this->form->getValue('id');

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('ordering', 'value'))
			->select($db->quoteName('name', 'text'))
			->from($db->quoteName('#__jcomments_smilies'))
			->order('ordering');

		if ((string) $this->element['readonly'] == 'true')
		{
			$html[] = HTMLHelper::_('list.ordering', '', $query, trim($attr), $this->value, $smileyId ? 0 : 1);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		}
		else
		{
			$html[] = HTMLHelper::_('list.ordering', $this->name, $query, trim($attr), $this->value, $smileyId ? 0 : 1);
		}

		return implode($html);
	}
}
