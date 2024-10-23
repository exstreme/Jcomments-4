<?php
/**
 * JComments plugin for VirtueMart objects support
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_virtuemart extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JcommentsObjectInfo;
		$configHelper = JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';

		if (is_file($configHelper))
		{
			if (!class_exists('VmConfig'))
			{
				require_once $configHelper;
			}

			VmConfig::loadConfig();

			/** @var \Joomla\Database\DatabaseInterface $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('prod_lang.product_name', 'prod.created_by')))
				->from($db->quoteName('#__virtuemart_products_' . VMLANG, 'prod_lang'))
				->join('LEFT', $db->quoteName('#__virtuemart_products', 'prod'), 'prod.virtuemart_product_id = prod.virtuemart_product_id')
				->where($db->quoteName('prod_lang.virtuemart_product_id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row))
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('virtuemart_category_id'))
					->from($db->quoteName('#__virtuemart_product_categories'))
					->where($db->quoteName('virtuemart_product_id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);
				$db->setQuery($query);
				$categoryId = $db->loadResult();

				$info->title  = $row->product_name;
				$info->userid = $row->created_by;
				$info->link   = Route::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $id . '&virtuemart_category_id=' . $categoryId);
			}
		}

		return $info;
	}
}
