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
 * JComments report table
 *
 * @property   integer  $commentid
 * @property   integer  $userid
 * @property   integer  $name
 * @property   integer  $ip
 * @property   integer  $date
 * @property   integer  $reason
 *
 * @since  1.5
 */
class ReportTable extends Table
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
		parent::__construct('#__jcomments_reports', 'id', $db);
	}
}
