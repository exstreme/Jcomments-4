<?php
/**
 * JComments Latest Comments - Shows latest comments
 *
 * @version           4.0.0
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Module\LatestComments\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseDriver;

class ObjectgroupField extends ListField
{
	protected $type = 'ObjectGroup';

	protected function getOptions()
	{
		$options = array();

		/** @var DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
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
