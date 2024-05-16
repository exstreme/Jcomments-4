<?php
/**
 * JComments plugin for HikaShop (https://www.hikashop.com) support
 *
 * @version       4.0
 * @package       JComments
 * @author        Hikari Team (hikari.software@gmail.com)
 * @copyright (C) 2011 by Hikari Team (https://www.hikashop.com)
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_hikashop extends JCommentsPlugin
{
	public function getObjectTitle($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.product_name', 'name'))
			->select($db->quoteName('a.product_id', 'id'))
			->select($db->quoteName('b.product_name', 'parent_name'))
			->from($db->quoteName('#__hikashop_product', 'a'))
			->join('LEFT', $db->quoteName('#__hikashop_product', 'b'), 'a.product_parent_id = b.product_id')
			->where($db->quoteName('a.product_id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$obj  = $db->loadObject();
		$name = !empty($obj->name) ? $obj->name : $obj->parent_name;

		if (empty($name))
		{
			$name = $id;
		}

		return $name;
	}

	public function getObjectLink($id)
	{
		$itemid = self::getItemid('com_hikashop');
		$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

		// TODO Wrong link?
		return Route::_('index.php?option=com_hikashop&task=product.show&cid=' . $id . $itemid);
	}

	public function getObjectOwner($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.product_vendor_id', 'created_by'))
			->select($db->quoteName('a.product_id', 'id'))
			->select($db->quoteName('b.product_vendor_id', 'parent_created_by'))
			->from($db->quoteName('#__hikashop_product', 'a'))
			->join('LEFT', $db->quoteName('#__hikashop_product', 'b'), 'a.product_parent_id = b.product_id')
			->where($db->quoteName('a.product_id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$obj = $db->loadObject();
		$id = !empty($obj->created_by) ? $obj->created_by : $obj->parent_created_by;

		if (!empty($id))
		{
			$query->select($db->quoteName('user_cms_id'))
				->from($db->quoteName('#__hikashop_user'))
				->where($db->quoteName('user_id') . ' = :uid')
				->bind(':uid', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$id = $db->loadResult();
		}

		if (empty($id))
		{
			$app = Factory::getApplication();

			if ($app->isClient('administrator'))
			{
				$id = $app->getIdentity()->id;
			}
		}

		return (int) $id;
	}
}
