<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

/**
 * JComments object
 *
 * @since   3.0
 */
class JCommentsObjectInfo
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

	/** @var datetime */
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
		if ($src !== null && is_object($src))
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
