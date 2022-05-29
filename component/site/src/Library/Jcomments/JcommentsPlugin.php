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

namespace Joomla\Component\Jcomments\Site\Library\Jcomments;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * JComments plugin base class
 *
 * @since  3.0
 */
class JcommentsPlugin
{
	/**
	 * Return the title of an object by given identifier.
	 *
	 * @param   integer  $id  A object identifier.
	 *
	 * @return  string Object title
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function getObjectTitle(int $id): string
	{
		return Factory::getApplication()->get('sitename');
	}

	/**
	 * Return the URI to object by given identifier.
	 *
	 * @param   integer  $id  A object identifier.
	 *
	 * @return  string URI of an object
	 *
	 * @since   3.0
	 */
	public function getObjectLink(int $id): string
	{
		return Uri::root(true);
	}

	/**
	 * Return identifier of the object owner.
	 *
	 * @param   integer  $id  A object identifier.
	 *
	 * @return  integer Identifier of the object owner, otherwise -1
	 *
	 * @since   3.0
	 */
	public function getObjectOwner(int $id): int
	{
		return -1;
	}

	/**
	 * @param   string  $objectGroup  E.g. com_content
	 * @param   string  $link         Menu link to searching.
	 *
	 * @return  integer
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public static function getItemid(string $objectGroup, string $link = ''): int
	{
		static $cache = array();

		$key = 'jc_' . $objectGroup . '_itemid';

		if (!isset($cache[$key]))
		{
			$app = Factory::getApplication();

			if (empty($link))
			{
				$component = ComponentHelper::getComponent($objectGroup);

				if (isset($component->id))
				{
					$item = $app->getMenu()->getItems('component_id', $component->id, true);
				}
				else
				{
					$item = null;
				}
			}
			else
			{
				$item = $app->getMenu()->getItems('link', $link, true);
			}

			$cache[$key] = ($item !== null) ? $item->id : 0;
			unset($items);
		}

		return $cache[$key];
	}

	/**
	 * Gets the parameter object for a plugin
	 *
	 * @param   string  $pluginName  The plugin name
	 * @param   string  $type        The plugin type, relates to the sub-directory in the plugins directory
	 *
	 * @return  Registry
	 *
	 * @since   3.0
	 */
	public static function getParams(string $pluginName, string $type = 'content'): Registry
	{
		$plugin = PluginHelper::getPlugin($type, $pluginName);

		if (is_object($plugin))
		{
			$pluginParams = new Registry($plugin->params);
		}
		else
		{
			$pluginParams = new Registry('');
		}

		return $pluginParams;
	}
}
