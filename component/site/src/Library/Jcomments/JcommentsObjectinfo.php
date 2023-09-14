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
 * @since   3.0
 */
class JcommentsObjectinfo
{
	/** @var integer */
	public $id = null;

	/** @var integer */
	public $object_id = null;

	/** @var string */
	public $object_group = null;

	/** @var integer */
	public $category_id = null;

	/** @var string */
	public $lang = null;

	/** @var string */
	public $title = null;

	/** @var string */
	public $link = null;

	/** @var integer */
	public $access = null;

	/** @var integer */
	public $userid = null;

	/** @var integer */
	public $expired = null;

	/** @var string */
	public $modified = null;

	/**
	 * Object info class constructor
	 *
	 * @param   null  $src  Source
	 *
	 * @since   3.0
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
