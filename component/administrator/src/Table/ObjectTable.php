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

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * JComments objects table.
 * Store information about component item(e.g. com_content article item). Used to easy access to item parameters.
 *
 * @property integer id
 * @property integer object_id
 * @property string  object_group
 * @property integer category_id
 * @property string  lang
 * @property string  title
 * @property string  link
 * @property integer access
 * @property integer userid
 * @property string  expired
 * @property string  modified
 *
 * @since  4.1
 */
class ObjectTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.5
	 */
	public function __construct($db)
	{
		parent::__construct('#__jcomments_objects', 'id', $db);
	}
}
