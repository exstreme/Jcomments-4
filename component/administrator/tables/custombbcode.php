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

use Joomla\CMS\Table\Table;

/**
 * JComments CustomBBCodes table
 */
class JCommentsTableCustomBBCode extends Table
{
	protected $_supportNullValue = true;

	public function __construct($_db)
	{
		parent::__construct('#__jcomments_custom_bbcodes', 'id', $_db);
	}

	public function check()
	{
		if (empty($this->ordering))
		{
			$this->ordering = self::getNextOrder();
		}

		return true;
	}

	public function store($updateNulls = false)
	{
		parent::store($updateNulls);

		return true;
	}
}
