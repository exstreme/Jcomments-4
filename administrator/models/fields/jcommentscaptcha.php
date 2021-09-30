<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

class JFormFieldJCommentsCaptcha extends ListField
{
	protected $type = 'JCommentsCaptcha';
	protected $format = '%s (Joomla)';

	protected function getInput()
	{
		$this->multiple = false;

		return parent::getInput();
	}

	protected function getOptions()
	{
		$folder = 'captcha';

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('element AS value, name AS text')
			->from('#__extensions')
			->where('folder = ' . $db->quote($folder))
			->where('enabled = 1')
			->order('ordering, name');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		$lang = Factory::getApplication()->getLanguage();

		foreach ($options as $i => $item)
		{
			$source    = JPATH_PLUGINS . '/' . $folder . '/' . $item->value;
			$extension = 'plg_' . $folder . '_' . $item->value;
			$lang->load($extension . '.sys', JPATH_ADMINISTRATOR, null, false, true)
			|| $lang->load($extension . '.sys', $source, null, false, true);
			$options[$i]->text = sprintf($this->format, JText::_($item->text));
		}

		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
