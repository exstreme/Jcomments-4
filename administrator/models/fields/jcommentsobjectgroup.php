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
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldJCommentsObjectGroup extends ListField
{
	protected $type = 'JCommentsObjectGroup';

	protected function getInput()
	{
		$attr = '';
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';
		$attr .= $this->multiple ? ' multiple="multiple"' : '';

		$options = (array) $this->getOptions();

		return HTMLHelper::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
	}

	protected function getOptions()
	{
		$options = array();

		/* @var JDatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('DISTINCT ' . $db->qn('element'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = "component"')
			->order('element');

		$db->setQuery($query);
		$components = $db->loadColumn();

		$plugins = Folder::files(JPATH_SITE . '/components/com_jcomments/plugins/', '\.plugin\.php', true, false);

		if (is_array($plugins))
		{
			foreach ($plugins as $plugin)
			{
				$pluginName = str_replace('.plugin.php', '', $plugin);
				foreach ($components as $component)
				{
					if ($pluginName == $component || strpos($pluginName, $component . '_') !== false)
					{
						$options[] = HTMLHelper::_('select.option', $pluginName, $pluginName);
					}
				}
			}
		}
		else
		{
			$options[] = HTMLHelper::_('select.option', 'com_jcomments', 'com_jcomments');
		}

		return $options;
	}
}
