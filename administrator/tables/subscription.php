<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * JComments subscriptions table
 *
 */
class JCommentsTableSubscription extends JTable
{
	/** @var int Primary key */
	var $id = null;
	/** @var int */
	var $object_id = null;
	/** @var string */
	var $object_group = null;
	/** @var string */
	var $lang = null;
	/** @var int */
	var $userid = null;
	/** @var string */
	var $name = null;
	/** @var string */
	var $email = null;
	/** @var string */
	var $hash = null;
	/** @var boolean */
	var $published = null;

	public function __construct($_db)
	{
		parent::__construct('#__jcomments_subscriptions', 'id', $_db);
	}

	public function store($updateNulls = false)
	{
		if ($this->userid != 0 && empty($this->email))
		{
			// TODO How to get user object by user ID in J4?
			$user        = Factory::getUser($this->userid);
			$this->email = $user->email;
		}

		if ($this->userid == 0 && !empty($this->email))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('email') . ' = ' . $db->Quote($db->escape($this->email, true)));

			$db->setQuery($query);
			$users = $db->loadObjectList();

			if (count($users))
			{
				$this->userid = $users[0]->id;
				$this->name   = $users[0]->name;
			}
		}

		if (empty($this->lang))
		{
			$this->lang = JCommentsMultilingual::getLanguage();
		}

		$this->hash = $this->getHash();

		return parent::store($updateNulls);
	}

	public function getHash()
	{
		return md5($this->object_id . $this->object_group . $this->userid . $this->email . $this->lang);
	}
}
