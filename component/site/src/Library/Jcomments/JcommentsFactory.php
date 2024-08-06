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
}
