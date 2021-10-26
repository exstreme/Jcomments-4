<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * JComments plugin base class
 */
class JCommentsPlugin
{
	/**
	 * Return the title of an object by given identifier.
	 *
	 * @param   int  $id  A object identifier.
	 *
	 * @return string Object title
	 */
	public function getObjectTitle($id)
	{
		return Factory::getApplication()->get('sitename');
	}

	/**
	 * Return the URI to object by given identifier.
	 *
	 * @param   int  $id  A object identifier.
	 *
	 * @return string URI of an object
	 */
	public function getObjectLink($id)
	{
		return Uri::root(true);
	}

	/**
	 * Return identifier of the object owner.
	 *
	 * @param   int  $id  A object identifier.
	 *
	 * @return int Identifier of the object owner, otherwise -1
	 */
	public function getObjectOwner($id)
	{
		return -1;
	}

	public static function getItemid($objectGroup, $link = '')
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
}
