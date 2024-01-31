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

use Joomla\CMS\Factory;

/**
 * JComments Factory class
 *
 * @since  1.0
 */
class JcommentsFactory
{
	/**
	 * Returns a reference to the global {@link JcommentsSmilies} object, only creating it if it does not already exist.
	 *
	 * @return  JcommentsSmilies
	 *
	 * @since   3.0
	 */
	public static function getSmilies()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsSmilies;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JcommentsBbcode} object, only creating it if it does not already exist.
	 *
	 * @return JcommentsBbcode
	 *
	 * @since  3.0
	 */
	public static function getBbcode()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsBbcode;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JCommentsAcl} object, only creating it if it doesn't already exist.
	 *
	 * @return  JCommentsAcl
	 *
	 * @since   4.0
	 */
	public static function getAcl()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsAcl;
		}

		return $instance;
	}

	/**
	 * Return the current state of the language filter.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public static function getLanguageFilter(): bool
	{
		static $enabled = null;

		if (!isset($enabled))
		{
			$app = Factory::getApplication();

			// SiteApplication class is not available in admin.
			if ($app->isClient('site'))
			{
				$enabled = $app->getLanguageFilter();
			}
			else
			{
				/** @var \Joomla\Database\DatabaseDriver $db */
				$db = Factory::getContainer()->get('DatabaseDriver');

				$query = $db->getQuery(true)
					->select($db->quoteName('enabled'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
					->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
					->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));

				$db->setQuery($query);
				$enabled = $db->loadResult();
			}
		}

		return (bool) $enabled;
	}
}
