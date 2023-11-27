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
		$config = ComponentHelper::getParams('com_jcomments');
		$controller = Factory::getApplication()->input->getString('controller');

		// Skip doing custom page links
		if ($config->get('template_view') == 'list' && $config->get('load_cached_comments'))
		{
			return parent::_item_active($item);
		}

		// Do not override pagination layout in user profile
		if ($controller == 'user')
		{
			return LayoutHelper::render('joomla.pagination.link', ['data' => $item, 'active' => true]);
		}
		else
		{
			return LayoutHelper::render('pagination_link', ['data' => $item, 'active' => true]);
		}
	}
}
