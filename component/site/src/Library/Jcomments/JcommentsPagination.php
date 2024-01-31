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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Pagination\PaginationObject;

/**
 * Pagination class. Provides a common interface for content pagination for the jcomments component.
 *
 * @since  4.1
 */
class JcommentsPagination extends Pagination
{
	/**
	 * Method to create an active pagination link to the item
	 *
	 * @param   PaginationObject  $item  The object with which to make an active link.
	 *
	 * @return  string  HTML link
	 *
	 * @since   1.5
	 */
	protected function _item_active(PaginationObject $item)
	{
		// Do not override pagination layout in user profile and when not in list mode.
		if (ComponentHelper::getParams('com_jcomments')->get('template_view') == 'list' && Factory::getApplication()->input->getString('controller') != 'user')
		{
			return LayoutHelper::render('pagination_link', ['data' => $item, 'active' => true], '', array('component' => 'com_jcomments'));
		}

		// Fallback to default layout
		return parent::_item_active($item);
	}

	/**
	 * Method to create an inactive pagination string
	 *
	 * @param   PaginationObject  $item  The item to be processed
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	protected function _item_inactive(PaginationObject $item)
	{
		// Do not override pagination layout in user profile
		if (Factory::getApplication()->input->getString('controller') == 'user')
		{
			return parent::_item_inactive($item);
		}

		return LayoutHelper::render('pagination_link', ['data' => $item, 'active' => false], '', array('component' => 'com_jcomments'));
	}
}
