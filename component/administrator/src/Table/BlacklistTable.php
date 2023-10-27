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

namespace Joomla\Component\Jcomments\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\IpHelper;

/**
 * JComments blacklist table
 *
 * @property    string   $ip
 * @property    integer  $userid
 * @property    string   $created
 * @property    integer  $created_by
 * @property    string   $expire
 * @property    string   $reason
 * @property    string   $notes
 *
 * @since  1.5
 */
class BlacklistTable extends Table
{
	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  3.10
	 */
	protected $_supportNullValue = true;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.5
	 */
	public function __construct($db)
	{
		parent::__construct('#__jcomments_blacklist', 'id', $db);
	}

	/**
	 * Method to perform sanity checks on the Table instance properties to ensure they are safe to store in the database.
	 *
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @since   1.7.0
	 */
	public function check()
	{
		if ($this->ip == IpHelper::getIp())
		{
			$this->setError(Text::_('A_BLACKLIST_ERROR_YOU_CAN_NOT_BAN_YOUR_IP'));

			return false;
		}

		return true;
	}

	/**
	 * Overrides Table::store to set modified data.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = true)
	{
		if (empty($this->id))
		{
			$this->created_by = Factory::getApplication()->getIdentity()->id;
			$this->created = Factory::getDate()->toSql();
		}

		if (empty($this->expire))
		{
			$this->expire = null;
		}

		return parent::store($updateNulls);
	}
}
