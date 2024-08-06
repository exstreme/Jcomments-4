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

namespace Joomla\Component\Jcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * Subscriptions class
 *
 * @since  4.0
 */
class SubscriptionsModel extends ListModel
{
	/**
	 * Checks if given user is subscribed to new comments notifications for an object
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userid       The registered user identifier
	 * @param   string   $email        User email
	 * @param   string   $language     Language tag
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function isSubscribed(int $objectID, string $objectGroup, int $userid, string $email = '', string $language = ''): bool
	{
		$this->setState('object_id', $objectID);
		$this->setState('object_group', $objectGroup);
		$this->setState('list.options.userid', $userid);
		$this->setState('email', $email);

		if (empty($language))
		{
			$language = Factory::getApplication()->getLanguage()->getTag();
		}

		$this->setState('list.options.lang', $language);

		$items  = $this->getItems();
		$result = false;

		if (!empty($items))
		{
			foreach ($items as $item)
			{
				if ($item->userid === $userid)
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  $pks  An array of record primary keys.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function delete(&$pks): bool
	{
		$pks = ArrayHelper::toInteger((array) $pks);
		$db  = $this->getDatabase();
		$uid = Factory::getApplication()->getIdentity()->get('id');

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jcomments_subscriptions'))
			->whereIn($db->quoteName('id'), $pks)
			->where($db->quoteName('userid') . ' = :uid')
			->bind(':uid', $uid, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Get the master query for retrieving a list of subsciptions subject to the model state.
	 *
	 * @return  \Joomla\Database\QueryInterface
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db          = $this->getDatabase();
		$objectID    = $this->getState('object_id');
		$objectGroup = $this->getState('object_group');

		$query = $db->getQuery(true)
			->select(
				$this->getState(
					'list.select',
					array(
						$db->quoteName('s.id'),
						$db->quoteName('s.object_id'),
						$db->quoteName('s.object_group'),
						$db->quoteName('s.lang'),
						$db->quoteName('s.lang', 'language'),
						$db->quoteName('s.userid')
					)
				)
			)
			->select(
				array(
					$db->quoteName('l.lang_code', 'language'),
					$db->quoteName('l.title', 'language_title'),
					$db->quoteName('l.image', 'language_image')
				)
			);

		$query->from($db->quoteName('#__jcomments_subscriptions', 's'))
			->leftJoin(
				$db->quoteName('#__languages', 'l'),
				$db->quoteName('l.lang_code') . ' = ' . $db->quoteName('s.lang')
			);

		$objectInfo = $this->getState('list.options.object_info');

		if ($objectInfo)
		{
			$query->select(
				array(
					$db->quoteName('jo.title', 'object_title'),
					$db->quoteName('jo.link', 'object_link'),
					$db->quoteName('jo.access', 'object_access'),
					$db->quoteName('jo.userid', 'object_owner')
				)
			);

			$query->leftJoin(
				$db->quoteName('#__jcomments_objects', 'jo'),
				$db->quoteName('jo.object_id') . ' = ' . $db->quoteName('s.object_id')
				. ' AND ' . $db->quoteName('jo.object_group') . ' = ' . $db->quoteName('s.object_group')
				. ' AND ' . $db->quoteName('jo.lang') . ' = ' . $db->quoteName('s.lang')
			);
		}
		else
		{
			$query->select('CAST(NULL AS CHAR(0)) AS object_title')
				->select('CAST(NULL AS CHAR(0)) AS object_link')
				->select('0 AS object_access')
				->select('0 AS object_owner');
		}

		if (!empty($objectID))
		{
			$query->where($db->quoteName('s.object_id') . ' = :oid')
				->bind(':oid', $objectID, ParameterType::INTEGER);
		}

		if (!empty($objectGroup))
		{
			if (is_array($objectGroup))
			{
				$filter = InputFilter::getInstance();
				$objectGroup = array_map(
					function ($objectGroup) use ($filter)
					{
						return $filter->clean($objectGroup, 'cmd');
					},
					$objectGroup
				);

				$query->where(
					'(' . $db->quoteName('s.object_group') . " = '"
					. implode("' OR " . $db->quoteName('s.object_group') . " = '", $objectGroup) . "')"
				);
			}
			else
			{
				$query->where($db->quoteName('s.object_group') . ' = :ogroup')
					->bind(':ogroup', $objectGroup);
			}
		}

		$published = $this->getState('list.options.published');

		if (!is_null($published))
		{
			$query->where($db->quoteName('s.published') . ' = :state')
				->bind(':state', $published, ParameterType::INTEGER);
		}

		$userid = $this->getState('list.options.userid');

		$query->where($db->quoteName('s.userid') . ' = :uid')
			->bind(':uid', $userid, ParameterType::INTEGER);

		if ($userid === 0)
		{
			$email = $this->getState('email');

			$query->where($db->quoteName('s.email') . ' = :email')
				->bind(':email', $email);
		}

		$lang = $this->getState('list.options.lang');

		if (Multilanguage::isEnabled() && !is_null($lang))
		{
			$language = !empty($lang) ? $lang : Factory::getApplication()->getLanguage()->getTag();

			$query->where($db->quoteName('s.lang') . ' = :lang')
				->bind(':lang', $language);
		}

		$query->order(
			$db->escape($this->getState('list.ordering', 's.id')) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
		);

		return $query;
	}

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
	 * @throws  \Exception
	 * @since   4.1
	 */
	protected function populateState($ordering = 's.id', $direction = 'ASC')
	{
		parent::populateState($ordering, $direction);

		$app = Factory::getApplication();

		// Load the parameters.
		$params = ComponentHelper::getParams('com_jcomments');
		$this->setState('params', $params);

		$objectGroup = $app->input->getCmd('object_group', '');
		$this->setState('object_group', $objectGroup);

		$objectID = $app->input->getInt('object_id', 0);
		$this->setState('object_id', $objectID);

		// List state information
		$limit = $app->input->get('limit', $app->get('list_limit', 0), 'uint');
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $limitstart);

		$this->setState('list.ordering', 's.id');
		$this->setState('list.direction', 'ASC');
		$this->setState('list.options.published', 1);
		$this->setState('list.options.userid', $app->getIdentity()->get('id'));
		$this->setState('list.options.lang');
	}
}
