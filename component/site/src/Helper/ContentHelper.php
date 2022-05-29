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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * JComments Content Plugin Helper.
 *
 * @alias  JcommentsContentHelper
 *
 * @since  4.0
 */
class ContentHelper
{
	/**
	 *
	 * @param   object  $row           The content item object
	 * @param   array   $patterns      Array with patterns strings to search for
	 * @param   array   $replacements  Array with strings to replace
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	private static function processTags($row, array $patterns = array(), array $replacements = array())
	{
		if (count($patterns) > 0)
		{
			ob_start();

			$keys = array('introtext', 'fulltext', 'text');

			foreach ($keys as $key)
			{
				if (isset($row->$key))
				{
					$row->$key = preg_replace($patterns, $replacements, $row->$key);
				}
			}

			ob_end_clean();
		}
	}

	/**
	 * Searches given tag in content object
	 *
	 * @param   object  $row      The content item object
	 * @param   string  $pattern  RegExp
	 *
	 * @return  boolean True if any tag found, False otherwise
	 *
	 * @since   1.5
	 */
	private static function findTag($row, string $pattern): bool
	{
		$keys = array('introtext', 'fulltext', 'text');

		foreach ($keys as $key)
		{
			if (isset($row->$key) && preg_match($pattern, $row->$key))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Replaces or removes commenting systems tags like {moscomment}, {jomcomment} etc
	 *
	 * @param   object   $row         The content item object
	 * @param   boolean  $removeTags  Remove all 3rd party tags or replace it to JComments tags?
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function processForeignTags($row, bool $removeTags = false)
	{
		if (false == $removeTags)
		{
			$patterns     = array('#\{(jomcomment|easycomments|KomentoEnable)\}#is', '#\{(\!jomcomment|KomentoDisable)\}#is', '#\{KomentoLock\}#is');
			$replacements = array('{jcomments on}', '{jcomments off}', '{jcomments lock}');
		}
		else
		{
			$patterns     = array('#\{(jomcomment|easycomments|KomentoEnable|KomentoDisable|KomentoLock)\}#is');
			$replacements = array('');
		}

		self::processTags($row, $patterns, $replacements);
	}

	/**
	 * Return true if one of text fields contains {jcomments on} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments on} found, False otherwise
	 *
	 * @since   1.5
	 */
	public static function isEnabled($row): bool
	{
		return self::findTag($row, '/{jcomments\s+on}/is');
	}

	/**
	 * Return true if one of text fields contains {jcomments off} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments off} found, False otherwise
	 *
	 * @since   1.5
	 */
	public static function isDisabled($row): bool
	{
		return self::findTag($row, '/{jcomments\s+off}/is');
	}

	/**
	 * Return true if one of text fields contains {jcomments lock} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments lock} found, False otherwise
	 *
	 * @since   1.5
	 */
	public static function isLocked($row): bool
	{
		return self::findTag($row, '/{jcomments\s+lock}/is');
	}

	/**
	 * Clears all JComments tags from content item
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function clear($row)
	{
		$patterns     = array('/{jcomments\s+(off|on|lock)}/is');
		$replacements = array('');

		self::processTags($row, $patterns, $replacements);
	}

	/**
	 * Checks if comments are enabled for specified category
	 *
	 * @param   integer  $id  Category ID
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 */
	public static function checkCategory(int $id): bool
	{
		$config     = ComponentHelper::getParams('com_jcomments');
		$categories = (array) $config->get('enable_categories');

		return (in_array('*', $categories) || in_array($id, $categories));
	}

	/**
	 * Get author name
	 *
	 * @param   object  $comment  Comment object
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	public static function getCommentAuthorName($comment): string
	{
		$name = '';

		if ($comment != null)
		{
			$config = ComponentHelper::getParams('com_jcomments');

			if ($comment->userid && $config->get('display_author') == 'username' && $comment->username != '')
			{
				$name = $comment->username;
			}
			else
			{
				$name = $comment->name ?: 'Guest';
			}
		}

		return $name;
	}
}
