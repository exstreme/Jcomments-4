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
 * JComments object
 *
 * @since   2.3
 */
class JcommentsObjectinfo
{
	/**
	 * @var   integer
	 * @since 2.3
	 */
	public $object_id = null;

	/**
	 * @var   string
	 * @since 2.3
	 */
	public $object_group = null;

	/**
	 * @var   integer
	 * @since 2.3
	 */
	public $catid = null;

	/**
	 * @var   string
	 * @since 2.3
	 */
	public $object_lang = null;

	/**
	 * @var   string
	 * @since 2.3
	 */
	public $object_title = null;

	/**
	 * @var   string
	 * @since 2.3
	 */
	public $object_link = null;

	/**
	 * @var   integer
	 * @since 2.3
	 */
	public $object_access = null;

	/**
	 * @var   integer
	 * @since 2.3
	 */
	public $object_owner = null;

	/**
	 * @var   string
	 * @since 2.3
	 */
	public $expired = null;

	/**
	 * @var   string
	 * @since 2.3
	 */
	public $modified = null;

	/**
	 * Object info class constructor
	 *
	 * @param   null  $src  Source
	 *
	 * @since   2.3
	 */
	public function __construct($src = null)
	{
		if (is_object($src))
		{
			$vars = get_object_vars($this);

			foreach ($vars as $k => $v)
			{
				if (isset($src->$k))
				{
					$this->$k = $src->$k;
				}
			}
		}
	}
}
