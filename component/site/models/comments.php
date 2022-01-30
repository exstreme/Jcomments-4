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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;

/**
 * Comments list class
 *
 * @since  4.0
 */
class JcommentsModelComments extends ListModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $context = 'com_jcomments.comments';

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		$app    = Factory::getApplication();
		$params = new Registry;

		if ($menu = $app->getMenu()->getActive())
		{
			$params->loadString($menu->getParams());
		}

		$this->setState('params', $params);

		$limit = $params->get('list_limit');

		// Override default limit settings and respect user selection if 'show_pagination_limit' is set to Yes.
		if ($params->get('show_pagination_limit'))
		{
			$limit = $app->getUserStateFromRequest('list.limit', 'limit', $params->get('list_limit'), 'uint');
		}

		$this->setState('list.limit', $limit);

		$limitstart = $app->input->getUInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		$this->setState('list.ordering', $params->get('orderby'));
		$this->setState('list.direction', $params->get('ordering'));
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   4.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('list.limit');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   4.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$user  = Factory::getApplication()->getIdentity();
		$query = $db->getQuery(true)
			->select(
				$this->getState(
					'list.select',
					$db->quoteName(array('id', 'title', 'comment', 'date'))
				)
			)
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('published') . ' IN (0,1)')
			->where($db->quoteName('userid') . ' = ' . $user->get('id'))
			->order($db->quote($this->getState('list.ordering', 'title')) . ' ' . $this->getState('list.direction', 'ASC'));

		return $query;
	}
}
