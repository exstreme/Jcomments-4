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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * JComments comments table
 *
 */
class JCommentsTableComment extends Table
{
	/** @var int Primary key */
	var $id = null;
	/** @var int */
	var $parent = null;
	/** @var int */
	var $thread_id = null;
	/** @var string */
	var $path = null;
	/** @var int */
	var $level = null;
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
	var $username = null;
	/** @var string */
	var $title = null;
	/** @var string */
	var $comment = null;
	/** @var string */
	var $email = null;
	/** @var string */
	var $homepage = null;
	/** @var datetime */
	var $date = null;
	/** @var string */
	var $ip = null;
	/** @var int */
	var $isgood = null;
	/** @var int */
	var $ispoor = null;
	/** @var boolean */
	var $published = null;
	/** @var boolean */
	var $deleted = null;
	/** @var boolean */
	var $subscribe = null;
	/** @var string */
	var $source = null;
	/** @var boolean */
	var $checked_out = 0;
	/** @var datetime */
	var $checked_out_time = 0;
	/** @var string */
	var $editor = '';


	/**
	 * Object constructor to set table and key fields.  In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param   DatabaseDriver  $table  Name of the table to model.
	 *
	 * @since   1.7.0
	 */
	public function __construct($table)
	{
		parent::__construct('#__jcomments', 'id', $table);
	}

	/**
	 * Magic method to get an object property's value by name.
	 *
	 * @param   string  $name  Name of the property for which to return a value.
	 *
	 * @return  mixed  The requested value if it exists.
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'datetime':
				return $this->date;
		}

		return null;
	}

	public function store($updateNulls = false)
	{
		require_once JPATH_ROOT . '/components/com_jcomments/helpers/object.php';

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$config = ComponentHelper::getParams('com_jcomments');
		$app    = Factory::getApplication();

		if (JCommentsSystemPluginHelper::isAdmin($app))
		{
			$language = Factory::getApplication()->getLanguage();
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
			$parent = new JCommentsTableComment($this->_db);

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

				$this->thread_id = $parent->thread_id ? $parent->thread_id : $parent->id;
				$this->level     = $parent->level + 1;
				$this->path      = $parent->path . ',' . $parent->id;
			}
		}
		else
		{
			if (empty($this->title) && (int) $config->get('comment_title') == 1)
			{
				$title = JCommentsObjectHelper::getTitle($this->object_id, $this->object_group, $this->lang);

				if (!empty($title))
				{
					$this->title = Text::_('COMMENT_TITLE_RE') . ' ' . $title;
				}
			}

			$this->path = '0';
		}

		// Update language in objects table
		$query = $db->getQuery(true)
			->update($db->quoteName('#__jcomments_objects'))
			->set($db->quoteName('lang') . ' = ' . $db->quote($this->lang))
			->where($db->quoteName('object_id') . ' = ' . (int) $this->object_id)
			->where($db->quoteName('object_group') . ' = ' .  $db->quote($this->object_group));

		$db->setQuery($query);
		$db->execute();

		// Update language in subscriptions table
		$query = $db->getQuery(true)
			->update($db->quoteName('#__jcomments_subscriptions'))
			->set($db->quoteName('lang') . ' = ' . $db->quote($this->lang))
			->where($db->quoteName('object_id') . ' = ' . (int) $this->object_id)
			->where($db->quoteName('object_group') . ' = ' .  $db->quote($this->object_group));

		$db->setQuery($query);
		$db->execute();

		if (isset($this->datetime))
		{
			unset($this->datetime);
		}

		if (isset($this->author))
		{
			unset($this->author);
		}

		return parent::store($updateNulls);
	}

	public function delete($oid = null)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$id     = $oid ? $oid : $this->{$this->getKeyName()};
		$result = parent::delete($oid);

		if ($result)
		{
			// Process nested comments (threaded mode).
			$query = $db->getQuery(true)
				->select($db->quoteName(array('id', 'parent')))
				->from($db->quoteName('#__jcomments'))
				->where($db->quoteName('object_group') . ' = ' . $db->quote($this->object_group))
				->where($db->quoteName('object_id') . ' = ' . (int) $this->object_id);

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			require_once JPATH_ROOT . '/components/com_jcomments/libraries/joomlatune/tree.php';

			$tree        = new JoomlaTuneTree($rows);
			$descendants = $tree->descendants($id);

			unset($rows);

			if (count($descendants))
			{
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments'))
					->where($db->quoteName('id') . ' IN (' . implode(',', $descendants) . ')');
				$db->setQuery($query);
				$db->execute();

				$descendants[] = $id;
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('commentid') . ' IN (' . implode(',', $descendants) . ')');
				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('commentid') . ' IN (' . implode(',', $descendants) . ')');
				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				// Delete comment's vote info
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('commentid') . ' = ' . (int) $id);
				$db->setQuery($query);
				$db->execute();

				// Delete comment's reports info
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('commentid') . ' = ' . (int) $id);
				$db->setQuery($query);
				$db->execute();
			}

			unset($descendants);
		}

		return $result;
	}

	public function markAsDeleted()
	{
		$this->title   = null;
		$this->deleted = 1;
		$this->store();
	}

	protected function clearComment($value)
	{
		require_once JPATH_ROOT . '/components/com_jcomments/classes/text.php';

		// Change \n to <br />
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

		$value = JCommentsText::nl2br($value);

		foreach ($map as $key => $code)
		{
			$value = preg_replace('#' . preg_quote($key, '#') . '#isUu', $code, $value);
		}

		// strip bbcodes
		$patterns = array(
			'/\[font=(.*?)\](.*?)\[\/font\]/i'
		, '/\[size=(.*?)\](.*?)\[\/size\]/i'
		, '/\[color=(.*?)\](.*?)\[\/color\]/i'
		, '/\[b\](null|)\[\/b\]/i'
		, '/\[i\](null|)\[\/i\]/i'
		, '/\[u\](null|)\[\/u\]/i'
		, '/\[s\](null|)\[\/s\]/i'
		, '/\[url=null\]null\[\/url\]/i'
		, '/\[img\](null|)\[\/img\]/i'
		, '/\[url=(.*?)\](.*?)\[\/url\]/i'
		, '/\[email](.*?)\[\/email\]/i'
			// JA Comment syntax
		, '/\[quote=\"?([^\:\]]+)(\:[0-9]+)?\"?\]/ism'
		, '/\[link=\"?([^\]]+)\"?\]/ism'
		, '/\[\/link\]/ism'
		, '/\[youtube ([^\s]+) youtube\]/ism'
		);

		$replacements = array(
			'\\2'
		, '\\2'
		, '\\2'
		, ''
		, ''
		, ''
		, ''
		, ''
		, ''
		, '\\2 ([url]\\1[/url])'
		, '\\1'
		, '[quote name="\\1"]'
		, '[url=\\1]'
		, '[/url]'
		, '[youtube]\\1[/youtube]'
		);
		$value        = preg_replace($patterns, $replacements, $value);

		return $value;
	}

	protected function clearName($value)
	{
		return preg_replace('/[\'"\>\<\(\)\[\]]?+/iu', '', $value);
	}
}