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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsTree;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

/**
 * JComments comments table
 *
 * @property   integer $id
 * @property   integer $parent
 * @property   integer $thread_id
 * @property   string  $path
 * @property   integer $level
 * @property   integer $object_id
 * @property   string  $object_group
 * @property   string  $lang
 * @property   integer $userid
 * @property   string  $name
 * @property   string  $username
 * @property   string  $email
 * @property   string  $homepage
 * @property   string  $title
 * @property   string  $comment
 * @property   string  $ip
 * @property   string  $date
 * @property   integer $isgood
 * @property   integer $ispoor
 * @property   integer $published
 * @property   integer $deleted
 * @property   integer $subscribe
 * @property   string  $source
 * @property   integer $source_id
 * @property   integer $checked_out
 * @property   string  $checked_out_time
 * @property   integer $pinned
 *
 * @since  1.5
 */
class CommentTable extends Table
{
	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
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
		parent::__construct('#__jcomments', 'id', $db);
	}

	/**
	 * Magic method to get an object property's value by name.
	 *
	 * @param   string  $name  Name of the property for which to return a value.
	 *
	 * @return  mixed  The requested value if it exists.
	 *
	 * @since   1.5
	 */
	public function __get($name)
	{
		if ($name == 'datetime')
		{
			return $this->date;
		}

		return null;
	}

	/**
	 * Overrides Table::store to set modified data.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function store($updateNulls = true)
	{
		$app    = Factory::getApplication();
		$db     = $this->getDbo();
		$config = ComponentHelper::getParams('com_jcomments');

		// See clearComment() method description
		if ($app->isClient('administrator'))
		{
			$language = $app->getLanguage();
			$language->load('com_jcomments', JPATH_SITE);

			if ($this->id == 0 && !empty($this->source))
			{
				$this->comment  = $this->clearComment($this->comment);
				$this->homepage = strip_tags($this->homepage);
				$this->title    = strip_tags($this->title);

				if (!$this->userid)
				{
					$this->name     = $this->clearComment($this->name);
					$this->username = $this->clearComment($this->username);
				}
			}
		}

		if ($this->parent > 0)
		{
			$parent = new CommentTable($db);

			if ($parent->load($this->parent))
			{
				if (empty($this->title) && (int) $config->get('comment_title') == 1)
				{
					if (!empty($parent->title))
					{
						if (strpos($parent->title, Text::_('COMMENT_TITLE_RE')) === false)
						{
							$this->title = Text::_('COMMENT_TITLE_RE') . ' ' . $parent->title;
						}
						else
						{
							$this->title = $parent->title;
						}
					}
				}

				$this->thread_id = $parent->thread_id ?: $parent->id;
				$this->level     = $parent->level + 1;
				$this->path      = $parent->path . ',' . $parent->id;
			}
		}
		else
		{
			if (empty($this->title) && (int) $config->get('comment_title') == 1)
			{
				$title = ObjectHelper::getObjectField(null, 'object_title', $this->object_id, $this->object_group, $this->lang);

				if (!empty($title))
				{
					$this->title = Text::_('COMMENT_TITLE_RE') . ' ' . $title;
				}
			}

			$this->path = '0';
		}

		if (isset($this->datetime))
		{
			unset($this->datetime);
		}

		if (isset($this->author))
		{
			unset($this->author);
		}

		if ($this->pinned != 1)
		{
			$this->pinned = null;
		}

		return parent::store($updateNulls);
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed  $pk  An optional primary key value to delete. If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \UnexpectedValueException
	 * @since   3.0
	 */
	public function delete($pk = null)
	{
		$db          = $this->getDbo();
		$id          = $pk ?: $this->{$this->getKeyName()};
		$result      = parent::delete($pk);
		$objectGroup = $db->escape($this->object_group);

		if ($result)
		{
			try
			{
				// Process nested comments (threaded mode).
				$query = $db->getQuery(true)
					->select($db->quoteName(array('id', 'parent')))
					->from($db->quoteName('#__jcomments'))
					->where($db->quoteName('object_id') . ' = :oid')
					->where($db->quoteName('object_group') . ' = :ogroup')
					->bind(':oid', $this->object_id, ParameterType::INTEGER)
					->bind(':ogroup', $objectGroup);

				$db->setQuery($query);
				$rows = $db->loadObjectList();

				$tree        = new JcommentsTree($rows);
				$descendants = $tree->descendants($id);

				unset($rows);

				if (count($descendants))
				{
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__jcomments'))
						->whereIn($db->quoteName('id'), $descendants);

					$db->setQuery($query);
					$db->execute();

					$descendants[] = $id;
					$where = ' IN (' . implode(',', $descendants) . ')';
					unset($descendants);
				}
				else
				{
					$where = ' = ' . (int) $id;
				}

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('commentid') . $where);
				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('commentid') . $where);
				$db->setQuery($query);
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}

		return $result;
	}

	/**
	 * Mark comment as deleted.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function markAsDeleted(): bool
	{
		$this->title = '';
		$this->deleted = 1;

		return $this->store();
	}

	/**
	 * Method to set the pinned state for a row or list of rows in the database table.
	 *
	 * The method respects checked out rows by other users and will attempt to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update. If not set the instance property value is used.
	 * @param   integer  $state   The pinned state. eg. [0 = unpinned, 1 = pinned]
	 * @param   integer  $userId  The user ID of the user performing the operation.
	 *
	 * @return  boolean  True on success; false if $pks is empty.
	 *
	 * @since   4.1
	 */
	public function pin($pks = null, $state = 0, $userId = 0): bool
	{
		// Sanitize input
		$userId = (int) $userId;
		$state  = (int) $state;

		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableBeforePin',
			[
				'subject' => $this,
				'pks'     => $pks,
				'state'   => $state,
				'userId'  => $userId,
			]
		);
		$this->getDispatcher()->dispatch('onTableBeforePin', $event);

		if (!is_null($pks))
		{
			if (!is_array($pks))
			{
				$pks = [$pks];
			}

			foreach ($pks as $key => $pk)
			{
				if (!is_array($pk))
				{
					$pks[$key] = [$this->_tbl_key => $pk];
				}
			}
		}

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			$pk = [];

			foreach ($this->_tbl_keys as $key)
			{
				if ($this->$key)
				{
					$pk[$key] = $this->$key;
				}
				else
				{
					// We don't have a full primary key - return false
					$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

					return false;
				}
			}

			$pks = [$pk];
		}

		$pinnedField = 'pinned';
		$checkedOutField = $this->getColumnAlias('checked_out');

		foreach ($pks as $pk)
		{
			// Update the pinning state for rows with the given primary keys.
			$query = $this->_db->getQuery(true)
				->update($this->_db->quoteName($this->_tbl))
				->set($this->_db->quoteName($pinnedField) . ' = ' . ($state == 0 ? 'NULL' : $state));

			// Determine if there is checkin support for the table.
			if ($this->hasField('checked_out') || $this->hasField('checked_out_time'))
			{
				$query->where(
					'('
					. $this->_db->quoteName($checkedOutField) . ' = 0'
					. ' OR ' . $this->_db->quoteName($checkedOutField) . ' = ' . (int) $userId
					. ' OR ' . $this->_db->quoteName($checkedOutField) . ' IS NULL'
					. ')'
				);
				$checkin = true;
			}
			else
			{
				$checkin = false;
			}

			// Build the WHERE clause for the primary keys.
			$this->appendPrimaryKeys($query, $pk);

			$this->_db->setQuery($query);

			try
			{
				$this->_db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}

			// If checkin is supported and all rows were adjusted, check them in.
			if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
			{
				$this->checkIn($pk);
			}

			// If the Table instance value is in the list of primary keys that were set, set the instance.
			$ours = true;

			foreach ($this->_tbl_keys as $key)
			{
				if ($this->$key != $pk[$key])
				{
					$ours = false;
				}
			}

			if ($ours)
			{
				$this->$pinnedField = $state;
			}
		}

		$this->setError('');

		// Post-processing by observers
		$event = AbstractEvent::create(
			'onTableAfterPin',
			[
				'subject' => $this,
				'pks'     => $pks,
				'state'   => $state,
				'userId'  => $userId,
			]
		);
		$this->getDispatcher()->dispatch('onTableAfterPin', $event);

		return true;
	}

	/**
	 * Clear text from tags, replace some tags when importing comments from another component.
	 *
	 * @param   string  $value  Text to clean
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	protected function clearComment(string $value): string
	{
		// Change \n to <br />
		$value = JcommentsText::nl2br($value);

		$matches = array();
		preg_match_all('#(\[code\=?([a-z0-9]*?)\].*\[\/code\])#isUu', trim($value), $matches);

		$map = array();
		$key = '';

		foreach ($matches[1] as $code)
		{
			$key       = '{' . md5($code . $key) . '}';
			$map[$key] = $code;
			$value     = preg_replace('#' . preg_quote($code, '#') . '#isUu', $key, $value);
		}

		foreach ($map as $key => $code)
		{
			$value = preg_replace('#' . preg_quote($key, '#') . '#isUu', $code, $value);
		}

		// Strip bbcodes
		$patterns = array(
			'/\[font=(.*?)\](.*?)\[\/font\]/i',
			'/\[size=(.*?)\](.*?)\[\/size\]/i',
			'/\[color=(.*?)\](.*?)\[\/color\]/i',
			'/\[b\](null|)\[\/b\]/i',
			'/\[i\](null|)\[\/i\]/i',
			'/\[u\](null|)\[\/u\]/i',
			'/\[s\](null|)\[\/s\]/i',
			'/\[url=null\]null\[\/url\]/i',
			'/\[img\](null|)\[\/img\]/i',
			'/\[url=(.*?)\](.*?)\[\/url\]/i',
			'/\[email](.*?)\[\/email\]/i',
			// JA Comment syntax
			'/\[quote=\"?([^\:\]]+)(\:[0-9]+)?\"?\]/ism',
			'/\[link=\"?([^\]]+)\"?\]/ism',
			'/\[\/link\]/ism',
			'/\[youtube ([^\s]+) youtube\]/ism'
		);

		$replacements = array(
			'\\2',
			'\\2',
			'\\2',
			'',
			'',
			'',
			'',
			'',
			'',
			'\\2 ([url]\\1[/url])',
			'\\1',
			'[quote name="\\1"]',
			'[url=\\1]',
			'[/url]',
			'[youtube]\\1[/youtube]'
		);

		return preg_replace($patterns, $replacements, $value);
	}
}
