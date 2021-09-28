<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldUsergroups extends ListField
{
	protected $type = 'Usergroups';

	protected function getInput()
	{
		if (!is_array($this->value))
		{
			$this->value = explode(',', $this->value);
		}

		return parent::getInput();
	}

	protected function getOptions()
	{
		$options = array();

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('a.*, COUNT(DISTINCT b.id) AS level')
			->from($db->quoteName('#__usergroups') . ' AS a')
			->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id, a.title, a.lft, a.rgt, a.parent_id')
			->order('a.lft ASC');

		$db->setQuery($query);
		$groups = $db->loadObjectList();

		foreach ($groups as $group)
		{
			$prefix    = trim(str_repeat(' ', $group->level));
			$options[] = HTMLHelper::_('select.option', $group->id, trim($prefix . ' ' . $group->title));
		}

		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
