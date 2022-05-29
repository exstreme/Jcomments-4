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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsTree;
use Joomla\Database\DatabaseDriver;

/**
 * JComments comments table
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
		switch ($name)
		{
			case 'datetime':
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
	 * @since   1.6
	 */
	public function store($updateNulls = true)
	{
		$app    = Factory::getApplication();
		$db     = $this->getDbo();
		$config = ComponentHelper::getParams('com_jcomments');

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
				$title = ObjectHelper::getObjectField('title', $this->object_id, $this->object_group, $this->lang);

				if (!empty($title))
				{
					$this->title = Text::_('COMMENT_TITLE_RE') . ' ' . $title;
				}
			}

			$this->path = '0';
		}

		// Adjust lang field for batch operation.
		if ($app->input->post->get('task', '') == 'comment.batch')
		{
			$this->lang = $this->language;
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
		$db     = $this->getDbo();
		$id     = $pk ?: $this->{$this->getKeyName()};
		$result = parent::delete($pk);

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

			$tree        = new JcommentsTree($rows);
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

		return $result;
	}

	/**
	 * Mark comment as deleted.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function markAsDeleted()
	{
		$this->title   = '';
		$this->deleted = 1;

		return $this->store();
	}

	protected function clearComment($value)
	{
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

		//$value = JCommentsText::nl2br($value);

		foreach ($map as $key => $code)
		{
			$value = preg_replace('#' . preg_quote($key, '#') . '#isUu', $code, $value);
		}

		// Strip bbcodes
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

		return preg_replace($patterns, $replacements, $value);
	}

	protected function clearName($value)
	{
		return preg_replace('/[\'"\>\<\(\)\[\]]?+/iu', '', $value);
	}
}
