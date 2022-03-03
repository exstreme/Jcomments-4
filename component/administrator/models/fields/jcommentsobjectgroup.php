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
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldJCommentsObjectGroup extends ListField
{
	protected $type = 'JCommentsObjectGroup';

	protected function getOptions()
	{
		$options = array();

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('DISTINCT ' . $db->qn('element'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = "component"')
			->order($db->qn('element'));

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
