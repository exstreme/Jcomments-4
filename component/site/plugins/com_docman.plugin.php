<?php
/**
 * JComments plugin for DocMan objects support (https://www.joomlatools.com/extensions/docman/)
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_docman extends JCommentsPlugin
{
	public function getObjectTitle($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'dmname')))
			->from($db->quoteName('#__docman'))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}

	public function getObjectLink($id)
	{
		static $itemid = null;

		if (!isset($itemid))
		{
			$needles = array('gid' => (int) $id);

			if ($item = self::findItem($needles))
			{
				$itemid = $item->id;
			}
			else
			{
				$itemid = '';
			}
		}

		include_once JPATH_SITE . '/includes/application.php';

		$link = 'index.php?option=com_docman&task=doc_details&gid=' . $id;

		if ($itemid != '')
		{
			$link .= '&Itemid=' . $itemid;
		};

		$router = JPATH_SITE . '/components/com_docman/router.php';

		if (is_file($router))
		{
			include_once $router;
		}

		return Route::_($link);
	}

	public function getObjectOwner($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName('dmsubmitedby'))
			->from($db->quoteName('#__docman'))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}

	protected static function findItem($needles)
	{
		$component = ComponentHelper::getComponent('com_docman');

		$app    = Factory::getApplication();
		$menus  = $app->getMenu('site');
		$items  = $menus->getItems('componentid', $component->id);
		$user   = $app->getIdentity();
		$access = $user->getAuthorisedGroups();

		foreach ($needles as $needle => $id)
		{
			if (is_array($items))
			{
				foreach ($items as $item)
				{
					if ($item->published == 1 && in_array($item->access, $access))
					{
						return $item;
					}
				}
			}
		}

		return false;
	}
}
