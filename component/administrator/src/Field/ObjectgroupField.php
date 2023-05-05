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
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Form Field class for display available object groups.
 *
 * @since  1.7.0
 * @noinspection  PhpUnused
 */
class ObjectgroupField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'ObjectGroup';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.7.0
	 */
	protected function getOptions()
	{
		$options = array();

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('DISTINCT ' . $db->quoteName('element'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = "component"')
			->order($db->quoteName('element'));

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
